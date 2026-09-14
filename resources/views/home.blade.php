@extends('layouts.app')

@section('title', 'Cetak Foto Jogja - Cetak Foto & Sewa Fotografer')

@section('content')
<section class="bg-gradient-to-b from-rose-50 to-neutral-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-14 text-center">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-neutral-900">Cetak Foto Jogja</h1>
        <p class="mt-3 text-neutral-600 max-w-xl mx-auto">
            Solusi cetak foto dan sewa fotografer panggilan di Jogja. Pilih layanan yang kamu butuhkan, isi form, order langsung lewat WhatsApp.
        </p>
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <div class="grid gap-6 sm:grid-cols-2">
        <a href="{{ route('cetak-foto.index') }}" class="group block rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-rose-300 transition">
            <div class="h-12 w-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl">🖨️</div>
            <h2 class="mt-4 text-xl font-bold text-neutral-900">Cetak Foto</h2>
            <p class="mt-2 text-sm text-neutral-500">Pas foto, cetak reguler, pigura, polaroid, photo strip, photo square, photo block, mini canvas, sampai jasa editing foto.</p>
            <span class="mt-4 inline-block text-sm font-semibold text-rose-600 group-hover:underline">Lihat katalog & harga &rarr;</span>
        </a>

        <a href="{{ route('sewa-fotografer.index') }}" class="group block rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-rose-300 transition">
            <div class="h-12 w-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl">📷</div>
            <h2 class="mt-4 text-xl font-bold text-neutral-900">Sewa Fotografer</h2>
            <p class="mt-2 text-sm text-neutral-500">Fotografer datang ke lokasi acara kamu: prewedding, ulang tahun, produk, keluarga, dan acara lainnya.</p>
            <span class="mt-4 inline-block text-sm font-semibold text-rose-600 group-hover:underline">Booking jadwal &rarr;</span>
        </a>
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 sm:px-6 pb-10">
    <div class="flex items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-neutral-900">Hasil Karya Kami</h2>
            <p class="mt-1 text-sm text-neutral-500">Contoh hasil cetak foto dan jepretan dari sesi fotografer kami.</p>
        </div>
    </div>

    <div class="mt-4 columns-2 sm:columns-3 lg:columns-4 gap-3 sm:gap-4 [column-fill:balance]">
        @for ($i = 1; $i <= 8; $i++)
            @php $fotoPortofolio = "images/portofolio/{$i}.jpg"; $adaFoto = file_exists(public_path($fotoPortofolio)); @endphp
            <div class="mb-3 sm:mb-4 break-inside-avoid rounded-xl overflow-hidden bg-neutral-100 border border-neutral-200">
                @if ($adaFoto)
                    <img src="{{ asset($fotoPortofolio) }}" alt="Hasil karya Cetak Foto Jogja {{ $i }}" class="w-full h-auto max-h-96 object-cover hover:scale-105 transition duration-300">
                @else
                    <div class="aspect-square flex items-center justify-center text-neutral-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5m-18 0A2.25 2.25 0 005.25 19.5h13.5A2.25 2.25 0 0021 17.25m-18 0L8.25 11l3.5 3.5 2.25-2.25L21 17.25" />
                            <circle cx="8.25" cy="8.25" r="1.25" fill="currentColor" stroke="none" />
                        </svg>
                    </div>
                @endif
            </div>
        @endfor
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 sm:px-6 pb-14">
    <div class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6">
        <h2 class="text-lg font-bold text-neutral-900">Lokasi Kami</h2>
        <p class="mt-1 text-sm text-neutral-500">Jl. Tempel, Gendol, Margorejo, Kec. Tempel, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55552</p>

        <div class="mt-4 rounded-xl overflow-hidden border border-neutral-200">
            <iframe
                src="https://www.google.com/maps?q={{ urlencode('Jalan Tempel, Gendol, Margorejo, Kec. Tempel, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55552') }}&output=embed"
                class="w-full h-72 sm:h-96"
                style="border:0"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>

        <a href="https://maps.app.goo.gl/XV2h6SeEgFWF8is39" target="_blank" rel="noopener"
           class="mt-4 inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
            Buka di Google Maps
        </a>
    </div>
</section>
@endsection
