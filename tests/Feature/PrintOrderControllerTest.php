<?php

namespace Tests\Feature;

use App\Models\PrintOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrintOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function buatOrder(array $attrs = [], array $itemAttrs = []): PrintOrder
    {
        $order = PrintOrder::create(array_merge([
            'nomor_pesanan' => PrintOrder::generateNomorPesanan(),
            'estimasi_harga' => 50000,
            'nama' => 'Budi',
        ], $attrs));

        $order->items()->create(array_merge([
            'kategori' => 'pas_foto',
            'kategori_label' => 'Pas Foto',
            'jumlah' => 10,
            'harga_satuan' => 5000,
            'subtotal' => 50000,
        ], $itemAttrs));

        return $order;
    }

    public function test_guest_cannot_access_order_cetak_foto_pages(): void
    {
        $this->get(route('admin.order-cetak-foto.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_orders(): void
    {
        $user = User::factory()->create();
        $this->buatOrder(['nama' => 'Budi']);

        $this->actingAs($user)->get(route('admin.order-cetak-foto.index'))
            ->assertOk()
            ->assertSee('Budi');
    }

    public function test_admin_can_view_order_detail(): void
    {
        $user = User::factory()->create();
        $order = $this->buatOrder(['nama' => 'Budi'], ['gdrive_link' => 'https://drive.google.com/drive/folders/abc']);

        $this->actingAs($user)->get(route('admin.order-cetak-foto.show', $order))
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('https://drive.google.com/drive/folders/abc');
    }

    public function test_admin_can_update_order_status(): void
    {
        $user = User::factory()->create();
        $order = $this->buatOrder();

        $this->actingAs($user)
            ->post(route('admin.order-cetak-foto.status', $order), ['status' => 'diproses'])
            ->assertRedirect();

        $this->assertSame('diproses', $order->fresh()->status);
    }

    public function test_admin_can_save_resi_for_dikirim_order(): void
    {
        $user = User::factory()->create();
        $order = $this->buatOrder(['metode_ambil' => 'dikirim', 'ongkir_label' => 'JNE - REG']);

        $this->actingAs($user)
            ->post(route('admin.order-cetak-foto.status', $order), [
                'status' => 'diproses',
                'resi' => 'JNE1234567890',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('diproses', $order->status);
        $this->assertSame('JNE1234567890', $order->resi);
    }

    public function test_admin_can_download_customer_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('pesanan-cetak-foto/foto1.jpg', 'isi-file-dummy');

        $user = User::factory()->create();
        $order = $this->buatOrder([], ['file_paths' => ['pesanan-cetak-foto/foto1.jpg']]);
        $item = $order->items->first();

        $this->actingAs($user)
            ->get(route('admin.order-cetak-foto.download-file', [$order, $item, 0]))
            ->assertOk();
    }

    public function test_download_file_with_invalid_index_returns_404(): void
    {
        $user = User::factory()->create();
        $order = $this->buatOrder([], ['file_paths' => []]);
        $item = $order->items->first();

        $this->actingAs($user)
            ->get(route('admin.order-cetak-foto.download-file', [$order, $item, 0]))
            ->assertNotFound();
    }

    public function test_download_file_rejects_item_from_different_order(): void
    {
        $user = User::factory()->create();
        $orderA = $this->buatOrder();
        $orderB = $this->buatOrder();
        $itemB = $orderB->items->first();

        $this->actingAs($user)
            ->get(route('admin.order-cetak-foto.download-file', [$orderA, $itemB, 0]))
            ->assertNotFound();
    }

    public function test_admin_can_update_payment_status(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bukti-transfer/bukti.jpg', 'isi-bukti-dummy');

        $user = User::factory()->create();
        $order = $this->buatOrder([
            'metode_ambil' => 'ambil_toko',
            'metode_bayar' => 'transfer',
            'bukti_transfer' => 'bukti-transfer/bukti.jpg',
            'status_pembayaran' => 'menunggu_verifikasi',
        ]);

        $this->actingAs($user)
            ->post(route('admin.order-cetak-foto.pembayaran', $order), ['status_pembayaran' => 'lunas'])
            ->assertRedirect();

        $this->assertSame('lunas', $order->fresh()->status_pembayaran);
    }

    public function test_admin_can_view_bukti_transfer(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bukti-transfer/bukti.jpg', 'isi-bukti-dummy');

        $user = User::factory()->create();
        $order = $this->buatOrder([
            'metode_bayar' => 'transfer',
            'bukti_transfer' => 'bukti-transfer/bukti.jpg',
            'status_pembayaran' => 'menunggu_verifikasi',
        ]);

        $this->actingAs($user)
            ->get(route('admin.order-cetak-foto.bukti-transfer', $order))
            ->assertOk();
    }

    public function test_admin_can_view_label_for_dikirim_order(): void
    {
        $user = User::factory()->create();
        $order = $this->buatOrder([
            'metode_ambil' => 'dikirim',
            'alamat_pengiriman' => 'CATUR HARJO, SLEMAN | Jl. Contoh No. 1',
            'ongkir_label' => 'JNE - REG',
            'nama' => 'Budi',
        ]);

        $this->actingAs($user)->get(route('admin.order-cetak-foto.label', $order))
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('Jl. Contoh No. 1')
            ->assertSee('JNE - REG')
            ->assertSee(config('services.toko.nama'));
    }

    public function test_label_redirects_for_ambil_toko_order(): void
    {
        $user = User::factory()->create();
        $order = $this->buatOrder(['metode_ambil' => 'ambil_toko']);

        $this->actingAs($user)->get(route('admin.order-cetak-foto.label', $order))
            ->assertRedirect(route('admin.order-cetak-foto.show', $order));
    }

    public function test_admin_can_download_all_files_as_zip(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('pesanan-cetak-foto/foto1.jpg', 'isi-file-dummy-1');
        Storage::disk('local')->put('pesanan-cetak-foto/foto2.jpg', 'isi-file-dummy-2');

        $user = User::factory()->create();
        $order = $this->buatOrder([], ['file_paths' => ['pesanan-cetak-foto/foto1.jpg', 'pesanan-cetak-foto/foto2.jpg']]);

        $this->actingAs($user)
            ->get(route('admin.order-cetak-foto.download-all', $order))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=order-'.$order->id.'-foto.zip');
    }
}
