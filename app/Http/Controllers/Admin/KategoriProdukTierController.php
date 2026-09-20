<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriProduk;
use App\Models\KategoriProdukTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KategoriProdukTierController extends Controller
{
    public function store(Request $request, KategoriProduk $kategoriProduk): RedirectResponse
    {
        $validated = $this->validateData($request);

        $kategoriProduk->tiers()->create($validated);

        return back()->with('status', 'Tier harga berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriProduk $kategoriProduk, KategoriProdukTier $tier): RedirectResponse
    {
        abort_unless($tier->kategori_produk_id === $kategoriProduk->id, 404);

        $tier->update($this->validateData($request));

        return back()->with('status', 'Tier harga berhasil diperbarui.');
    }

    public function destroy(KategoriProduk $kategoriProduk, KategoriProdukTier $tier): RedirectResponse
    {
        abort_unless($tier->kategori_produk_id === $kategoriProduk->id, 404);

        $tier->delete();

        return back()->with('status', 'Tier harga berhasil dihapus.');
    }

    /**
     * @return array{min: int, max: int|null, harga: int}
     */
    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'min' => ['required', 'integer', 'min:0'],
            'max' => ['nullable', 'integer', 'min:0', 'gte:min'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $validated['max'] = $validated['max'] ?? null;

        return $validated;
    }
}
