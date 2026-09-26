@extends('layouts.app')

@section('title', 'Keranjang - Cetak Foto Jogja')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-xl font-bold text-neutral-900">Keranjang</h1>

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

    @if (empty($items))
        <div class="mt-8 text-center py-10">
            <p class="text-neutral-500">Keranjang kamu masih kosong.</p>
            <a href="{{ route('cetak-foto.index') }}" class="mt-3 inline-block text-rose-600 font-medium hover:underline">Lihat Katalog &rarr;</a>
        </div>
    @else
        @php $grandTotal = collect($items)->sum('subtotal'); @endphp

        <div class="mt-6 space-y-3">
            @foreach ($items as $i => $item)
                <div class="rounded-xl border border-neutral-200 bg-white p-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-neutral-800">{{ $item['kategori_label'] }}</p>
                        @if ($item['varian_nama'])
                            <p class="text-sm text-neutral-500">{{ $item['varian_nama'] }}</p>
                        @endif
                        <p class="text-sm text-neutral-500">{{ $item['jumlah'] }} pcs</p>
                        @if ($item['is_custom'])
                            <p class="text-xs text-amber-600 mt-1">Harga custom, dikonfirmasi admin via WhatsApp</p>
                        @else
                            <p class="text-sm font-semibold text-rose-600 mt-1">Rp{{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('keranjang.hapus', $i) }}" onsubmit="return confirm('Hapus produk ini dari keranjang?');">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Hapus</button>
                    </form>
                </div>
            @endforeach
        </div>

        @if (! collect($items)->contains('is_custom', true))
            <div class="mt-4 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center justify-between">
                <span class="text-sm font-medium text-neutral-700">Total</span>
                <span class="text-xl font-extrabold text-rose-600">Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="mt-6 flex gap-3">
            <a href="{{ route('cetak-foto.index') }}"
               class="flex-1 text-center rounded-lg border border-neutral-300 px-4 py-3 font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                Tambah Produk Lain
            </a>
            <a href="{{ route('checkout.informasi') }}"
               class="relative z-50 flex-1 text-center rounded-lg bg-rose-600 px-4 py-3 font-semibold text-white hover:bg-rose-700 transition">
                Lanjut ke Informasi
            </a>
        </div>
    @endif
</section>
@endsection
