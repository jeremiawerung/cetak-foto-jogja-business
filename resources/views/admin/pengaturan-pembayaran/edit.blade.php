@extends('layouts.internal')

@section('title', 'Pengaturan Pembayaran - Admin Katalog')

@section('content')
@include('admin.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-6">
    <div>
        <h1 class="text-xl font-bold text-neutral-900">Pengaturan Pembayaran</h1>
        <p class="mt-1 text-sm text-neutral-500">Rekening bank dan gambar QRIS yang tampil di halaman checkout customer.</p>
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

    <form method="POST" action="{{ route('admin.pengaturan-pembayaran.update') }}" enctype="multipart/form-data"
          class="rounded-xl border border-neutral-200 bg-white p-4 space-y-5 max-w-xl">
        @csrf

        <div>
            <h2 class="text-sm font-bold text-neutral-900 mb-3">Rekening Bank (Transfer)</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-neutral-500 mb-1">Nama Bank</label>
                    <input type="text" name="nama_bank" value="{{ old('nama_bank', $pengaturan->nama_bank) }}" required placeholder="BCA"
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-500 mb-1">Nomor Rekening</label>
                    <input type="text" name="no_rekening" value="{{ old('no_rekening', $pengaturan->no_rekening) }}" required placeholder="1234567890"
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-neutral-500 mb-1">Atas Nama</label>
                    <input type="text" name="atas_nama" value="{{ old('atas_nama', $pengaturan->atas_nama) }}" required placeholder="Cetak Foto Jogja"
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-neutral-100">
            <h2 class="text-sm font-bold text-neutral-900 mb-3">QRIS</h2>

            @if ($pengaturan->qris_gambar && file_exists(public_path($pengaturan->qris_gambar)))
                <img src="{{ asset($pengaturan->qris_gambar) }}" alt="QRIS" class="w-40 h-40 object-contain rounded-lg border border-neutral-200 mb-3">
            @else
                <div class="w-40 h-40 flex items-center justify-center rounded-lg border-2 border-dashed border-neutral-300 text-xs text-neutral-400 p-3 mb-3">
                    Belum ada gambar QRIS
                </div>
            @endif

            <label class="block text-xs font-medium text-neutral-500 mb-1">Ganti Gambar QRIS (opsional)</label>
            <input type="file" name="qris_gambar" accept="image/*"
                   class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-neutral-400">Format gambar, maksimal 4MB. Biarkan kosong kalau tidak ingin mengubah gambar QRIS.</p>
        </div>

        <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 transition">
            Simpan Pengaturan
        </button>
    </form>
</main>
@endsection
