<?php

namespace App\Http\Controllers;

use App\Services\Katalog\HargaCalculator;
use App\Services\Katalog\KatalogBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeranjangController extends Controller
{
    private const SESI = 'cart';

    public function index(Request $request): View
    {
        return view('keranjang.index', [
            'items' => $request->session()->get(self::SESI, []),
        ]);
    }

    public function tambah(Request $request, KatalogBuilder $katalogBuilder, HargaCalculator $kalkulator): RedirectResponse
    {
        $catalog = $katalogBuilder->build();

        $validated = $request->validate([
            'kategori' => ['required', 'string', 'in:'.implode(',', array_keys($catalog))],
            'varian' => ['nullable', 'string'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'foto' => ['nullable', 'array', 'max:20'],
            'foto.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'gdrive_link' => ['nullable', 'url', 'max:500'],
        ]);

        $kategoriData = $catalog[$validated['kategori']];
        $hasil = $kalkulator->hitung($kategoriData, $validated['varian'] ?? null, $validated['jumlah']);
        $beratSatuan = $kalkulator->beratSatuan($kategoriData, $validated['varian'] ?? null);

        $cart = $request->session()->get(self::SESI, []);

        if ($hasil['custom'] && count($cart) > 0) {
            return back()->withErrors(['keranjang' => 'Item custom (hubungi admin) harus dipesan sendiri, tidak bisa digabung produk lain. Kosongkan keranjang dulu.']);
        }

        if (! empty($cart) && collect($cart)->contains('is_custom', true)) {
            return back()->withErrors(['keranjang' => 'Keranjang berisi item custom, selesaikan atau hapus dulu sebelum menambah produk lain.']);
        }

        $filePaths = [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $file) {
                $filePaths[] = $file->store('pesanan-cetak-foto');
            }
        }

        $cart[] = [
            'kategori' => $validated['kategori'],
            'kategori_label' => $kategoriData['label'],
            'varian' => $validated['varian'] ?? null,
            'varian_nama' => $hasil['varian_nama'],
            'jumlah' => $validated['jumlah'],
            'harga_satuan' => $hasil['harga_satuan'],
            'subtotal' => $hasil['total'],
            'berat_satuan' => $beratSatuan,
            'berat_subtotal' => $beratSatuan !== null ? $beratSatuan * $validated['jumlah'] : null,
            'is_custom' => $hasil['custom'],
            'file_paths' => $filePaths,
            'gdrive_link' => $validated['gdrive_link'] ?? null,
        ];

        $request->session()->put(self::SESI, $cart);

        return redirect()->route('keranjang.index')->with('status', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function hapus(Request $request, int $index): RedirectResponse
    {
        $cart = $request->session()->get(self::SESI, []);

        if (isset($cart[$index])) {
            unset($cart[$index]);
            $request->session()->put(self::SESI, array_values($cart));
        }

        return back()->with('status', 'Produk dihapus dari keranjang.');
    }
}
