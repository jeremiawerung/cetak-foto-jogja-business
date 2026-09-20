<?php

namespace Tests\Feature;

use App\Models\KategoriProduk;
use App\Models\PrintOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private function buatKategoriVarian(): KategoriProduk
    {
        $kategori = KategoriProduk::create([
            'slug' => 'polaroid_test',
            'label' => 'Polaroid',
            'pricing_mode' => 'varian',
            'satuan_label' => 'foto',
            'is_active' => true,
        ]);

        $kategori->items()->create([
            'slug' => 'ecer',
            'nama' => 'Ecer',
            'harga' => 4000,
            'is_active' => true,
            'urutan' => 1,
            'berat' => 50,
        ]);

        return $kategori;
    }

    private function buatKategoriTiered(): KategoriProduk
    {
        $kategori = KategoriProduk::create([
            'slug' => 'pas_foto_test',
            'label' => 'Pas Foto',
            'pricing_mode' => 'tiered',
            'satuan_label' => 'lembar',
            'is_active' => true,
            'berat' => 5,
        ]);

        $kategori->tiers()->create(['min' => 1, 'max' => null, 'harga' => 2000]);

        return $kategori;
    }

    private function fakeRajaOngkir(): void
    {
        Http::fake([
            '*/calculate/domestic-cost' => Http::response([
                'meta' => ['message' => 'Success', 'code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 9000, 'etd' => '2-3 day'],
                ],
            ], 200),
        ]);
    }

    private function tambahKeKeranjang(string $kategori, ?string $varian, int $jumlah): void
    {
        $this->post(route('keranjang.tambah'), array_filter([
            'kategori' => $kategori,
            'varian' => $varian,
            'jumlah' => $jumlah,
        ], fn ($v) => $v !== null));
    }

    public function test_cannot_access_informasi_with_empty_cart(): void
    {
        $this->get(route('checkout.informasi'))->assertRedirect(route('cetak-foto.index'));
    }

    public function test_cannot_access_konfirmasi_with_empty_cart(): void
    {
        $this->get(route('checkout.konfirmasi'))->assertRedirect(route('cetak-foto.index'));
    }

    public function test_menambah_produk_ke_keranjang(): void
    {
        $this->buatKategoriVarian();

        $response = $this->post(route('keranjang.tambah'), [
            'kategori' => 'polaroid_test',
            'varian' => 'ecer',
            'jumlah' => 3,
        ]);

        $response->assertRedirect(route('keranjang.index'));
        $this->assertCount(1, session('cart'));
        $this->assertSame(12000, session('cart')[0]['subtotal']);

        $this->get(route('keranjang.index'))->assertOk()->assertSee('Polaroid');
    }

    public function test_full_checkout_flow_with_ambil_toko_and_bayar_toko(): void
    {
        $this->buatKategoriVarian();
        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);

        $this->get(route('checkout.informasi'))->assertOk();

        $this->post(route('checkout.informasi.simpan'), [
            'nama' => 'Budi',
            'no_hp' => '08123456789',
        ])->assertRedirect(route('checkout.pengiriman'));

        $this->post(route('checkout.pengiriman.simpan'), [
            'metode_ambil' => 'ambil_toko',
        ])->assertRedirect(route('checkout.konfirmasi'));

        $this->get(route('checkout.konfirmasi'))->assertOk()->assertSee('Bayar di Toko');

        $response = $this->post(route('checkout.konfirmasi.simpan'), [
            'metode_bayar' => 'bayar_toko',
            'syarat_setuju' => '1',
        ]);

        $order = PrintOrder::with('items')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('checkout.sukses', $order));

        $this->assertCount(1, $order->items);
        $item = $order->items->first();
        $this->assertSame('polaroid_test', $item->kategori);
        $this->assertSame('Ecer', $item->varian);
        $this->assertSame(12000, $item->subtotal);
        $this->assertSame(150, $item->berat_subtotal);

        $this->assertSame(12000, $order->estimasi_harga);
        $this->assertSame(150, $order->berat_total);
        $this->assertSame(0, $order->biaya_ongkir);
        $this->assertSame('ambil_toko', $order->metode_ambil);
        $this->assertSame('bayar_toko', $order->metode_bayar);
        $this->assertSame('belum_bayar', $order->status_pembayaran);
        $this->assertMatchesRegularExpression('/^CFJ-\d{8}-[A-Z0-9]{6}$/', $order->nomor_pesanan);

        $this->assertNull(session('cart'));
        $this->assertNull(session('checkout'));

        $this->get(route('checkout.sukses', $order))->assertOk();
    }

    public function test_multi_item_cart_creates_order_with_multiple_line_items(): void
    {
        $this->buatKategoriVarian();
        $this->buatKategoriTiered();

        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);
        $this->tambahKeKeranjang('pas_foto_test', null, 10);

        $this->get(route('keranjang.index'))->assertOk();

        $this->post(route('checkout.informasi.simpan'), ['nama' => 'Budi']);
        $this->post(route('checkout.pengiriman.simpan'), ['metode_ambil' => 'ambil_toko']);
        $this->post(route('checkout.konfirmasi.simpan'), [
            'metode_bayar' => 'bayar_toko',
            'syarat_setuju' => '1',
        ]);

        $order = PrintOrder::with('items')->first();
        $this->assertCount(2, $order->items);
        $this->assertSame(12000 + 20000, $order->estimasi_harga);
        $this->assertSame(150 + 50, $order->berat_total);
    }

    public function test_custom_item_cannot_be_mixed_with_other_items(): void
    {
        $kategori = KategoriProduk::create(['slug' => 'custom_test', 'label' => 'Custom', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs', 'is_active' => true]);
        $kategori->items()->create(['slug' => 'item_custom', 'nama' => 'Item Custom', 'harga' => 0, 'is_custom' => true, 'is_active' => true, 'urutan' => 1]);
        $this->buatKategoriVarian();

        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);

        $response = $this->post(route('keranjang.tambah'), [
            'kategori' => 'custom_test',
            'varian' => 'item_custom',
            'jumlah' => 1,
        ]);

        $response->assertSessionHasErrors('keranjang');
        $this->assertCount(1, session('cart'));
    }

    public function test_cannot_add_other_item_when_cart_has_custom_item(): void
    {
        $kategori = KategoriProduk::create(['slug' => 'custom_test', 'label' => 'Custom', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs', 'is_active' => true]);
        $kategori->items()->create(['slug' => 'item_custom', 'nama' => 'Item Custom', 'harga' => 0, 'is_custom' => true, 'is_active' => true, 'urutan' => 1]);
        $this->buatKategoriVarian();

        $this->tambahKeKeranjang('custom_test', 'item_custom', 1);

        $response = $this->post(route('keranjang.tambah'), [
            'kategori' => 'polaroid_test',
            'varian' => 'ecer',
            'jumlah' => 1,
        ]);

        $response->assertSessionHasErrors('keranjang');
        $this->assertCount(1, session('cart'));
    }

    public function test_custom_item_skips_pengiriman_and_payment_and_goes_straight_to_konfirmasi(): void
    {
        $kategori = KategoriProduk::create(['slug' => 'custom_test', 'label' => 'Custom', 'pricing_mode' => 'varian', 'satuan_label' => 'pcs', 'is_active' => true]);
        $kategori->items()->create(['slug' => 'item_custom', 'nama' => 'Item Custom', 'harga' => 0, 'is_custom' => true, 'is_active' => true, 'urutan' => 1]);

        $this->tambahKeKeranjang('custom_test', 'item_custom', 1);

        $this->post(route('checkout.informasi.simpan'), ['nama' => 'Budi'])
            ->assertRedirect(route('checkout.konfirmasi'));

        $this->get(route('checkout.pengiriman'))->assertRedirect(route('checkout.konfirmasi'));
        $this->get(route('checkout.konfirmasi'))->assertOk();

        $response = $this->post(route('checkout.konfirmasi.simpan'), ['syarat_setuju' => '1']);

        $order = PrintOrder::with('items')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('checkout.sukses', $order));

        $this->assertTrue($order->is_custom);
        $this->assertNull($order->metode_ambil);
        $this->assertNull($order->metode_bayar);
        $this->assertSame('belum_bayar', $order->status_pembayaran);
        $this->assertTrue($order->items->first()->is_custom);
    }

    public function test_full_checkout_flow_with_dikirim_and_transfer_applies_ongkir(): void
    {
        Storage::fake('local');
        $this->fakeRajaOngkir();
        $this->buatKategoriVarian();
        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);

        $this->post(route('checkout.informasi.simpan'), ['nama' => 'Budi']);

        $this->post(route('checkout.pengiriman.simpan'), [
            'metode_ambil' => 'dikirim',
            'alamat_pengiriman' => 'Jl. Contoh No. 1',
            'tujuan_id' => 999,
            'tujuan_label' => 'Kecamatan Contoh, Kota Contoh',
            'kurir_kode' => 'jne',
            'kurir_layanan' => 'REG',
        ])->assertRedirect(route('checkout.konfirmasi'));

        $this->get(route('checkout.konfirmasi'))
            ->assertOk()
            ->assertDontSee('Bayar di Toko');

        $this->post(route('checkout.konfirmasi.simpan'), [
            'metode_bayar' => 'transfer',
            'bukti_transfer' => UploadedFile::fake()->image('bukti.jpg'),
            'syarat_setuju' => '1',
        ])->assertRedirect();

        $order = PrintOrder::first();
        $this->assertSame('dikirim', $order->metode_ambil);
        $this->assertSame(9000, $order->biaya_ongkir);
        $this->assertSame('JNE - REG (2-3 day)', $order->ongkir_label);
        $this->assertStringContainsString('Kecamatan Contoh, Kota Contoh', $order->alamat_pengiriman);
        $this->assertSame('menunggu_verifikasi', $order->status_pembayaran);
        $this->assertNotNull($order->bukti_transfer);
        Storage::disk('local')->assertExists($order->bukti_transfer);
        $this->assertSame($order->estimasi_harga + $order->biaya_ongkir, $order->totalPembayaran());
    }

    public function test_pengiriman_rejects_stale_kurir_choice(): void
    {
        $this->fakeRajaOngkir();
        $this->buatKategoriVarian();
        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);
        $this->post(route('checkout.informasi.simpan'), ['nama' => 'Budi']);

        $response = $this->post(route('checkout.pengiriman.simpan'), [
            'metode_ambil' => 'dikirim',
            'alamat_pengiriman' => 'Jl. Contoh No. 1',
            'tujuan_id' => 999,
            'tujuan_label' => 'Kecamatan Contoh, Kota Contoh',
            'kurir_kode' => 'sudah_tidak_ada',
            'kurir_layanan' => 'REG',
        ]);

        $response->assertSessionHasErrors('kurir_kode');
    }

    public function test_qris_payment_requires_bukti_upload(): void
    {
        $this->buatKategoriVarian();
        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);
        $this->post(route('checkout.informasi.simpan'), []);
        $this->post(route('checkout.pengiriman.simpan'), ['metode_ambil' => 'ambil_toko']);

        $response = $this->post(route('checkout.konfirmasi.simpan'), [
            'metode_bayar' => 'qris',
            'syarat_setuju' => '1',
        ]);

        $response->assertSessionHasErrors('bukti_transfer');
    }

    public function test_bayar_toko_rejected_when_metode_ambil_is_dikirim(): void
    {
        $this->fakeRajaOngkir();
        $this->buatKategoriVarian();
        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);
        $this->post(route('checkout.informasi.simpan'), []);

        $this->post(route('checkout.pengiriman.simpan'), [
            'metode_ambil' => 'dikirim',
            'alamat_pengiriman' => 'Jl. Contoh No. 1',
            'tujuan_id' => 999,
            'tujuan_label' => 'Kecamatan Contoh, Kota Contoh',
            'kurir_kode' => 'jne',
            'kurir_layanan' => 'REG',
        ])->assertRedirect(route('checkout.konfirmasi'));

        $response = $this->post(route('checkout.konfirmasi.simpan'), [
            'metode_bayar' => 'bayar_toko',
            'syarat_setuju' => '1',
        ]);

        $response->assertSessionHasErrors('metode_bayar');
        $this->assertNull(PrintOrder::first());
    }

    public function test_konfirmasi_requires_syarat_setuju(): void
    {
        $this->buatKategoriVarian();
        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);
        $this->post(route('checkout.informasi.simpan'), []);
        $this->post(route('checkout.pengiriman.simpan'), ['metode_ambil' => 'ambil_toko']);

        $response = $this->post(route('checkout.konfirmasi.simpan'), [
            'metode_bayar' => 'bayar_toko',
        ]);

        $response->assertSessionHasErrors('syarat_setuju');
        $this->assertNull(PrintOrder::first());
    }

    public function test_cari_tujuan_endpoint_returns_destinations(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'meta' => ['message' => 'Success', 'code' => 200, 'status' => 'success'],
                'data' => [
                    ['id' => 31580, 'label' => 'MARGO REJO, TEMPEL, SLEMAN, DI YOGYAKARTA, 55552', 'province_name' => 'DI YOGYAKARTA', 'city_name' => 'SLEMAN', 'district_name' => 'TEMPEL', 'subdistrict_name' => 'MARGO REJO', 'zip_code' => '55552'],
                ],
            ], 200),
        ]);

        $response = $this->getJson(route('checkout.pengiriman.cari-tujuan', ['q' => 'Tempel Sleman']));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', 31580);
        $response->assertJsonPath('data.0.label', 'MARGO REJO, TEMPEL, SLEMAN, DI YOGYAKARTA, 55552');
    }

    public function test_cari_tujuan_endpoint_ignores_short_keyword(): void
    {
        Http::fake();

        $response = $this->getJson(route('checkout.pengiriman.cari-tujuan', ['q' => 'ab']));

        $response->assertOk();
        $response->assertJson(['data' => []]);
        Http::assertNothingSent();
    }

    public function test_opsi_ongkir_endpoint_returns_courier_options(): void
    {
        $this->fakeRajaOngkir();
        $this->buatKategoriVarian();
        $this->tambahKeKeranjang('polaroid_test', 'ecer', 3);

        $response = $this->getJson(route('checkout.pengiriman.opsi-ongkir', ['tujuan_id' => 999]));

        $response->assertOk();
        $response->assertJsonPath('data.0.kode', 'jne');
        $response->assertJsonPath('data.0.biaya', 9000);
    }
}
