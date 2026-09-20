@extends('layouts.app')

@section('title', 'Katalog & Order Cetak Foto - Cetak Foto Jogja')

@section('content')
<section class="bg-rose-50 border-b border-rose-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900">Katalog & Harga Cetak Foto</h1>
        <p class="mt-2 text-neutral-600 max-w-2xl">Pilih produk, isi jumlah, dan upload foto. Estimasi harga muncul otomatis, lalu order langsung lewat WhatsApp.</p>
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 sm:px-6 py-10 space-y-10">
    @foreach ($catalog as $kategoriId => $data)
        <div id="katalog-{{ $kategoriId }}" class="rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-4">
                    @php $gambarKatalog = !empty($data['gambar']) && file_exists(public_path($data['gambar'])) ? $data['gambar'] : null; @endphp
                    <div class="shrink-0 w-24 h-24 sm:w-28 sm:h-28 rounded-xl overflow-hidden bg-neutral-100 border border-neutral-200">
                        @if ($gambarKatalog)
                            <img src="{{ asset($gambarKatalog) }}" alt="Contoh {{ $data['label'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-neutral-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5m-18 0A2.25 2.25 0 005.25 19.5h13.5A2.25 2.25 0 0021 17.25m-18 0L8.25 11l3.5 3.5 2.25-2.25L21 17.25" />
                                    <circle cx="8.25" cy="8.25" r="1.25" fill="currentColor" stroke="none" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-neutral-900">{{ $data['label'] }}</h2>
                        @if (!empty($data['deskripsi']))
                            <p class="text-sm text-neutral-500 mt-1">{{ $data['deskripsi'] }}</p>
                        @endif
                    </div>
                </div>
                @if ($data['pricing_mode'] === 'tiered')
                    <a href="{{ route('produk.show', $kategoriId) }}"
                       class="shrink-0 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                        Lihat & Pesan
                    </a>
                @endif
            </div>

            @if ($data['pricing_mode'] === 'tiered')
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-neutral-500 border-b border-neutral-200">
                                <th class="py-2 pr-4">Jumlah Foto</th>
                                <th class="py-2">Harga / Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['tiers'] as $tier)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-2 pr-4">{{ $tier['min'] }}{{ $tier['max'] ? ' - '.$tier['max'] : ' ke atas' }} foto</td>
                                    <td class="py-2">Rp{{ number_format($tier['harga'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-neutral-500 border-b border-neutral-200">
                                <th class="py-2 pr-4">Varian</th>
                                <th class="py-2 pr-4">Harga</th>
                                <th class="py-2">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['items'] as $item)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-2 pr-4 font-medium text-neutral-800">
                                        <a href="{{ route('produk.show', [$kategoriId, $item['id']]) }}" class="hover:underline hover:text-rose-600">
                                            {{ $item['nama'] }}
                                        </a>
                                    </td>
                                    <td class="py-2 pr-4">
                                        @if (!empty($item['custom']))
                                            <span class="text-neutral-500">Hubungi admin</span>
                                        @else
                                            Rp{{ number_format($item['harga'], 0, ',', '.') }}
                                            @if (!empty($item['harga_normal']))
                                                <span class="ml-1 text-xs text-neutral-400 line-through">Rp{{ number_format($item['harga_normal'], 0, ',', '.') }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-2 text-neutral-500">{{ $item['rincian'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (!empty($data['catatan']))
                <p class="mt-3 text-xs text-neutral-500">{{ $data['catatan'] }}</p>
            @endif
        </div>
    @endforeach
</section>

<section class="bg-white border-t border-neutral-200">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10 text-center">
        <h2 class="text-xl font-bold text-neutral-900">Siap Order?</h2>
        <p class="mt-1 text-sm text-neutral-500">Klik nama produk atau "Lihat & Pesan" di atas untuk lihat detail, masukkan ke keranjang, lalu checkout.</p>
        <a href="{{ route('keranjang.index') }}"
           class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-6 py-3 font-semibold text-white hover:bg-rose-700 transition">
            Lihat Keranjang
        </a>
    </div>
</section>
@endsection
