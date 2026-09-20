@extends('layouts.app')

@section('title', 'Cek Status Pesanan - Cetak Foto Jogja')

@section('content')
<section class="bg-rose-50 border-b border-rose-100">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900">Cek Status Pesanan</h1>
        <p class="mt-2 text-neutral-600">Masukkan nomor pesanan kamu (contoh: CFJ-20260920-ABC123) untuk melihat status terbaru.</p>
    </div>
</section>

<section class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
    <form method="GET" action="{{ route('cek-pesanan') }}" class="flex gap-2">
        <input type="text" name="nomor" value="{{ $nomor }}" placeholder="CFJ-20260920-ABC123" required
               class="flex-1 rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500 uppercase">
        <button type="submit" class="rounded-lg bg-rose-600 px-5 py-2 font-semibold text-white hover:bg-rose-700 transition">
            Cek
        </button>
    </form>

    @if ($tidakDitemukan)
        <div class="mt-6 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
            Nomor pesanan "{{ $nomor }}" tidak ditemukan. Pastikan nomor pesanan sudah benar.
        </div>
    @endif

    @if ($order)
        <div class="mt-8 rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-xs text-neutral-400">No. Pesanan</p>
                    <p class="font-bold text-neutral-900">{{ $order->nomor_pesanan }}</p>
                </div>
                <p class="text-xs text-neutral-400">{{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3">
                    <p class="text-xs text-neutral-500">Status Pesanan</p>
                    <p class="font-semibold text-neutral-800">{{ \App\Models\PrintOrder::STATUSES[$order->status] ?? $order->status }}</p>
                </div>
                <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3">
                    <p class="text-xs text-neutral-500">Status Pembayaran</p>
                    <p class="font-semibold text-neutral-800">
                        {{ $order->is_custom ? 'Menunggu Konfirmasi Admin' : (\App\Models\PrintOrder::STATUS_PEMBAYARAN[$order->status_pembayaran] ?? $order->status_pembayaran) }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                @foreach ($order->items as $item)
                    <div class="rounded-lg bg-neutral-50 border border-neutral-200 p-3 text-sm">
                        <div class="flex justify-between">
                            <span class="font-medium text-neutral-800">{{ $item->kategori_label }}{{ $item->varian ? ' - '.$item->varian : '' }}</span>
                            @if (!$item->is_custom)
                                <span class="font-semibold text-neutral-800">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            @endif
                        </div>
                        <div class="text-neutral-500">{{ $item->jumlah }} pcs</div>
                    </div>
                @endforeach
            </div>

            @if (! $order->is_custom)
                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-neutral-500">Metode Pengambilan</dt>
                        <dd class="font-medium text-neutral-800">{{ \App\Models\PrintOrder::METODE_AMBIL[$order->metode_ambil] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500">Total Pembayaran</dt>
                        <dd class="font-semibold text-rose-600">Rp{{ number_format($order->totalPembayaran(), 0, ',', '.') }}</dd>
                    </div>
                </dl>

                @if ($order->metode_ambil === 'dikirim' && $order->resi)
                    <div class="rounded-lg bg-emerald-50 border border-emerald-100 p-3 text-sm">
                        <p class="text-emerald-700">Paket sudah dikirim ({{ $order->ongkir_label }})</p>
                        <p class="font-mono font-semibold text-emerald-800 mt-1">No. Resi: {{ $order->resi }}</p>
                        <p class="text-xs text-emerald-600 mt-1">Lacak status pengiriman di situs/aplikasi kurir terkait pakai nomor resi ini.</p>
                    </div>
                @endif
            @endif

            @if ($order->is_custom)
                <p class="text-xs text-amber-600 bg-amber-50 border border-amber-100 rounded-lg p-3">
                    Item pesanan ini custom, harga &amp; detail lanjutan dikonfirmasi admin lewat WhatsApp.
                </p>
            @endif

            <a href="https://wa.me/{{ config('services.whatsapp.number') }}?text={{ rawurlencode('Halo, saya mau tanya soal pesanan '.$order->nomor_pesanan) }}"
               target="_blank" rel="noopener"
               class="block text-center rounded-lg border border-neutral-300 px-4 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                Ada Pertanyaan? Chat Admin
            </a>
        </div>
    @endif
</section>
@endsection
