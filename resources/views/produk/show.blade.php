@extends('layouts.app')

@php
    $namaProduk = $item['nama'] ?? $kategoriData['label'];
    $gambarProduk = $item['gambar'] ?? $kategoriData['gambar'] ?? null;
    $gambarProduk = $gambarProduk && file_exists(public_path($gambarProduk)) ? $gambarProduk : null;
    $isCustom = ! empty($item['custom']);
    $deskripsiProduk = $item['rincian'] ?? $kategoriData['deskripsi'] ?? "Cetak {$namaProduk} online di Jogja, Yogyakarta & Jogjakarta. Order lewat WhatsApp, kirim se-DIY & seluruh Indonesia.";
    $hargaProduk = $item['harga'] ?? ($kategoriData['tiers'][0]['harga'] ?? null);
@endphp

@section('title', "{$namaProduk} - Cetak Foto Jogja")
@section('description', \Illuminate\Support\Str::limit("{$namaProduk}: {$deskripsiProduk}", 155))
@if ($gambarProduk)
    @section('image', asset($gambarProduk))
@endif

@if (! $isCustom && $hargaProduk)
    @push('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $namaProduk,
            'description' => \Illuminate\Support\Str::limit($deskripsiProduk, 300),
            'image' => $gambarProduk ? asset($gambarProduk) : asset('images/cetak-foto-jogja-logo-HD.png'),
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'IDR',
                'price' => (string) $hargaProduk,
                'availability' => 'https://schema.org/InStock',
                'url' => url()->current(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
    @endpush
@endif

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
    <a href="{{ route('cetak-foto.index') }}" class="text-sm text-neutral-500 hover:underline">&larr; Kembali ke Katalog</a>

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

    <div class="mt-4 grid sm:grid-cols-2 gap-8">
        <div class="rounded-2xl overflow-hidden bg-neutral-100 border border-neutral-200 aspect-square">
            @if ($gambarProduk)
                <img src="{{ asset($gambarProduk) }}" alt="{{ $namaProduk }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-neutral-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5m-18 0A2.25 2.25 0 005.25 19.5h13.5A2.25 2.25 0 0021 17.25m-18 0L8.25 11l3.5 3.5 2.25-2.25L21 17.25" />
                        <circle cx="8.25" cy="8.25" r="1.25" fill="currentColor" stroke="none" />
                    </svg>
                </div>
            @endif
        </div>

        <div>
            <p class="text-sm text-neutral-500">{{ $kategoriData['label'] }}</p>
            <h1 class="text-2xl font-extrabold text-neutral-900">{{ $namaProduk }}</h1>

            <div class="mt-3">
                @if ($kategoriData['pricing_mode'] === 'tiered')
                    <p id="harga-tampil" class="text-2xl font-bold text-rose-600">Rp{{ number_format($kategoriData['tiers'][0]['harga'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-neutral-500">per {{ $kategoriData['satuan_label'] }}, harga makin murah kalau beli banyak</p>
                @elseif ($isCustom)
                    <p class="text-2xl font-bold text-rose-600">Hubungi Admin</p>
                @else
                    <p class="text-2xl font-bold text-rose-600">Rp{{ number_format($item['harga'], 0, ',', '.') }}</p>
                    @if (!empty($item['harga_normal']))
                        <p class="text-sm text-neutral-400 line-through">Rp{{ number_format($item['harga_normal'], 0, ',', '.') }}</p>
                    @endif
                @endif
            </div>

            @if (!empty($item['rincian']))
                <p class="mt-3 text-sm text-neutral-600">{{ $item['rincian'] }}</p>
            @elseif (!empty($kategoriData['deskripsi']))
                <p class="mt-3 text-sm text-neutral-600">{{ $kategoriData['deskripsi'] }}</p>
            @endif

            @php
                $berat = $item['berat'] ?? $kategoriData['berat'] ?? null;
                $panjang = $item['panjang'] ?? $kategoriData['panjang'] ?? null;
                $lebar = $item['lebar'] ?? $kategoriData['lebar'] ?? null;
            @endphp
            @if ($berat || $panjang || $lebar)
                <div class="mt-3 flex flex-wrap gap-3 text-xs text-neutral-500">
                    @if ($panjang && $lebar)
                        <span>Ukuran: {{ rtrim(rtrim($panjang, '0'), '.') }} x {{ rtrim(rtrim($lebar, '0'), '.') }} cm</span>
                    @endif
                    @if ($berat)
                        <span>Berat: {{ $berat }} gram / {{ $kategoriData['satuan_label'] }}</span>
                    @endif
                </div>
            @endif

            @if ($kategoriData['pricing_mode'] === 'tiered')
                <div class="mt-4 overflow-x-auto rounded-lg border border-neutral-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                                <th class="py-2 px-3">Jumlah</th>
                                <th class="py-2 px-3">Harga / {{ $kategoriData['satuan_label'] }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kategoriData['tiers'] as $tier)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-2 px-3">{{ $tier['min'] }}{{ $tier['max'] ? ' - '.$tier['max'] : ' ke atas' }}</td>
                                    <td class="py-2 px-3">Rp{{ number_format($tier['harga'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <form method="POST" action="{{ route('keranjang.tambah') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="kategori" value="{{ $kategoriSlug }}">
                @if ($item)
                    <input type="hidden" name="varian" value="{{ $item['id'] }}">
                @endif

                <div>
                    <label class="block text-sm font-medium text-neutral-700">Jumlah ({{ $kategoriData['satuan_label'] }})</label>
                    <div class="mt-1 flex items-center gap-2 max-w-[200px]">
                        <button type="button" id="btn-kurang" class="h-10 w-10 shrink-0 rounded-lg border border-neutral-300 text-lg font-bold text-neutral-600 hover:bg-neutral-50">&minus;</button>
                        <input type="number" name="jumlah" id="input-jumlah" min="1" value="1" required
                               class="w-full text-center rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                        <button type="button" id="btn-tambah" class="h-10 w-10 shrink-0 rounded-lg border border-neutral-300 text-lg font-bold text-neutral-600 hover:bg-neutral-50">&plus;</button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700">Upload Foto</label>
                    <input type="file" name="foto[]" multiple accept="image/png,image/jpeg,image/webp"
                           class="mt-1 w-full text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-rose-600 file:font-semibold hover:file:bg-rose-100">
                    <p class="mt-1 text-xs text-neutral-500">Format JPG/PNG/WEBP, maks 10MB per foto, maks 20 foto.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700">Link Google Drive (opsional)</label>
                    <input type="url" name="gdrive_link" placeholder="https://drive.google.com/..."
                           class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                </div>

                @if (!$isCustom && $kategoriData['pricing_mode'] !== 'tiered')
                    <div class="rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center justify-between">
                        <span class="text-sm font-medium text-neutral-700">Subtotal</span>
                        <span id="subtotal-tampil" class="text-xl font-extrabold text-rose-600">Rp{{ number_format($item['harga'], 0, ',', '.') }}</span>
                    </div>
                @endif

                <button type="submit" class="relative z-50 w-full rounded-lg bg-rose-600 px-4 py-3 font-semibold text-white hover:bg-rose-700 transition">
                    Tambah ke Keranjang
                </button>
            </form>
        </div>
    </div>
</section>

@php
    $produkJs = [
        'pricing_mode' => $kategoriData['pricing_mode'],
        'harga' => $item['harga'] ?? null,
        'custom' => $isCustom,
        'tiers' => $kategoriData['tiers'] ?? null,
    ];
@endphp
<script>
    window.PRODUK = {!! json_encode($produkJs) !!};
</script>
@endsection
