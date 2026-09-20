<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriProduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KategoriProdukController extends Controller
{
    private const GAMBAR_DIR = 'images/kategori-produk';

    public function index(): View
    {
        $kategoris = KategoriProduk::withCount('items')->orderBy('urutan')->get();

        return view('admin.kategori-produk.index', compact('kategoris'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:kategori_produks,slug'],
            'label' => ['required', 'string', 'max:255'],
            'pricing_mode' => ['required', 'in:varian,tiered'],
            'satuan_label' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'gambar' => ['nullable', 'image', 'max:4096'],
        ]);

        $kategori = KategoriProduk::create([
            'slug' => $validated['slug'],
            'label' => $validated['label'],
            'pricing_mode' => $validated['pricing_mode'],
            'satuan_label' => $validated['satuan_label'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'is_active' => true,
            'urutan' => (KategoriProduk::max('urutan') ?? 0) + 1,
        ]);

        if ($request->hasFile('gambar')) {
            $kategori->update(['gambar' => $this->simpanGambar($request)]);
        }

        return redirect()->route('admin.kategori-produk.show', $kategori)
            ->with('status', 'Kategori baru berhasil ditambahkan.');
    }

    public function show(KategoriProduk $kategoriProduk): View
    {
        $kategoriProduk->load(['items', 'tiers']);

        return view('admin.kategori-produk.show', compact('kategoriProduk'));
    }

    public function update(Request $request, KategoriProduk $kategoriProduk): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'satuan_label' => ['required', 'string', 'max:255'],
            'panjang' => ['nullable', 'numeric', 'min:0'],
            'lebar' => ['nullable', 'numeric', 'min:0'],
            'berat' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'gambar' => ['nullable', 'image', 'max:4096'],
        ]);

        $kategoriProduk->update([
            'label' => $validated['label'],
            'satuan_label' => $validated['satuan_label'],
            'panjang' => $validated['panjang'] ?? null,
            'lebar' => $validated['lebar'] ?? null,
            'berat' => $validated['berat'] ?? null,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        if ($request->hasFile('gambar')) {
            $this->hapusGambarLama($kategoriProduk);
            $kategoriProduk->update(['gambar' => $this->simpanGambar($request)]);
        }

        return back()->with('status', 'Kategori berhasil diperbarui.');
    }

    public function toggleActive(KategoriProduk $kategoriProduk): RedirectResponse
    {
        $kategoriProduk->update(['is_active' => ! $kategoriProduk->is_active]);

        $status = $kategoriProduk->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('status', "Kategori berhasil {$status}.");
    }

    public function destroy(KategoriProduk $kategoriProduk): RedirectResponse
    {
        $this->hapusGambarLama($kategoriProduk);
        $kategoriProduk->delete();

        return redirect()->route('admin.kategori-produk.index')
            ->with('status', 'Kategori berhasil dihapus.');
    }

    private function simpanGambar(Request $request): string
    {
        $file = $request->file('gambar');
        $nama = Str::random(20).'.'.$file->getClientOriginalExtension();

        $file->move(public_path(self::GAMBAR_DIR), $nama);

        return self::GAMBAR_DIR.'/'.$nama;
    }

    private function hapusGambarLama(KategoriProduk $kategoriProduk): void
    {
        if ($kategoriProduk->gambar && file_exists(public_path($kategoriProduk->gambar))) {
            @unlink(public_path($kategoriProduk->gambar));
        }
    }
}
