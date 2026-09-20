@extends('layouts.internal')

@section('title', "Kelola {$kategoriProduk->label} - Internal")

@section('content')
@include('admin.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">{{ $kategoriProduk->label }}</h1>
            <p class="text-xs text-neutral-400 mt-1">
                Slug: <span class="font-mono">{{ $kategoriProduk->slug }}</span>
                &middot; Mode harga: {{ $kategoriProduk->pricing_mode === 'tiered' ? 'Tier Bertingkat' : 'Varian/Item' }}
            </p>
        </div>
        <a href="{{ route('admin.kategori-produk.index') }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Kembali</a>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <div class="flex items-center justify-between gap-3 mb-3">
            <h2 class="text-sm font-bold text-neutral-900">Detail Kategori</h2>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('admin.kategori-produk.toggle-active', $kategoriProduk) }}">
                    @csrf
                    @if ($kategoriProduk->is_active)
                        <button type="submit" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                            Nonaktifkan
                        </button>
                    @else
                        <button type="submit" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                            Aktifkan
                        </button>
                    @endif
                </form>
                <form method="POST" action="{{ route('admin.kategori-produk.destroy', $kategoriProduk) }}"
                      onsubmit="return confirm('Hapus kategori {{ $kategoriProduk->label }} beserta semua item/tier di dalamnya? Tindakan ini tidak bisa dibatalkan.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m2 0v13a1 1 0 01-1 1H8a1 1 0 01-1-1V7h10zM10 11v6m4-6v6" />
                        </svg>
                        Hapus
                    </button>
                </form>
            </div>
        </div>

        @if ($kategoriProduk->gambar)
            <img src="{{ asset($kategoriProduk->gambar) }}" alt="{{ $kategoriProduk->label }}" class="w-32 h-24 object-cover rounded-lg border border-neutral-200 mb-3">
        @endif

        <form method="POST" action="{{ route('admin.kategori-produk.update', $kategoriProduk) }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-2">
            @csrf
            <input type="text" name="label" value="{{ $kategoriProduk->label }}" required placeholder="Label"
                   class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            <input type="text" name="satuan_label" value="{{ $kategoriProduk->satuan_label }}" required placeholder="Satuan"
                   class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">

            <div class="sm:col-span-2">
                <p class="text-xs font-medium text-neutral-600 mb-1">Ukuran & Berat Produk (untuk hitung ongkir)</p>
                @if ($kategoriProduk->pricing_mode === 'varian')
                    <p class="text-xs text-neutral-400 mb-1">Mode Varian/Item — isi ukuran per item di bagian "Item / Varian" di bawah, bukan di sini.</p>
                @endif
                <div class="grid grid-cols-3 gap-3">
                    <input type="number" step="0.01" name="panjang" value="{{ $kategoriProduk->panjang }}" min="0" placeholder="Panjang (cm)"
                           class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <input type="number" step="0.01" name="lebar" value="{{ $kategoriProduk->lebar }}" min="0" placeholder="Lebar (cm)"
                           class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <input type="number" name="berat" value="{{ $kategoriProduk->berat }}" min="0" placeholder="Berat (gram)"
                           class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                </div>
            </div>

            <textarea name="deskripsi" rows="2" placeholder="Deskripsi (tampil di atas tabel harga)"
                      class="sm:col-span-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ $kategoriProduk->deskripsi }}</textarea>
            <textarea name="catatan" rows="2" placeholder="Catatan (tampil kecil di bawah tabel harga)"
                      class="sm:col-span-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ $kategoriProduk->catatan }}</textarea>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-neutral-600 mb-1">Ganti Gambar (opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="text-sm">
            </div>
            <button type="submit" class="sm:col-span-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Simpan Perubahan
            </button>
        </form>
    </section>

    @if ($kategoriProduk->pricing_mode === 'varian')
        <section class="rounded-xl border border-neutral-200 bg-white p-4">
            <h2 class="text-sm font-bold text-neutral-900">Item / Varian</h2>
            <p class="mt-1 text-xs text-neutral-500">Item yang dinonaktifkan tidak muncul di halaman /cetak-foto, tapi tidak dihapus.</p>

            <div class="mt-3 overflow-x-auto rounded-lg border border-neutral-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                            <th class="py-2 px-3">Nama</th>
                            <th class="py-2 px-3">Harga</th>
                            <th class="py-2 px-3">Keterangan</th>
                            <th class="py-2 px-3">Status</th>
                            <th class="py-2 px-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kategoriProduk->items as $item)
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-2 px-3 font-medium text-neutral-800">
                                    <div class="flex items-center gap-2">
                                        @if ($item->gambar)
                                            <img src="{{ asset($item->gambar) }}" alt="{{ $item->nama }}" class="w-8 h-8 rounded object-cover border border-neutral-200">
                                        @endif
                                        <a href="{{ route('produk.show', [$kategoriProduk->slug, $item->slug]) }}" target="_blank" class="hover:underline hover:text-rose-600">
                                            {{ $item->nama }}
                                        </a>
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-neutral-600">
                                    @if ($item->is_custom)
                                        <span class="text-neutral-400">Hubungi admin</span>
                                    @else
                                        Rp{{ number_format($item->harga, 0, ',', '.') }}
                                        @if ($item->harga_normal)
                                            <span class="ml-1 text-xs text-neutral-400 line-through">Rp{{ number_format($item->harga_normal, 0, ',', '.') }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-neutral-500">{{ $item->rincian ?? '-' }}</td>
                                <td class="py-2 px-3">
                                    @if ($item->is_active)
                                        <span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Aktif</span>
                                    @else
                                        <span class="inline-block rounded-full border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-500">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    <div class="flex items-center gap-2">
                                        <details>
                                            <summary class="cursor-pointer text-xs font-medium text-rose-600 hover:underline list-none">Edit</summary>
                                            <form method="POST" action="{{ route('admin.kategori-produk.item.update', [$kategoriProduk, $item]) }}" enctype="multipart/form-data" class="mt-2 grid gap-2 w-64">
                                                @csrf
                                                <input type="text" name="nama" value="{{ $item->nama }}" required placeholder="Nama" class="rounded-lg border-neutral-300 text-xs">
                                                <label class="text-xs text-neutral-500 -mb-1">Gambar produk (untuk halaman detail):</label>
                                                <input type="file" name="gambar" accept="image/*" class="text-xs">
                                                @if ($item->gambar)
                                                    <img src="{{ asset($item->gambar) }}" alt="{{ $item->nama }}" class="w-16 h-16 rounded object-cover border border-neutral-200">
                                                @endif
                                                <input type="text" name="slug" value="{{ $item->slug }}" required pattern="[A-Za-z0-9_-]+" placeholder="Slug" class="rounded-lg border-neutral-300 text-xs">
                                                <label class="flex items-center gap-1 text-xs text-neutral-600">
                                                    <input type="checkbox" name="is_custom" value="1" {{ $item->is_custom ? 'checked' : '' }}
                                                           onchange="this.closest('form').querySelector('[name=harga]').disabled = this.checked">
                                                    Harga custom (hubungi admin)
                                                </label>
                                                <input type="number" name="harga" value="{{ $item->harga }}" min="0" placeholder="Harga" {{ $item->is_custom ? 'disabled' : '' }}
                                                       class="rounded-lg border-neutral-300 text-xs">
                                                <input type="number" name="harga_normal" value="{{ $item->harga_normal }}" min="0" placeholder="Harga normal (opsional, coret)" class="rounded-lg border-neutral-300 text-xs">
                                                <input type="text" name="rincian" value="{{ $item->rincian }}" placeholder="Keterangan (opsional)" class="rounded-lg border-neutral-300 text-xs">
                                                <p class="text-xs text-neutral-500 -mb-1">Ukuran &amp; berat (untuk hitung ongkir):</p>
                                                <div class="grid grid-cols-3 gap-1">
                                                    <input type="number" step="0.01" name="panjang" value="{{ $item->panjang }}" min="0" placeholder="P (cm)" class="rounded-lg border-neutral-300 text-xs">
                                                    <input type="number" step="0.01" name="lebar" value="{{ $item->lebar }}" min="0" placeholder="L (cm)" class="rounded-lg border-neutral-300 text-xs">
                                                    <input type="number" name="berat" value="{{ $item->berat }}" min="0" placeholder="Gram" class="rounded-lg border-neutral-300 text-xs">
                                                </div>
                                                <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">Simpan</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('admin.kategori-produk.item.toggle-active', [$kategoriProduk, $item]) }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-neutral-500 hover:underline">
                                                {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.kategori-produk.item.destroy', [$kategoriProduk, $item]) }}"
                                              onsubmit="return confirm('Hapus item {{ $item->nama }}?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 px-3 text-center text-neutral-400">Belum ada item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('admin.kategori-produk.item.store', $kategoriProduk) }}" class="mt-4 grid gap-3 sm:grid-cols-3">
                @csrf
                <input type="text" name="nama" placeholder="Nama item (mis. Paket A)" required class="rounded-lg border-neutral-300 text-sm">
                <input type="text" name="slug" placeholder="Slug unik (mis. paket_a)" required pattern="[A-Za-z0-9_-]+" class="rounded-lg border-neutral-300 text-sm">
                <input type="text" name="rincian" placeholder="Keterangan (opsional)" class="rounded-lg border-neutral-300 text-sm">
                <label class="flex items-center gap-1 text-xs text-neutral-600 sm:col-span-3">
                    <input type="checkbox" name="is_custom" value="1" onchange="document.getElementById('new-item-harga').disabled = this.checked">
                    Harga custom (hubungi admin, tidak perlu isi harga)
                </label>
                <input id="new-item-harga" type="number" name="harga" min="0" placeholder="Harga (Rp)" class="rounded-lg border-neutral-300 text-sm">
                <input type="number" name="harga_normal" min="0" placeholder="Harga normal (opsional, coret)" class="rounded-lg border-neutral-300 text-sm">
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    Tambah Item
                </button>
            </form>
        </section>
    @else
        <section class="rounded-xl border border-neutral-200 bg-white p-4">
            <h2 class="text-sm font-bold text-neutral-900">Tier Harga</h2>
            <p class="mt-1 text-xs text-neutral-500">Harga per satuan berdasarkan rentang jumlah beli. Urutkan dari jumlah kecil ke besar, kosongkan "Sampai" untuk tier terakhir (tanpa batas atas).</p>

            <div class="mt-3 overflow-x-auto rounded-lg border border-neutral-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                            <th class="py-2 px-3">Dari</th>
                            <th class="py-2 px-3">Sampai</th>
                            <th class="py-2 px-3">Harga per Satuan</th>
                            <th class="py-2 px-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kategoriProduk->tiers as $tier)
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-2 px-3 text-neutral-700">{{ $tier->min }}</td>
                                <td class="py-2 px-3 text-neutral-700">{{ $tier->max ?? 'Tanpa batas' }}</td>
                                <td class="py-2 px-3 text-neutral-700">Rp{{ number_format($tier->harga, 0, ',', '.') }}</td>
                                <td class="py-2 px-3">
                                    <div class="flex items-center gap-2">
                                        <details>
                                            <summary class="cursor-pointer text-xs font-medium text-rose-600 hover:underline list-none">Edit</summary>
                                            <form method="POST" action="{{ route('admin.kategori-produk.tier.update', [$kategoriProduk, $tier]) }}" class="mt-2 grid gap-2 w-48">
                                                @csrf
                                                <input type="number" name="min" value="{{ $tier->min }}" min="0" required placeholder="Dari" class="rounded-lg border-neutral-300 text-xs">
                                                <input type="number" name="max" value="{{ $tier->max }}" min="0" placeholder="Sampai (kosongkan = tanpa batas)" class="rounded-lg border-neutral-300 text-xs">
                                                <input type="number" name="harga" value="{{ $tier->harga }}" min="0" required placeholder="Harga" class="rounded-lg border-neutral-300 text-xs">
                                                <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">Simpan</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('admin.kategori-produk.tier.destroy', [$kategoriProduk, $tier]) }}"
                                              onsubmit="return confirm('Hapus tier ini?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 px-3 text-center text-neutral-400">Belum ada tier.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('admin.kategori-produk.tier.store', $kategoriProduk) }}" class="mt-4 grid gap-3 sm:grid-cols-3">
                @csrf
                <input type="number" name="min" min="0" required placeholder="Dari (mis. 1)" class="rounded-lg border-neutral-300 text-sm">
                <input type="number" name="max" min="0" placeholder="Sampai (kosongkan = tanpa batas)" class="rounded-lg border-neutral-300 text-sm">
                <input type="number" name="harga" min="0" required placeholder="Harga per satuan" class="rounded-lg border-neutral-300 text-sm">
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    Tambah Tier
                </button>
            </form>
        </section>
    @endif
</main>
@endsection
