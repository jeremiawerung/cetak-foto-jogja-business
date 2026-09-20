@extends('layouts.internal')

@section('title', "Order {$printOrder->nomor_pesanan} - Internal")

@section('content')
@include('admin.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">Order {{ $printOrder->nomor_pesanan ?? '#'.$printOrder->id }}</h1>
            <p class="text-xs text-neutral-400 mt-1">Masuk {{ $printOrder->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('admin.order-cetak-foto.index') }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Kembali</a>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900 mb-3">Data Pemesan</h2>
        <dl class="grid gap-3 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-neutral-500">Nama</dt>
                <dd class="font-medium text-neutral-800">{{ $printOrder->nama ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-neutral-500">No HP</dt>
                <dd class="font-medium text-neutral-800">{{ $printOrder->no_hp ?: '-' }}</dd>
            </div>
            @if ($printOrder->catatan)
                <div class="sm:col-span-2">
                    <dt class="text-neutral-500">Catatan</dt>
                    <dd class="font-medium text-neutral-800 whitespace-pre-line">{{ $printOrder->catatan }}</dd>
                </div>
            @endif
        </dl>
    </section>

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900 mb-3">Item Pesanan ({{ $printOrder->items->count() }})</h2>
        <div class="space-y-3">
            @foreach ($printOrder->items as $item)
                <div class="rounded-lg border border-neutral-200 p-3 text-sm">
                    <div class="flex justify-between">
                        <span class="font-medium text-neutral-800">{{ $item->kategori_label }}</span>
                        <span class="font-semibold {{ $item->is_custom ? 'text-amber-600' : 'text-neutral-800' }}">
                            {{ $item->is_custom ? 'Hubungi Admin' : 'Rp'.number_format($item->subtotal, 0, ',', '.') }}
                        </span>
                    </div>
                    @if ($item->varian)
                        <div class="text-neutral-500">{{ $item->varian }}</div>
                    @endif
                    <div class="text-neutral-500">
                        {{ $item->jumlah }} pcs
                        @if ($item->berat_subtotal)
                            &middot; {{ $item->berat_subtotal }} gram
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <dl class="grid gap-3 sm:grid-cols-2 text-sm mt-4 pt-4 border-t border-neutral-100">
            @if ($printOrder->berat_total)
                <div>
                    <dt class="text-neutral-500">Total Berat</dt>
                    <dd class="font-medium text-neutral-800">{{ $printOrder->berat_total }} gram</dd>
                </div>
            @endif
            <div>
                <dt class="text-neutral-500">Estimasi Harga</dt>
                <dd class="font-medium text-neutral-800">Rp{{ number_format($printOrder->estimasi_harga, 0, ',', '.') }}</dd>
            </div>
            @if ($printOrder->biaya_ongkir)
                <div>
                    <dt class="text-neutral-500">Biaya Ongkir ({{ $printOrder->ongkir_label }})</dt>
                    <dd class="font-medium text-neutral-800">Rp{{ number_format($printOrder->biaya_ongkir, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500">Total Pembayaran</dt>
                    <dd class="font-semibold text-rose-600">Rp{{ number_format($printOrder->totalPembayaran(), 0, ',', '.') }}</dd>
                </div>
            @endif
        </dl>
    </section>

    @if ($printOrder->is_custom)
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            Item order ini <strong>custom</strong> (harga ditentukan admin). Pengambilan &amp; pembayaran tidak diproses online, diatur langsung lewat WhatsApp.
        </section>
    @else
        <section class="rounded-xl border border-neutral-200 bg-white p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-sm font-bold text-neutral-900">Pengambilan & Pembayaran</h2>
                @if ($printOrder->metode_ambil === 'dikirim')
                    <a href="{{ route('admin.order-cetak-foto.label', $printOrder) }}" target="_blank"
                       class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                        Cetak Label Pengiriman
                    </a>
                @endif
            </div>
            <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-neutral-500">Metode Pengambilan</dt>
                    <dd class="font-medium text-neutral-800">{{ \App\Models\PrintOrder::METODE_AMBIL[$printOrder->metode_ambil] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-neutral-500">Metode Pembayaran</dt>
                    <dd class="font-medium text-neutral-800">{{ \App\Models\PrintOrder::METODE_BAYAR[$printOrder->metode_bayar] ?? '-' }}</dd>
                </div>
                @if ($printOrder->metode_ambil === 'dikirim')
                    <div class="sm:col-span-2">
                        <dt class="text-neutral-500">Alamat Pengiriman</dt>
                        <dd class="font-medium text-neutral-800 whitespace-pre-line">{{ $printOrder->alamat_pengiriman }}</dd>
                    </div>
                @endif
            </dl>

            @if (in_array($printOrder->metode_bayar, ['transfer', 'qris'], true))
                <div class="mt-4 pt-4 border-t border-neutral-100">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="text-sm font-medium text-neutral-700">Bukti {{ $printOrder->metode_bayar === 'qris' ? 'Pembayaran QRIS' : 'Transfer' }}</span>
                        <span class="inline-block rounded-full border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-600">
                            {{ \App\Models\PrintOrder::STATUS_PEMBAYARAN[$printOrder->status_pembayaran] ?? $printOrder->status_pembayaran }}
                        </span>
                    </div>

                    @if ($printOrder->bukti_transfer)
                        <a href="{{ route('admin.order-cetak-foto.bukti-transfer', $printOrder) }}" target="_blank" rel="noopener"
                           class="inline-block text-sm text-rose-600 font-medium hover:underline">Lihat / Unduh Bukti &rarr;</a>
                    @else
                        <p class="text-sm text-neutral-400">Belum ada bukti diunggah.</p>
                    @endif

                    <form method="POST" action="{{ route('admin.order-cetak-foto.pembayaran', $printOrder) }}" class="mt-3 flex flex-wrap items-center gap-2">
                        @csrf
                        <select name="status_pembayaran" class="rounded-lg border-neutral-300 text-sm">
                            @foreach (\App\Models\PrintOrder::STATUS_PEMBAYARAN as $value => $label)
                                <option value="{{ $value }}" {{ $printOrder->status_pembayaran === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                            Simpan Status Pembayaran
                        </button>
                    </form>
                </div>
            @elseif ($printOrder->metode_bayar === 'bayar_toko')
                <div class="mt-4 pt-4 border-t border-neutral-100">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="text-sm font-medium text-neutral-700">Status Pembayaran</span>
                        <span class="inline-block rounded-full border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-600">
                            {{ \App\Models\PrintOrder::STATUS_PEMBAYARAN[$printOrder->status_pembayaran] ?? $printOrder->status_pembayaran }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('admin.order-cetak-foto.pembayaran', $printOrder) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <select name="status_pembayaran" class="rounded-lg border-neutral-300 text-sm">
                            @foreach (\App\Models\PrintOrder::STATUS_PEMBAYARAN as $value => $label)
                                <option value="{{ $value }}" {{ $printOrder->status_pembayaran === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                            Simpan Status Pembayaran
                        </button>
                    </form>
                </div>
            @endif
        </section>
    @endif

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900 mb-3">Status Order</h2>
        <form method="POST" action="{{ route('admin.order-cetak-foto.status', $printOrder) }}" class="flex flex-wrap items-center gap-2">
            @csrf
            <select name="status" class="rounded-lg border-neutral-300 text-sm">
                @foreach (\App\Models\PrintOrder::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ $printOrder->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            @if ($printOrder->metode_ambil === 'dikirim')
                <input type="text" name="resi" value="{{ $printOrder->resi }}" placeholder="Nomor resi (opsional)"
                       class="rounded-lg border-neutral-300 text-sm">
            @endif

            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Simpan Status
            </button>
        </form>

        @if ($printOrder->metode_ambil === 'dikirim' && $printOrder->resi)
            <p class="mt-2 text-xs text-neutral-500">
                Nomor resi tersimpan: <span class="font-mono font-medium text-neutral-700">{{ $printOrder->resi }}</span>
                — cek status pengiriman langsung di situs/aplikasi kurir terkait ({{ $printOrder->ongkir_label }}).
            </p>
        @endif
    </section>

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <div class="flex items-center justify-between gap-3 mb-3">
            <h2 class="text-sm font-bold text-neutral-900">File dari Customer</h2>
            @if ($printOrder->items->contains(fn ($i) => ! empty($i->file_paths)))
                <a href="{{ route('admin.order-cetak-foto.download-all', $printOrder) }}"
                   class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                    Unduh Semua (ZIP)
                </a>
            @endif
        </div>

        @foreach ($printOrder->items as $item)
            <div class="mb-4 last:mb-0">
                <p class="text-xs font-semibold text-neutral-600 mb-2">{{ $item->kategori_label }}{{ $item->varian ? ' - '.$item->varian : '' }}</p>

                @if ($item->gdrive_link)
                    <div class="mb-2 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-sm">
                        <span class="text-emerald-700 font-medium">Link Google Drive:</span>
                        <a href="{{ $item->gdrive_link }}" target="_blank" rel="noopener" class="ml-1 text-emerald-700 underline break-all">
                            {{ $item->gdrive_link }}
                        </a>
                    </div>
                @endif

                @if (empty($item->file_paths))
                    <p class="text-sm text-neutral-400">Tidak ada file diunggah langsung.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach ($item->file_paths as $i => $path)
                            <a href="{{ route('admin.order-cetak-foto.download-file', [$printOrder, $item, $i]) }}"
                               class="block rounded-lg border border-neutral-200 p-2 text-center hover:border-rose-300">
                                <div class="aspect-square rounded bg-neutral-100 flex items-center justify-center text-neutral-300 overflow-hidden mb-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5m-18 0A2.25 2.25 0 005.25 19.5h13.5A2.25 2.25 0 0021 17.25m-18 0L8.25 11l3.5 3.5 2.25-2.25L21 17.25" />
                                        <circle cx="8.25" cy="8.25" r="1.25" fill="currentColor" stroke="none" />
                                    </svg>
                                </div>
                                <span class="text-xs font-medium text-rose-600">Unduh Foto {{ $i + 1 }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </section>
</main>
@endsection
