@extends('layouts.internal')

@section('title', 'Kelola Order Cetak Foto - Internal')

@section('content')
@include('admin.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-xl font-bold text-neutral-900">Kelola Order Cetak Foto</h1>
    <p class="mt-1 text-sm text-neutral-500">Order yang masuk dari halaman /cetak-foto beserta foto yang dikirim customer.</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 flex flex-wrap items-center gap-2 text-sm">
        <a href="{{ route('admin.order-cetak-foto.index') }}"
           class="rounded-full border px-3 py-1 {{ ! $statusTerpilih ? 'border-rose-300 bg-rose-50 text-rose-700 font-semibold' : 'border-neutral-200 text-neutral-500 hover:border-rose-300' }}">
            Semua
        </a>
        @foreach (\App\Models\PrintOrder::STATUSES as $value => $label)
            <a href="{{ route('admin.order-cetak-foto.index', ['status' => $value]) }}"
               class="rounded-full border px-3 py-1 {{ $statusTerpilih === $value ? 'border-rose-300 bg-rose-50 text-rose-700 font-semibold' : 'border-neutral-200 text-neutral-500 hover:border-rose-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-neutral-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                    <th class="py-2 px-3">No. Pesanan</th>
                    <th class="py-2 px-3">Waktu</th>
                    <th class="py-2 px-3">Customer</th>
                    <th class="py-2 px-3">Item</th>
                    <th class="py-2 px-3">Estimasi</th>
                    <th class="py-2 px-3">Status</th>
                    <th class="py-2 px-3">Pembayaran</th>
                    <th class="py-2 px-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b border-neutral-100 align-top">
                        <td class="py-2 px-3 font-mono text-xs text-neutral-500 whitespace-nowrap">{{ $order->nomor_pesanan ?? '-' }}</td>
                        <td class="py-2 px-3 text-neutral-500 whitespace-nowrap">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-2 px-3">
                            <div class="font-medium text-neutral-800">{{ $order->nama ?: '-' }}</div>
                            <div class="text-xs text-neutral-500">{{ $order->no_hp ?: '-' }}</div>
                        </td>
                        <td class="py-2 px-3 text-neutral-600">
                            {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}
                        </td>
                        <td class="py-2 px-3 text-neutral-600">
                            {{ $order->is_custom ? 'Hubungi Admin' : 'Rp'.number_format($order->totalPembayaran(), 0, ',', '.') }}
                        </td>
                        <td class="py-2 px-3">
                            <span class="inline-block rounded-full border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-600">
                                {{ \App\Models\PrintOrder::STATUSES[$order->status] ?? $order->status }}
                            </span>
                        </td>
                        <td class="py-2 px-3">
                            @if ($order->is_custom)
                                <span class="text-xs text-amber-600">Custom (via WA)</span>
                            @else
                                <span class="inline-block rounded-full border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-600">
                                    {{ \App\Models\PrintOrder::STATUS_PEMBAYARAN[$order->status_pembayaran] ?? $order->status_pembayaran }}
                                </span>
                            @endif
                        </td>
                        <td class="py-2 px-3">
                            <a href="{{ route('admin.order-cetak-foto.show', $order) }}" class="text-rose-600 font-medium hover:underline">Detail &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 px-3 text-center text-neutral-400">Belum ada order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</main>
@endsection
