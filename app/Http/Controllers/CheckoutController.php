<?php

namespace App\Http\Controllers;

use App\Models\PengaturanPembayaran;
use App\Models\PrintOrder;
use App\Models\User;
use App\Notifications\OrderMasukNotification;
use App\Services\AirtableLogger;
use App\Services\Katalog\HargaCalculator;
use App\Services\Katalog\KatalogBuilder;
use App\Services\Ongkir\RajaOngkirService;
use App\Services\WhatsAppLinkBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const SESI_CART = 'cart';

    private const SESI_CHECKOUT = 'checkout';

    public function informasiForm(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->pastikanKeranjangTidakKosong($request)) {
            return $redirect;
        }

        $cart = $request->session()->get(self::SESI_CART, []);

        return view('cetak-foto.checkout.informasi', [
            'data' => $request->session()->get(self::SESI_CHECKOUT, []),
            'isCustom' => collect($cart)->contains('is_custom', true),
        ]);
    }

    public function informasiSimpan(Request $request): RedirectResponse
    {
        if ($redirect = $this->pastikanKeranjangTidakKosong($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:30'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = $request->session()->get(self::SESI_CART, []);
        $isCustom = collect($cart)->contains('is_custom', true);

        $request->session()->put(self::SESI_CHECKOUT.'.nama', $validated['nama'] ?? null);
        $request->session()->put(self::SESI_CHECKOUT.'.no_hp', $validated['no_hp'] ?? null);
        $request->session()->put(self::SESI_CHECKOUT.'.catatan', $validated['catatan'] ?? null);
        $request->session()->put(self::SESI_CHECKOUT.'.step2_done', true);

        if ($isCustom) {
            $request->session()->put(self::SESI_CHECKOUT.'.step3_done', true);

            return redirect()->route('checkout.konfirmasi');
        }

        return redirect()->route('checkout.pengiriman');
    }

    public function pengirimanForm(Request $request, KatalogBuilder $katalogBuilder, HargaCalculator $kalkulator): View|RedirectResponse
    {
        if ($redirect = $this->pastikanTahap($request, ['step2_done'])) {
            return $redirect;
        }

        $cart = $request->session()->get(self::SESI_CART, []);
        if (collect($cart)->contains('is_custom', true)) {
            return redirect()->route('checkout.konfirmasi');
        }

        [, $ringkasan] = $this->hitungUlangKeranjang($cart, $katalogBuilder, $kalkulator);

        return view('cetak-foto.checkout.pengiriman', [
            'data' => $request->session()->get(self::SESI_CHECKOUT, []),
            'beratTotal' => $ringkasan['total_berat'],
        ]);
    }

    public function cariTujuan(Request $request, RajaOngkirService $rajaOngkir): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));

        return response()->json(['data' => $rajaOngkir->cariTujuan($keyword)]);
    }

    public function opsiOngkir(Request $request, KatalogBuilder $katalogBuilder, HargaCalculator $kalkulator, RajaOngkirService $rajaOngkir): JsonResponse
    {
        $validated = $request->validate(['tujuan_id' => ['required', 'integer']]);

        $cart = $request->session()->get(self::SESI_CART, []);
        [, $ringkasan] = $this->hitungUlangKeranjang($cart, $katalogBuilder, $kalkulator);

        $berat = $ringkasan['total_berat'] ?? 1000;

        return response()->json(['data' => $rajaOngkir->hitungOngkir($validated['tujuan_id'], $berat)]);
    }

    public function pengirimanSimpan(Request $request, KatalogBuilder $katalogBuilder, HargaCalculator $kalkulator, RajaOngkirService $rajaOngkir): RedirectResponse
    {
        if ($redirect = $this->pastikanTahap($request, ['step2_done'])) {
            return $redirect;
        }

        $validated = $request->validate([
            'metode_ambil' => ['required', Rule::in(array_keys(PrintOrder::METODE_AMBIL))],
            'alamat_pengiriman' => ['required_if:metode_ambil,dikirim', 'nullable', 'string', 'max:1000'],
            'tujuan_id' => ['required_if:metode_ambil,dikirim', 'nullable', 'integer'],
            'tujuan_label' => ['required_if:metode_ambil,dikirim', 'nullable', 'string', 'max:255'],
            'kurir_kode' => ['required_if:metode_ambil,dikirim', 'nullable', 'string'],
            'kurir_layanan' => ['required_if:metode_ambil,dikirim', 'nullable', 'string'],
        ]);

        $isDikirim = $validated['metode_ambil'] === 'dikirim';
        $biayaOngkir = 0;
        $ongkirLabel = null;

        if ($isDikirim) {
            $cart = $request->session()->get(self::SESI_CART, []);
            [, $ringkasan] = $this->hitungUlangKeranjang($cart, $katalogBuilder, $kalkulator);
            $opsi = $rajaOngkir->hitungOngkir((int) $validated['tujuan_id'], $ringkasan['total_berat'] ?? 1000);

            $dipilih = collect($opsi)->first(fn ($o) => $o['kode'] === $validated['kurir_kode'] && $o['layanan'] === $validated['kurir_layanan']);

            if (! $dipilih) {
                return back()->withErrors(['kurir_kode' => 'Layanan pengiriman yang dipilih sudah tidak tersedia, mohon pilih ulang.'])->withInput();
            }

            $biayaOngkir = $dipilih['biaya'];
            $ongkirLabel = $dipilih['nama'].' - '.$dipilih['layanan'].(trim($dipilih['etd']) !== '' ? ' ('.$dipilih['etd'].')' : '');
        }

        $alamatLengkap = $isDikirim
            ? $validated['tujuan_label'].' | '.$validated['alamat_pengiriman']
            : null;

        $request->session()->put(self::SESI_CHECKOUT.'.metode_ambil', $validated['metode_ambil']);
        $request->session()->put(self::SESI_CHECKOUT.'.alamat_pengiriman', $alamatLengkap);
        $request->session()->put(self::SESI_CHECKOUT.'.biaya_ongkir', $biayaOngkir);
        $request->session()->put(self::SESI_CHECKOUT.'.ongkir_label', $ongkirLabel);
        $request->session()->put(self::SESI_CHECKOUT.'.step3_done', true);

        return redirect()->route('checkout.konfirmasi');
    }

    public function konfirmasiForm(Request $request, KatalogBuilder $katalogBuilder, HargaCalculator $kalkulator): View|RedirectResponse
    {
        if ($redirect = $this->pastikanTahap($request, ['step2_done', 'step3_done'])) {
            return $redirect;
        }

        $data = $request->session()->get(self::SESI_CHECKOUT, []);
        $cart = $request->session()->get(self::SESI_CART, []);

        [$items, $ringkasan] = $this->hitungUlangKeranjang($cart, $katalogBuilder, $kalkulator);

        if (empty($items)) {
            return redirect()->route('cetak-foto.index')->with('status', 'Ada produk di keranjang yang sudah tidak tersedia, mohon ulangi dari awal.');
        }

        $pengaturanPembayaran = PengaturanPembayaran::current();

        return view('cetak-foto.checkout.konfirmasi', [
            'data' => $data,
            'items' => $items,
            'ringkasan' => $ringkasan,
            'bank' => [
                'nama_bank' => $pengaturanPembayaran->nama_bank,
                'no_rekening' => $pengaturanPembayaran->no_rekening,
                'atas_nama' => $pengaturanPembayaran->atas_nama,
            ],
            'qris' => [
                'gambar' => $pengaturanPembayaran->qris_gambar,
            ],
        ]);
    }

    public function konfirmasiSimpan(Request $request, KatalogBuilder $katalogBuilder, HargaCalculator $kalkulator, AirtableLogger $airtable, WhatsAppLinkBuilder $whatsapp): RedirectResponse
    {
        if ($redirect = $this->pastikanTahap($request, ['step2_done', 'step3_done'])) {
            return $redirect;
        }

        $data = $request->session()->get(self::SESI_CHECKOUT, []);
        $cart = $request->session()->get(self::SESI_CART, []);

        [$items, $ringkasan] = $this->hitungUlangKeranjang($cart, $katalogBuilder, $kalkulator);

        if (empty($items)) {
            return redirect()->route('cetak-foto.index')->with('status', 'Ada produk di keranjang yang sudah tidak tersedia, mohon ulangi dari awal.');
        }

        $isCustom = $ringkasan['is_custom'];

        $request->validate(['syarat_setuju' => ['accepted']]);

        $pembayaran = [];
        $buktiPath = null;

        if (! $isCustom) {
            $metodeAmbil = $data['metode_ambil'] ?? null;

            $pembayaran = Validator::make($request->all(), [
                'metode_bayar' => [
                    'required',
                    Rule::in(array_keys(PrintOrder::METODE_BAYAR)),
                    function ($attribute, $value, $fail) use ($metodeAmbil) {
                        if ($value === 'bayar_toko' && $metodeAmbil !== 'ambil_toko') {
                            $fail('Bayar di toko hanya berlaku untuk pengambilan di toko.');
                        }
                    },
                ],
                'bukti_transfer' => ['required_if:metode_bayar,transfer', 'required_if:metode_bayar,qris', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            ])->validate();

            if ($request->hasFile('bukti_transfer')) {
                $buktiPath = $request->file('bukti_transfer')->store('bukti-transfer');
            }
        }

        $statusPembayaran = 'belum_bayar';
        if (! $isCustom && in_array($pembayaran['metode_bayar'], ['transfer', 'qris'], true) && $buktiPath) {
            $statusPembayaran = 'menunggu_verifikasi';
        }

        $order = PrintOrder::create([
            'nomor_pesanan' => PrintOrder::generateNomorPesanan(),
            'estimasi_harga' => $ringkasan['total_harga'],
            'berat_total' => $ringkasan['total_berat'],
            'biaya_ongkir' => $data['biaya_ongkir'] ?? 0,
            'ongkir_label' => $data['ongkir_label'] ?? null,
            'nama' => $data['nama'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'is_custom' => $isCustom,
            'metode_ambil' => $data['metode_ambil'] ?? null,
            'alamat_pengiriman' => $data['alamat_pengiriman'] ?? null,
            'metode_bayar' => $pembayaran['metode_bayar'] ?? null,
            'bukti_transfer' => $buktiPath,
            'status_pembayaran' => $statusPembayaran,
        ]);

        foreach ($items as $item) {
            $order->items()->create([
                'kategori' => $item['kategori'],
                'kategori_label' => $item['kategori_label'],
                'varian' => $item['varian_nama'],
                'jumlah' => $item['jumlah'],
                'harga_satuan' => $item['harga_satuan'],
                'subtotal' => $item['subtotal'],
                'berat_satuan' => $item['berat_satuan'],
                'berat_subtotal' => $item['berat_subtotal'],
                'is_custom' => $item['is_custom'],
                'file_paths' => $item['file_paths'],
                'gdrive_link' => $item['gdrive_link'],
            ]);
        }

        Notification::send(User::adminKatalog(), new OrderMasukNotification($order));

        $airtable->log(config('services.airtable.table_cetak_foto'), [
            'Nomor Pesanan' => $order->nomor_pesanan,
            'Jumlah Item' => count($items),
            'Estimasi Harga' => $ringkasan['total_harga'],
            'Biaya Ongkir' => $data['biaya_ongkir'] ?? 0,
            'Nama' => $data['nama'] ?? '',
            'No HP' => $data['no_hp'] ?? '',
            'Catatan' => $data['catatan'] ?? '',
            'Metode Ambil' => $data['metode_ambil'] ?? '',
            'Metode Bayar' => $pembayaran['metode_bayar'] ?? '',
            'Status Pembayaran' => $statusPembayaran,
            'Waktu Order' => now()->toDateTimeString(),
        ]);

        $pesan = $this->susunPesanWhatsApp($ringkasan, $data, $order);
        $waLink = $whatsapp->build($pesan);

        $request->session()->forget(self::SESI_CART);
        $request->session()->forget(self::SESI_CHECKOUT);
        $request->session()->flash('wa_link', $waLink);

        return redirect()->route('checkout.sukses', $order);
    }

    public function sukses(Request $request, PrintOrder $printOrder): View
    {
        return view('cetak-foto.checkout.sukses', [
            'printOrder' => $printOrder,
            'waLink' => $request->session()->get('wa_link'),
        ]);
    }

    /**
     * Hitung ulang harga & berat tiap item keranjang dari data katalog terkini
     * (bukan pakai angka lama yang tersimpan di session), supaya tetap akurat
     * kalau admin sempat ubah harga/berat produk selagi customer checkout.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array{total_harga: int, total_berat: int|null, is_custom: bool}}
     */
    private function hitungUlangKeranjang(array $cart, KatalogBuilder $katalogBuilder, HargaCalculator $kalkulator): array
    {
        $catalog = $katalogBuilder->build();
        $items = [];
        $totalHarga = 0;
        $totalBerat = 0;
        $adaBerat = false;
        $isCustom = false;

        foreach ($cart as $line) {
            $kategoriData = $catalog[$line['kategori']] ?? null;

            if (! $kategoriData) {
                continue;
            }

            $hasil = $kalkulator->hitung($kategoriData, $line['varian'] ?? null, $line['jumlah']);
            $beratSatuan = $kalkulator->beratSatuan($kategoriData, $line['varian'] ?? null);
            $beratSubtotal = $beratSatuan !== null ? $beratSatuan * $line['jumlah'] : null;

            $items[] = [
                'kategori' => $line['kategori'],
                'kategori_label' => $kategoriData['label'],
                'varian' => $line['varian'] ?? null,
                'varian_nama' => $hasil['varian_nama'],
                'jumlah' => $line['jumlah'],
                'satuan_label' => $kategoriData['satuan_label'],
                'harga_satuan' => $hasil['harga_satuan'],
                'subtotal' => $hasil['total'],
                'berat_satuan' => $beratSatuan,
                'berat_subtotal' => $beratSubtotal,
                'is_custom' => $hasil['custom'],
                'file_paths' => $line['file_paths'] ?? [],
                'gdrive_link' => $line['gdrive_link'] ?? null,
            ];

            $totalHarga += $hasil['total'];
            $isCustom = $isCustom || $hasil['custom'];

            if ($beratSubtotal !== null) {
                $totalBerat += $beratSubtotal;
                $adaBerat = true;
            }
        }

        return [$items, [
            'total_harga' => $totalHarga,
            'total_berat' => $adaBerat ? $totalBerat : null,
            'is_custom' => $isCustom,
        ]];
    }

    private function pastikanKeranjangTidakKosong(Request $request): ?RedirectResponse
    {
        if (empty($request->session()->get(self::SESI_CART, []))) {
            return redirect()->route('cetak-foto.index')->with('status', 'Keranjang masih kosong, pilih produk dulu.');
        }

        return null;
    }

    private function pastikanTahap(Request $request, array $keys): ?RedirectResponse
    {
        if ($redirect = $this->pastikanKeranjangTidakKosong($request)) {
            return $redirect;
        }

        foreach ($keys as $key) {
            if (! $request->session()->get(self::SESI_CHECKOUT.'.'.$key)) {
                return redirect()->route('keranjang.index')->with('status', 'Mohon selesaikan dari langkah awal.');
            }
        }

        return null;
    }

    /**
     * Pesan WA sengaja dibuat ringkas - cuma konfirmasi + nomor pesanan.
     * Semua detail (item, foto, alamat, metode bayar, bukti transfer) sudah
     * tersimpan di sistem dan diakses admin lewat admin panel, tidak perlu
     * diulang di chat WA.
     */
    private function susunPesanWhatsApp(array $ringkasan, array $data, PrintOrder $order): string
    {
        $baris = [];
        $baris[] = 'Halo Cetak Foto Jogja, saya baru saja melakukan pemesanan.';
        $baris[] = '';
        $baris[] = 'No. Pesanan: '.$order->nomor_pesanan;

        if (! empty($data['nama'])) {
            $baris[] = 'Nama: '.$data['nama'];
        }

        $baris[] = '';

        if ($ringkasan['is_custom']) {
            $baris[] = 'Harga untuk pesanan ini custom, mohon diinfokan harganya ya. Terima kasih.';
        } else {
            $baris[] = 'Mohon dicek dan diproses. Terima kasih.';
        }

        return implode("\n", $baris);
    }
}
