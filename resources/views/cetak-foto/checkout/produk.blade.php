@extends('layouts.app')

@section('title', 'Pilih Produk - Checkout Cetak Foto Jogja')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="bg-white">
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-xl font-bold text-neutral-900">Checkout</h1>
        <p class="mt-1 text-sm text-neutral-500">Langkah 1 dari 4 — pilih produk yang mau dipesan.</p>

        <div class="mt-6">
            @include('cetak-foto.checkout._stepper', ['stepAktif' => 1])
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.produk.simpan') }}" class="mt-8 space-y-5" enctype="multipart/form-data">
            @csrf

            <div>
                <label class="block text-sm font-medium text-neutral-700">Kategori</label>
                <select name="kategori" id="input-kategori" required
                        class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">-- Pilih kategori --</option>
                    @foreach ($catalog as $kategoriId => $item)
                        <option value="{{ $kategoriId }}" {{ ($data['kategori'] ?? null) === $kategoriId ? 'selected' : '' }}>{{ $item['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div id="wrapper-varian">
                <label class="block text-sm font-medium text-neutral-700">Varian / Ukuran</label>
                <select name="varian" id="input-varian"
                        class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">-- Pilih kategori dulu --</option>
                </select>
                <p id="varian-note" class="mt-1 text-xs text-neutral-500"></p>
            </div>

            <div>
                <label id="label-jumlah" class="block text-sm font-medium text-neutral-700">Jumlah</label>
                <div class="mt-1 flex items-center gap-2">
                    <button type="button" id="btn-kurang" class="h-10 w-10 shrink-0 rounded-lg border border-neutral-300 text-lg font-bold text-neutral-600 hover:bg-neutral-50">&minus;</button>
                    <input type="number" name="jumlah" id="input-jumlah" min="1" value="{{ $data['jumlah'] ?? 1 }}" required
                           class="w-full text-center rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                    <button type="button" id="btn-tambah" class="h-10 w-10 shrink-0 rounded-lg border border-neutral-300 text-lg font-bold text-neutral-600 hover:bg-neutral-50">&plus;</button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Upload Foto</label>
                <input type="file" name="foto[]" id="input-foto" multiple accept="image/png,image/jpeg,image/webp"
                       class="mt-1 w-full text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-rose-600 file:font-semibold hover:file:bg-rose-100">
                <p class="mt-1 text-xs text-neutral-500">Format JPG/PNG/WEBP, maks 10MB per foto, maks 20 foto.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Link Google Drive (opsional)</label>
                <input type="url" name="gdrive_link" value="{{ $data['gdrive_link'] ?? '' }}" placeholder="https://drive.google.com/..."
                       class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                <p class="mt-1 text-xs text-neutral-500">Kalau jumlah foto banyak, upload ke Google Drive (atur akses "siapa saja yang punya link bisa lihat"), lalu tempel link-nya di sini.</p>
            </div>

            <div class="rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center justify-between">
                <span class="text-sm font-medium text-neutral-700">Estimasi Harga</span>
                <span id="estimasi-harga" class="text-xl font-extrabold text-rose-600">Rp0</span>
            </div>

            <button type="submit"
                    class="relative z-50 w-full rounded-lg bg-rose-600 px-4 py-3 font-semibold text-white hover:bg-rose-700 transition">
                Lanjut ke Informasi
            </button>
        </form>
    </div>
</section>

<script>
    window.CATALOG = @json($catalog);
    window.CHECKOUT_DATA = @json($data);
</script>
@endsection
