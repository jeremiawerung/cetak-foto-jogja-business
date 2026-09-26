<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cetak Foto Jogja')</title>
    @php
        $seoDescription = trim($__env->yieldContent('description', 'Cetak foto Jogja, Yogyakarta & Jogjakarta online: pas foto, cetak reguler, pigura, polaroid, hingga sewa fotografer panggilan se-DIY (Sleman, Bantul, Kota Jogja). Order gampang lewat WhatsApp, kirim ke seluruh Indonesia.'));
        $seoImage = trim($__env->yieldContent('image', asset('images/cetak-foto-jogja-logo-HD.png')));
        $seoTitle = trim($__env->yieldContent('title', 'Cetak Foto Jogja'));
    @endphp
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('services.toko.nama') }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $seoImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => config('services.toko.nama'),
            'image' => asset('images/cetak-foto-jogja-logo-HD.png'),
            'url' => url('/'),
            'telephone' => '+'.ltrim(config('services.whatsapp.number'), '+'),
            'priceRange' => 'Rp5.000 - Rp500.000',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => config('services.toko.alamat'),
                'addressLocality' => 'Sleman',
                'addressRegion' => 'Daerah Istimewa Yogyakarta',
                'postalCode' => '55552',
                'addressCountry' => 'ID',
            ],
            'areaServed' => ['Yogyakarta', 'Jogja', 'Sleman', 'Bantul', 'Kota Yogyakarta', 'Gunungkidul', 'Kulon Progo', 'Daerah Istimewa Yogyakarta'],
            'hasMap' => 'https://maps.app.goo.gl/XV2h6SeEgFWF8is39',
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => -7.6557828,
                'longitude' => 110.3229673,
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '08:30',
                    'closes' => '18:00',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
    @stack('schema')
</head>
<body class="bg-neutral-50 text-neutral-800 antialiased">

    @include('partials.navbar')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <a href="https://wa.me/{{ config('services.whatsapp.number') }}"
       target="_blank"
       rel="noopener"
       class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 hover:bg-emerald-600 transition">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.29-1.39a9.9 9.9 0 0 0 4.75 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.96 6.45 17.5 2 12.04 2Zm0 18.05h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.14.82.84-3.06-.2-.31a8.16 8.16 0 0 1-1.26-4.36c0-4.53 3.69-8.22 8.24-8.22 2.2 0 4.27.86 5.82 2.42a8.15 8.15 0 0 1 2.41 5.81c0 4.53-3.69 8.23-8.21 8.23Zm4.51-6.16c-.25-.12-1.46-.72-1.68-.8-.23-.08-.39-.12-.56.12-.16.25-.64.8-.78.96-.14.16-.29.18-.53.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.56-1.36-.77-1.86-.2-.48-.41-.42-.56-.43h-.48c-.16 0-.43.06-.66.31-.23.25-.86.84-.86 2.05s.88 2.38 1 2.54c.12.16 1.73 2.64 4.2 3.7.59.25 1.05.4 1.4.52.59.19 1.13.16 1.55.1.47-.07 1.46-.6 1.67-1.18.2-.58.2-1.08.14-1.18-.06-.1-.22-.16-.47-.28Z"/></svg>
        Chat WA
    </a>

    @stack('scripts')
</body>
</html>
