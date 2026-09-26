@extends('layouts.app')

@section('title', 'Konfirmasi Pesanan - Checkout Cetak Foto Jogja')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="bg-white">
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-xl font-bold text-neutral-900">Checkout</h1>
        <p class="mt-1 text-sm text-neutral-500">Langkah 4 dari 4 — cek ulang pesanan sebelum bayar.</p>

        <div class="mt-6">
            @include('cetak-foto.checkout._stepper', ['stepAktif' => 4])
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        @php $totalBayar = $ringkasan['total_harga'] + ($data['biaya_ongkir'] ?? 0); @endphp

        <div class="mt-8 space-y-3">
            @foreach ($items as $item)
                <div class="rounded-xl border border-neutral-200 p-4 text-sm space-y-1 text-neutral-600">
                    <div class="flex justify-between">
                        <span class="font-medium text-neutral-800">{{ $item['kategori_label'] }}</span>
                        @if (!$item['is_custom'])
                            <span class="font-semibold text-neutral-800">Rp{{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                        @else
                            <span class="font-semibold text-amber-600">Hubungi Admin</span>
                        @endif
                    </div>
                    @if ($item['varian_nama'])
                        <div>{{ $item['varian_nama'] }}</div>
                    @endif
                    <div>{{ $item['jumlah'] }} {{ $item['satuan_label'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="mt-3 rounded-xl border border-neutral-200 p-4 text-sm space-y-1 text-neutral-600">
            @if ($ringkasan['total_berat'])
                <div class="flex justify-between"><span>Total Berat</span><span class="font-medium text-neutral-800">{{ $ringkasan['total_berat'] }} gram</span></div>
            @endif

            @if ($ringkasan['is_custom'])
                <div class="flex justify-between pt-1 border-t border-neutral-100 mt-1"><span>Estimasi Harga</span><span class="font-semibold text-rose-600">Hubungi Admin</span></div>
            @else
                <div class="flex justify-between"><span>Subtotal</span><span class="font-medium text-neutral-800">Rp{{ number_format($ringkasan['total_harga'], 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Metode Pengambilan</span><span class="font-medium text-neutral-800">{{ \App\Models\PrintOrder::METODE_AMBIL[$data['metode_ambil']] ?? '-' }}</span></div>
                @if (($data['metode_ambil'] ?? null) === 'dikirim')
                    <div class="flex justify-between"><span>Ongkir ({{ $data['ongkir_label'] }})</span><span class="font-medium text-neutral-800">Rp{{ number_format($data['biaya_ongkir'], 0, ',', '.') }}</span></div>
                @endif
                <div class="flex justify-between pt-1 border-t border-neutral-100 mt-1"><span>Total Pembayaran</span><span class="font-semibold text-rose-600">Rp{{ number_format($totalBayar, 0, ',', '.') }}</span></div>
            @endif
        </div>

        @if ($ringkasan['is_custom'])
            <div class="mt-5 rounded-lg bg-amber-50 border border-amber-100 p-4 text-sm text-amber-700">
                Harga item ini custom, admin akan konfirmasi harga sekaligus metode pengambilan &amp; pembayaran langsung lewat WhatsApp setelah order masuk.
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.konfirmasi.simpan') }}" class="mt-5 space-y-5" enctype="multipart/form-data">
            @csrf

            @unless ($ringkasan['is_custom'])
                <div id="wrapper-metode-bayar">
                    <label class="block text-sm font-medium text-neutral-700">Metode Pembayaran</label>
                    <div class="mt-2 flex flex-wrap gap-4 text-sm">
                        @if (($data['metode_ambil'] ?? null) === 'ambil_toko')
                            <label class="flex items-center gap-2">
                                <input type="radio" name="metode_bayar" value="bayar_toko" id="opsi-bayar-toko">
                                Bayar di Toko
                            </label>
                        @endif
                        <label class="flex items-center gap-2">
                            <input type="radio" name="metode_bayar" value="transfer" id="opsi-transfer">
                            Transfer Bank
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="metode_bayar" value="qris" id="opsi-qris">
                            QRIS
                        </label>
                    </div>

                    <div id="wrapper-transfer" class="hidden mt-3 space-y-3">
                        <div id="info-transfer" class="rounded-lg bg-neutral-50 border border-neutral-200 p-3 text-sm text-neutral-700">
                            Transfer ke <span class="font-semibold">{{ $bank['nama_bank'] }} {{ $bank['no_rekening'] }}</span>
                            a.n. <span class="font-semibold">{{ $bank['atas_nama'] }}</span>
                            sebesar <span class="font-semibold">Rp{{ number_format($totalBayar, 0, ',', '.') }}</span>, lalu upload bukti transfernya.
                        </div>
                        <div id="info-qris" class="hidden rounded-lg bg-neutral-50 border border-neutral-200 p-3 text-sm text-neutral-700 text-center">
                            <p class="mb-2">Scan QRIS berikut untuk bayar <span class="font-semibold">Rp{{ number_format($totalBayar, 0, ',', '.') }}</span>:</p>
                            @if (!empty($qris['gambar']) && file_exists(public_path($qris['gambar'])))
                                <img src="{{ asset($qris['gambar']) }}" alt="QRIS Cetak Foto Jogja" class="mx-auto w-48 h-48 object-contain">
                            @else
                                <div class="mx-auto w-48 h-48 flex items-center justify-center rounded-lg border-2 border-dashed border-neutral-300 text-xs text-neutral-400 p-3">
                                    Gambar QRIS belum diupload admin
                                </div>
                            @endif
                        </div>
                        <div>
                            <label id="label-bukti" class="block text-sm font-medium text-neutral-700">Upload Bukti Transfer</label>
                            <input type="file" name="bukti_transfer" accept="image/png,image/jpeg,image/webp,application/pdf"
                                   class="mt-1 w-full text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:text-rose-600 file:font-semibold hover:file:bg-rose-100">
                            <p class="mt-1 text-xs text-neutral-500">Format JPG/PNG/WEBP/PDF, maks 5MB.</p>
                        </div>
                    </div>
                </div>
            @endunless

            <div class="rounded-lg border border-neutral-200 p-3">
                <label class="flex items-start gap-2 text-sm text-neutral-700">
                    <input type="checkbox" name="syarat_setuju" id="input-syarat" value="1" required class="mt-0.5">
                    <span>
                        Saya sudah membaca dan menyetujui
                        <button type="button" id="btn-lihat-syarat" class="text-rose-600 font-medium hover:underline">Syarat &amp; Ketentuan</button>
                        pemesanan.
                    </span>
                </label>
            </div>

            <div class="flex gap-3">
                @if ($ringkasan['is_custom'])
                    <a href="{{ route('checkout.informasi') }}"
                       class="flex-1 text-center rounded-lg border border-neutral-300 px-4 py-3 font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                        Kembali
                    </a>
                @else
                    <a href="{{ route('checkout.pengiriman') }}"
                       class="flex-1 text-center rounded-lg border border-neutral-300 px-4 py-3 font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                        Kembali
                    </a>
                @endif
                <button type="submit"
                        class="relative z-50 flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-3 font-semibold text-white hover:bg-emerald-600 transition">
                    Order via WhatsApp
                </button>
            </div>
        </form>
    </div>
</section>

{{-- Popup Syarat & Ketentuan --}}
<div id="modal-syarat" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-neutral-900">Syarat & Ketentuan</h3>
        <ul class="mt-3 space-y-2 text-sm text-neutral-600 list-disc pl-5">
            <li>Apabila stok/bahan tidak tersedia, dana akan kami refund 100% dengan konfirmasi lewat WhatsApp.</li>
            <li>Pesanan tidak dapat dibatalkan setelah proses pengerjaan dimulai.</li>
            <li>Estimasi waktu pengambilan/pengiriman dapat berubah tergantung kondisi.</li>
            <li>Pembayaran harus dilakukan sesuai metode yang dipilih dan akan diverifikasi manual oleh admin.</li>
        </ul>
        <button type="button" id="btn-setuju-syarat"
                class="mt-5 w-full rounded-lg bg-rose-600 px-4 py-3 font-semibold text-white hover:bg-rose-700 transition">
            Saya Mengerti &amp; Setuju
        </button>
    </div>
</div>
@endsection
