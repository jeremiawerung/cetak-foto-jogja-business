@extends('layouts.internal')

@section('title', 'Dashboard Admin Katalog - Cetak Foto Jogja')

@section('content')
@include('admin.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-xl font-bold text-neutral-900">Halo, {{ auth()->user()->name }}</h1>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.kategori-produk.index') }}"
           class="block rounded-2xl border border-neutral-200 bg-white p-6 hover:border-rose-300 hover:shadow-md transition">
            <h2 class="font-bold text-neutral-900">Kelola Katalog Cetak Foto</h2>
            <p class="mt-2 text-sm text-neutral-500">Atur kategori, item/varian, harga, dan gambar produk yang tampil di halaman /cetak-foto.</p>
        </a>
        <a href="{{ route('admin.order-cetak-foto.index') }}"
           class="block rounded-2xl border border-neutral-200 bg-white p-6 hover:border-rose-300 hover:shadow-md transition">
            <h2 class="font-bold text-neutral-900">Kelola Order Cetak Foto</h2>
            <p class="mt-2 text-sm text-neutral-500">Lihat order masuk, unduh foto yang dikirim customer, dan atur status pengerjaan.</p>
        </a>
        <a href="{{ route('admin.booking-studio.index') }}"
           class="block rounded-2xl border border-neutral-200 bg-white p-6 hover:border-rose-300 hover:shadow-md transition">
            <h2 class="font-bold text-neutral-900">Kelola Booking Studio</h2>
            <p class="mt-2 text-sm text-neutral-500">Lihat jadwal booking sewa fotografer dan atur status tiap booking.</p>
        </a>
        <a href="{{ route('admin.pengaturan-pembayaran.edit') }}"
           class="block rounded-2xl border border-neutral-200 bg-white p-6 hover:border-rose-300 hover:shadow-md transition">
            <h2 class="font-bold text-neutral-900">Pengaturan Pembayaran</h2>
            <p class="mt-2 text-sm text-neutral-500">Atur nomor rekening bank dan gambar QRIS yang tampil di halaman checkout.</p>
        </a>
    </div>
</main>
@endsection
