<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriProduk;
use App\Models\KategoriProdukItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KategoriProdukItemController extends Controller
{
    private const GAMBAR_DIR = 'images/kategori-produk-item';

    public function store(Request $request, KategoriProduk $kategoriProduk): RedirectResponse
    {
        $validated = $this->validateData($request, $kategoriProduk);

        if ($kategoriProduk->items()->where('slug', $validated['slug'])->exists()) {
            return back()->withErrors(['slug' => 'Slug ini sudah dipakai item lain di kategori yang sama.']);
        }

        $item = $kategoriProduk->items()->create([
            'slug' => $validated['slug'],
            'nama' => $validated['nama'],
            'harga' => $validated['is_custom'] ? 0 : $validated['harga'],
            'harga_normal' => $validated['harga_normal'] ?? null,
            'rincian' => $validated['rincian'] ?? null,
            'panjang' => $validated['panjang'] ?? null,
            'lebar' => $validated['lebar'] ?? null,
            'berat' => $validated['berat'] ?? null,
            'is_custom' => $validated['is_custom'],
            'is_active' => true,
            'urutan' => ($kategoriProduk->items()->max('urutan') ?? 0) + 1,
        ]);

        if ($request->hasFile('gambar')) {
            $item->update(['gambar' => $this->simpanGambar($request)]);
        }

        return back()->with('status', 'Item berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriProduk $kategoriProduk, KategoriProdukItem $item): RedirectResponse
    {
        abort_unless($item->kategori_produk_id === $kategoriProduk->id, 404);

        $validated = $this->validateData($request, $kategoriProduk, $item);

        $item->update([
            'slug' => $validated['slug'],
            'nama' => $validated['nama'],
            'harga' => $validated['is_custom'] ? 0 : $validated['harga'],
            'harga_normal' => $validated['harga_normal'] ?? null,
            'rincian' => $validated['rincian'] ?? null,
            'panjang' => $validated['panjang'] ?? null,
            'lebar' => $validated['lebar'] ?? null,
            'berat' => $validated['berat'] ?? null,
            'is_custom' => $validated['is_custom'],
        ]);

        if ($request->hasFile('gambar')) {
            $this->hapusGambarLama($item);
            $item->update(['gambar' => $this->simpanGambar($request)]);
        }

        return back()->with('status', 'Item berhasil diperbarui.');
    }

    public function toggleActive(KategoriProduk $kategoriProduk, KategoriProdukItem $item): RedirectResponse
    {
        abort_unless($item->kategori_produk_id === $kategoriProduk->id, 404);

        $item->update(['is_active' => ! $item->is_active]);

        $status = $item->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('status', "Item berhasil {$status}.");
    }

    public function destroy(KategoriProduk $kategoriProduk, KategoriProdukItem $item): RedirectResponse
    {
        abort_unless($item->kategori_produk_id === $kategoriProduk->id, 404);

        $this->hapusGambarLama($item);
        $item->delete();

        return back()->with('status', 'Item berhasil dihapus.');
    }

    /**
     * @return array{slug: string, nama: string, harga: int, harga_normal: int|null, rincian: string|null, is_custom: bool}
     */
    private function validateData(Request $request, KategoriProduk $kategoriProduk, ?KategoriProdukItem $item = null): array
    {
        $slugRule = Rule::unique('kategori_produk_items', 'slug')
            ->where('kategori_produk_id', $kategoriProduk->id)
            ->ignore($item);

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', $slugRule],
            'nama' => ['required', 'string', 'max:255'],
            'harga' => ['required_if:is_custom,0', 'nullable', 'integer', 'min:0'],
            'harga_normal' => ['nullable', 'integer', 'min:0'],
            'rincian' => ['nullable', 'string', 'max:255'],
            'panjang' => ['nullable', 'numeric', 'min:0'],
            'lebar' => ['nullable', 'numeric', 'min:0'],
            'berat' => ['nullable', 'integer', 'min:0'],
            'gambar' => ['nullable', 'image', 'max:4096'],
            'is_custom' => ['nullable', 'boolean'],
        ]);

        $validated['is_custom'] = $request->boolean('is_custom');
        $validated['harga'] = $validated['is_custom'] ? 0 : (int) ($validated['harga'] ?? 0);

        return $validated;
    }

    private function simpanGambar(Request $request): string
    {
        $file = $request->file('gambar');
        $nama = Str::random(20).'.'.$file->getClientOriginalExtension();

        $file->move(public_path(self::GAMBAR_DIR), $nama);

        return self::GAMBAR_DIR.'/'.$nama;
    }

    private function hapusGambarLama(KategoriProdukItem $item): void
    {
        if ($item->gambar && file_exists(public_path($item->gambar))) {
            @unlink(public_path($item->gambar));
        }
    }
}
