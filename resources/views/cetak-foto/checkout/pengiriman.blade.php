@extends('layouts.app')

@section('title', 'Pengiriman - Checkout Cetak Foto Jogja')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="bg-white">
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-xl font-bold text-neutral-900">Checkout</h1>
        <p class="mt-1 text-sm text-neutral-500">Langkah 3 dari 4 — pilih cara ambil/kirim pesanan.</p>

        <div class="mt-6">
            @include('cetak-foto.checkout._stepper', ['stepAktif' => 3])
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.pengiriman.simpan') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-neutral-700">Metode Pengambilan</label>
                <div class="mt-2 flex flex-wrap gap-4 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="metode_ambil" value="ambil_toko" id="opsi-ambil-toko" {{ ($data['metode_ambil'] ?? null) === 'ambil_toko' ? 'checked' : '' }}>
                        Ambil di Toko
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="metode_ambil" value="dikirim" id="opsi-dikirim" {{ ($data['metode_ambil'] ?? null) === 'dikirim' ? 'checked' : '' }}>
                        Dikirim
                    </label>
                </div>
            </div>

            <div id="wrapper-alamat" class="hidden space-y-5">
                <div id="blok-cari-tujuan">
                    <label class="block text-sm font-medium text-neutral-700">Cari Kecamatan/Kota Tujuan</label>
                    <input type="text" id="input-cari-tujuan" autocomplete="off" placeholder="Ketik nama kecamatan/kota (minimal 3 huruf)..."
                           class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                    <div id="hasil-tujuan" class="hidden mt-1 max-h-56 overflow-y-auto rounded-lg border border-neutral-200 bg-white divide-y divide-neutral-100"></div>
                </div>

                <div id="blok-tujuan-terpilih" class="hidden rounded-lg border border-emerald-200 bg-emerald-50 p-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs text-neutral-500">Tujuan Terpilih</p>
                        <p id="tujuan-terpilih-label" class="text-sm font-medium text-neutral-800"></p>
                    </div>
                    <button type="button" id="btn-ubah-tujuan" class="text-xs font-semibold text-rose-600 hover:underline shrink-0">Ubah</button>
                </div>

                <input type="hidden" name="tujuan_id" id="input-tujuan-id" value="{{ old('tujuan_id') }}">
                <input type="hidden" name="tujuan_label" id="input-tujuan-label" value="{{ old('tujuan_label') }}">

                <div id="wrapper-alamat-lengkap" class="hidden">
                    <label class="block text-sm font-medium text-neutral-700">Detail Alamat Lengkap</label>
                    <textarea name="alamat_pengiriman" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, patokan, dll"
                              class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">{{ $data['alamat_pengiriman'] ?? '' }}</textarea>
                </div>

                <div id="wrapper-kurir" class="hidden">
                    <p class="block text-sm font-medium text-neutral-700 mb-2">Pilih Layanan Pengiriman</p>
                    @if ($beratTotal)
                        <p class="mb-2 text-xs text-neutral-500">Total berat paket: <span class="font-medium text-neutral-700">{{ $beratTotal }} gram</span></p>
                    @endif
                    <p id="loading-kurir" class="text-xs text-neutral-400">Memuat opsi pengiriman...</p>
                    <div id="daftar-kurir" class="space-y-2"></div>
                    <input type="hidden" name="kurir_kode" id="input-kurir-kode">
                    <input type="hidden" name="kurir_layanan" id="input-kurir-layanan">
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('checkout.informasi') }}"
                   class="flex-1 text-center rounded-lg border border-neutral-300 px-4 py-3 font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                    Kembali
                </a>
                <button type="submit" id="btn-lanjut-konfirmasi"
                        class="relative z-50 flex-1 rounded-lg bg-rose-600 px-4 py-3 font-semibold text-white hover:bg-rose-700 transition">
                    Lanjut ke Konfirmasi
                </button>
            </div>
        </form>
    </div>
</section>

<script>
    window.CHECKOUT_URLS = {
        cariTujuan: "{{ route('checkout.pengiriman.cari-tujuan') }}",
        opsiOngkir: "{{ route('checkout.pengiriman.opsi-ongkir') }}",
    };
</script>
@endsection
