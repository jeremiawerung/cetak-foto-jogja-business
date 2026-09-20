@extends('layouts.internal')

@section('title', 'Kelola Katalog Cetak Foto - Internal')

@section('content')
@include('admin.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-xl font-bold text-neutral-900">Kelola Katalog Cetak Foto</h1>
    <p class="mt-1 text-sm text-neutral-500">Kategori yang dinonaktifkan tidak akan muncul di halaman /cetak-foto.</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="mt-6 rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900">Tambah Kategori Baru</h2>
        <p class="mt-1 text-xs text-neutral-500">
            Slug &amp; mode harga tidak bisa diubah lagi setelah dibuat (mode harga menentukan
            apakah kategori pakai varian/item atau tier harga bertingkat).
        </p>

        <form method="POST" action="{{ route('admin.kategori-produk.store') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 sm:grid-cols-2">
            @csrf
            <input type="text" name="label" placeholder="Label (mis. Pas Foto - Paket Hemat)" required
                   class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            <input type="text" name="slug" placeholder="Slug unik (mis. pas_foto_paket)" required pattern="[A-Za-z0-9_-]+"
                   class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            <input type="text" name="satuan_label" placeholder="Satuan (mis. paket / foto / pcs)" required
                   class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            <select name="pricing_mode" required class="rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                <option value="varian">Varian/Item (daftar produk dengan harga masing-masing)</option>
                <option value="tiered">Tier Bertingkat (makin banyak beli, harga per satuan beda)</option>
            </select>
            <textarea name="deskripsi" rows="2" placeholder="Deskripsi (opsional, tampil di atas tabel harga)"
                      class="sm:col-span-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm"></textarea>
            <textarea name="catatan" rows="2" placeholder="Catatan (opsional, tampil kecil di bawah tabel harga)"
                      class="sm:col-span-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm"></textarea>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-neutral-600 mb-1">Gambar Contoh (opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="text-sm">
            </div>
            <button type="submit" class="sm:col-span-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Tambah Kategori
            </button>
        </form>
    </section>

    <div class="mt-6 overflow-x-auto rounded-xl border border-neutral-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                    <th class="py-2 px-3">Kategori</th>
                    <th class="py-2 px-3">Mode Harga</th>
                    <th class="py-2 px-3">Jumlah Item</th>
                    <th class="py-2 px-3">Status</th>
                    <th class="py-2 px-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kategoris as $kategori)
                    <tr class="border-b border-neutral-100 align-top">
                        <td class="py-2 px-3 font-medium text-neutral-800">{{ $kategori->label }}</td>
                        <td class="py-2 px-3 text-neutral-600">
                            {{ $kategori->pricing_mode === 'tiered' ? 'Tier Bertingkat' : 'Varian/Item' }}
                        </td>
                        <td class="py-2 px-3 text-neutral-600">{{ $kategori->items_count }}</td>
                        <td class="py-2 px-3">
                            @if ($kategori->is_active)
                                <span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Aktif</span>
                            @else
                                <span class="inline-block rounded-full border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="py-2 px-3">
                            <a href="{{ route('admin.kategori-produk.show', $kategori) }}" class="text-rose-600 font-medium hover:underline">Kelola &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 px-3 text-center text-neutral-400">Belum ada kategori. Tambah lewat form di atas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</main>
@endsection
