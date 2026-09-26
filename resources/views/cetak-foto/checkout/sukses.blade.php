@extends('layouts.app')

@section('title', 'Pesanan Diterima - Cetak Foto Jogja')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="bg-white">
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-16 text-center">
        <div class="mx-auto w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h1 class="mt-4 text-xl font-bold text-neutral-900">Pesanan Kamu Sudah Diterima!</h1>
        <p class="mt-1 text-sm text-neutral-500">No. Pesanan</p>
        <p class="text-lg font-bold text-rose-600">{{ $printOrder->nomor_pesanan }}</p>

        <p class="mt-4 text-sm text-neutral-600">
            Terakhir, konfirmasi pesananmu lewat WhatsApp supaya admin bisa segera memproses.
        </p>

        @if ($waLink)
            <a href="{{ $waLink }}" target="_blank" rel="noopener"
               class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-6 py-3 font-semibold text-white hover:bg-emerald-600 transition">
                Lanjut ke WhatsApp
            </a>
        @endif

        <div class="mt-8 flex items-center justify-center gap-4 text-sm">
            <a href="{{ route('cetak-foto.index') }}" class="text-neutral-500 hover:underline">&larr; Kembali ke Katalog</a>
            <a href="{{ route('cek-pesanan', ['nomor' => $printOrder->nomor_pesanan]) }}" class="text-rose-600 font-medium hover:underline">Cek Status Pesanan</a>
        </div>
    </div>
</section>
@endsection
