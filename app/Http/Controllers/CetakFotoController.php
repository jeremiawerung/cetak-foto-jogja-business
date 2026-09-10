<?php

namespace App\Http\Controllers;

use App\Models\PrintOrder;
use App\Services\AirtableLogger;
use App\Services\WhatsAppLinkBuilder;
use Illuminate\Http\Request;

class CetakFotoController extends Controller
{
    public function index()
    {
        $catalog = config('catalog');

        return view('cetak-foto.index', compact('catalog'));
    }

    public function store(Request $request, AirtableLogger $airtable, WhatsAppLinkBuilder $whatsapp)
    {
        $catalog = config('catalog');

        $validated = $request->validate([
            'kategori' => ['required', 'string', 'in:'.implode(',', array_keys($catalog))],
            'varian' => ['nullable', 'string'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'nama' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'array', 'max:20'],
            'foto.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $kategoriData = $catalog[$validated['kategori']];
        $hasil = $this->hitungHarga($kategoriData, $validated['varian'] ?? null, $validated['jumlah']);

        $filePaths = [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $file) {
                $filePaths[] = $file->store('pesanan-cetak-foto');
            }
        }

        $order = PrintOrder::create([
            'kategori' => $validated['kategori'],
            'varian' => $hasil['varian_nama'],
            'jumlah' => $validated['jumlah'],
            'estimasi_harga' => $hasil['total'],
            'nama' => $validated['nama'] ?? null,
            'no_hp' => $validated['no_hp'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'file_paths' => $filePaths,
        ]);

        $airtable->log(config('services.airtable.table_cetak_foto'), [
            'Kategori' => $kategoriData['label'],
            'Varian' => $hasil['varian_nama'],
            'Jumlah' => $validated['jumlah'],
            'Estimasi Harga' => $hasil['total'],
            'Nama' => $validated['nama'] ?? '',
            'No HP' => $validated['no_hp'] ?? '',
            'Catatan' => $validated['catatan'] ?? '',
            'Jumlah File' => count($filePaths),
            'Waktu Order' => now()->toDateTimeString(),
        ]);

        $pesan = $this->susunPesanWhatsApp($kategoriData, $hasil, $validated);
        $waLink = $whatsapp->build($pesan);

        return response()->json([
            'order_id' => $order->id,
            'estimasi_harga' => $hasil['total'],
            'wa_link' => $waLink,
        ]);
    }

    private function hitungHarga(array $kategoriData, ?string $varianId, int $jumlah): array
    {
        if ($kategoriData['pricing_mode'] === 'tiered') {
            $hargaSatuan = 0;
            foreach ($kategoriData['tiers'] as $tier) {
                if ($jumlah >= $tier['min'] && ($tier['max'] === null || $jumlah <= $tier['max'])) {
                    $hargaSatuan = $tier['harga'];
                    break;
                }
            }

            return [
                'varian_nama' => null,
                'harga_satuan' => $hargaSatuan,
                'satuan_label' => $kategoriData['satuan_label'],
                'total' => $hargaSatuan * $jumlah,
                'custom' => false,
            ];
        }

        $item = collect($kategoriData['items'])->firstWhere('id', $varianId);

        if (! $item) {
            return [
                'varian_nama' => null,
                'harga_satuan' => 0,
                'satuan_label' => $kategoriData['satuan_label'],
                'total' => 0,
                'custom' => false,
            ];
        }

        $isCustom = ! empty($item['custom']);

        return [
            'varian_nama' => $item['nama'],
            'harga_satuan' => $isCustom ? 0 : $item['harga'],
            'satuan_label' => $kategoriData['satuan_label'],
            'total' => $isCustom ? 0 : $item['harga'] * $jumlah,
            'custom' => $isCustom,
        ];
    }

    private function susunPesanWhatsApp(array $kategoriData, array $hasil, array $data): string
    {
        $baris = [];
        $baris[] = 'Halo Cetak Foto Jogja, saya ingin order:';
        $baris[] = '';
        $baris[] = 'Kategori: '.$kategoriData['label'];

        if ($hasil['varian_nama']) {
            $baris[] = 'Varian/Ukuran: '.$hasil['varian_nama'];
        }

        $baris[] = 'Jumlah: '.$data['jumlah'].' '.$hasil['satuan_label'];

        if ($hasil['custom']) {
            $baris[] = 'Estimasi Harga: Custom, mohon info harga (hubungi admin)';
        } else {
            $baris[] = 'Estimasi Harga: Rp'.number_format($hasil['total'], 0, ',', '.');
        }

        if (! empty($data['catatan'])) {
            $baris[] = 'Catatan: '.$data['catatan'];
        }

        if (! empty($data['nama'])) {
            $baris[] = '';
            $baris[] = 'Nama: '.$data['nama'];
        }

        if (! empty($data['no_hp'])) {
            $baris[] = 'No HP: '.$data['no_hp'];
        }

        $baris[] = '';
        $baris[] = 'File foto akan saya kirimkan menyusul di chat ini. Terima kasih.';

        return implode("\n", $baris);
    }
}
