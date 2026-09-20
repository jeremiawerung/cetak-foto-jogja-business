@extends('layouts.internal')

@section('title', "Label Pengiriman {$printOrder->nomor_pesanan} - Internal")

@section('content')
<div class="no-print bg-white border-b border-neutral-200">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
        <a href="{{ route('admin.order-cetak-foto.show', $printOrder) }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Kembali</a>
        <button type="button" onclick="window.print()" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
            Print Label
        </button>
    </div>
</div>

<main class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
    <div id="label" class="mx-auto max-w-md border-2 border-neutral-800 rounded-lg p-5 bg-white space-y-4">
        <div class="text-center border-b border-dashed border-neutral-400 pb-3">
            <p class="text-xs text-neutral-500">No. Pesanan</p>
            <p class="font-bold text-lg tracking-wide">{{ $printOrder->nomor_pesanan }}</p>
        </div>

        <div>
            <p class="text-xs font-semibold text-neutral-500 uppercase">Pengirim</p>
            <p class="font-bold">{{ $toko['nama'] }}</p>
            <p class="text-sm">{{ $toko['alamat'] }}</p>
        </div>

        <div class="border-t border-dashed border-neutral-400 pt-3">
            <p class="text-xs font-semibold text-neutral-500 uppercase">Penerima</p>
            <p class="font-bold text-lg">{{ $printOrder->nama ?: '-' }}</p>
            <p class="text-sm">{{ $printOrder->no_hp ?: '-' }}</p>
            <p class="text-sm mt-1">{{ $printOrder->alamat_pengiriman }}</p>
        </div>

        <div class="border-t border-dashed border-neutral-400 pt-3 grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-xs text-neutral-500">Kurir</p>
                <p class="font-medium">{{ $printOrder->ongkir_label ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500">Berat</p>
                <p class="font-medium">{{ $printOrder->berat_total ? $printOrder->berat_total.' gram' : '-' }}</p>
            </div>
        </div>

        <div class="border-t border-dashed border-neutral-400 pt-3">
            <p class="text-xs font-semibold text-neutral-500 uppercase">Isi Paket</p>
            <ul class="text-sm list-disc pl-4">
                @foreach ($printOrder->items as $item)
                    <li>{{ $item->kategori_label }}{{ $item->varian ? ' - '.$item->varian : '' }} ({{ $item->jumlah }} pcs)</li>
                @endforeach
            </ul>
        </div>

        <div class="border-t border-dashed border-neutral-400 pt-3">
            <p class="text-xs font-semibold text-neutral-500 uppercase">No. Resi</p>
            <div class="mt-2 h-10 border-b-2 border-neutral-800"></div>
            <p class="text-[10px] text-neutral-400 mt-1">(diisi tangan setelah dapat resi dari kurir)</p>
        </div>
    </div>
</main>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        main {
            padding: 0;
            max-width: none;
        }

        #label {
            border-width: 1.5px;
            max-width: 100%;
        }
    }
</style>
@endsection
