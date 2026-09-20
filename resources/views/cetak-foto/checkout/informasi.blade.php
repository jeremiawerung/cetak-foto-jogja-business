@extends('layouts.app')

@section('title', 'Informasi Pemesan - Checkout Cetak Foto Jogja')

@section('content')
<section class="bg-white">
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-xl font-bold text-neutral-900">Checkout</h1>
        <p class="mt-1 text-sm text-neutral-500">Langkah 2 dari 4 — isi data pemesan.</p>

        <div class="mt-6">
            @include('cetak-foto.checkout._stepper', ['stepAktif' => 2])
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.informasi.simpan') }}" class="mt-8 space-y-5">
            @csrf

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Nama</label>
                    <input type="text" name="nama" value="{{ old('nama', $data['nama'] ?? '') }}" required
                           class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">No HP / WhatsApp</label>
                    <input type="text" name="no_hp" value="{{ old('no_hp', $data['no_hp'] ?? '') }}" required
                           class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Catatan (opsional)</label>
                <textarea name="catatan" rows="3"
                          class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">{{ old('catatan', $data['catatan'] ?? '') }}</textarea>
            </div>

            @if ($isCustom)
                <p class="rounded-lg bg-amber-50 border border-amber-100 p-3 text-xs text-amber-700">
                    Harga item ini custom (hubungi admin), jadi pengambilan &amp; pembayaran diatur langsung lewat WhatsApp setelah admin konfirmasi harga.
                </p>
            @endif

            <div class="flex gap-3">
                <a href="{{ route('keranjang.index') }}"
                   class="flex-1 text-center rounded-lg border border-neutral-300 px-4 py-3 font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                    Kembali ke Keranjang
                </a>
                <button type="submit"
                        class="flex-1 rounded-lg bg-rose-600 px-4 py-3 font-semibold text-white hover:bg-rose-700 transition">
                    {{ $isCustom ? 'Lanjut ke Konfirmasi' : 'Lanjut ke Pengiriman' }}
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
