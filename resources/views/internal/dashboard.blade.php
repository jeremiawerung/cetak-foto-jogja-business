@extends('layouts.internal')

@section('title', 'Dashboard Internal - Cetak Foto Jogja')

@section('content')
@include('internal.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-xl font-bold text-neutral-900">Halo, {{ auth()->user()->name }}</h1>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('internal.verifikasi-siswa.index') }}"
           class="block rounded-2xl border border-neutral-200 bg-white p-6 hover:border-rose-300 hover:shadow-md transition">
            <h2 class="font-bold text-neutral-900">Verifikasi Siswa</h2>
            <p class="mt-2 text-sm text-neutral-500">Upload roster sekolah, sinkron Google Form otomatis, dan pantau siapa yang belum isi/konflik.</p>
        </a>
    </div>
</main>
@endsection
