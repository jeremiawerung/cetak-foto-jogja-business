@extends('layouts.app')

@section('title', 'Sewa Fotografer - Cetak Foto Jogja')

@section('content')
<section class="bg-rose-50 border-b border-rose-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900">Sewa / Panggilan Fotografer</h1>
        <p class="mt-2 text-neutral-600 max-w-2xl">Fotografer datang ke lokasi acara kamu. Pilih tanggal & jam sesi, isi detail acara, lalu booking langsung lewat WhatsApp.</p>
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
    <h2 class="text-lg font-bold text-neutral-900">Paket (Segera Diumumkan)</h2>
    <p class="text-sm text-neutral-500 mt-1">Harga paket masih dalam penyusunan, hubungi admin untuk info lebih lanjut.</p>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        @foreach ($paket as $p)
            @php $gambarPaket = !empty($p['gambar']) && file_exists(public_path($p['gambar'])) ? $p['gambar'] : null; @endphp
            <div class="rounded-2xl border border-neutral-200 bg-white p-5">
                <div class="w-full h-32 rounded-xl overflow-hidden bg-neutral-100 border border-neutral-200 mb-3">
                    @if ($gambarPaket)
                        <img src="{{ asset($gambarPaket) }}" alt="Contoh {{ $p['nama'] }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-neutral-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5m-18 0A2.25 2.25 0 005.25 19.5h13.5A2.25 2.25 0 0021 17.25m-18 0L8.25 11l3.5 3.5 2.25-2.25L21 17.25" />
                                <circle cx="8.25" cy="8.25" r="1.25" fill="currentColor" stroke="none" />
                            </svg>
                        </div>
                    @endif
                </div>
                <p class="font-bold text-neutral-900">{{ $p['nama'] }}</p>
                <ul class="mt-2 text-sm text-neutral-500 space-y-1">
                    <li>Durasi: {{ $p['durasi'] }}</li>
                    <li>Hasil Edit: {{ $p['jumlah_foto_edit'] }}</li>
                </ul>
                <p class="mt-3 text-rose-600 font-semibold">{{ $p['harga'] }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="bg-white border-t border-neutral-200">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
        <h2 class="text-xl font-bold text-neutral-900">Form Booking Sesi Foto</h2>
        <p class="mt-1 text-sm text-neutral-500">Pilih tanggal & jam pada kalender, lalu lengkapi detail acara.</p>

        <div id="calendar"
             class="mt-6 rounded-2xl border border-neutral-200 p-4"
             data-availability-url="{{ route('sewa-fotografer.ketersediaan') }}"
             data-slots="{{ implode(',', $jamSlot) }}">
            <div class="flex items-center justify-between mb-3">
                <button type="button" id="cal-prev" class="h-8 w-8 rounded-lg hover:bg-neutral-100 text-neutral-600">&laquo;</button>
                <div id="cal-title" class="font-semibold text-neutral-800"></div>
                <button type="button" id="cal-next" class="h-8 w-8 rounded-lg hover:bg-neutral-100 text-neutral-600">&raquo;</button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-neutral-400 mb-1">
                <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
            </div>
            <div id="cal-grid" class="grid grid-cols-7 gap-1 text-center text-sm"></div>
            <div class="mt-3 flex items-center gap-3 text-xs text-neutral-400">
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Ada jadwal terisi</span>
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-neutral-300"></span> Penuh / tidak tersedia</span>
            </div>
        </div>

        <form id="booking-form" class="mt-6 space-y-5" data-action="{{ route('sewa-fotografer.booking') }}">
            @csrf
            <input type="hidden" name="tanggal" id="input-tanggal" required>

            <div class="rounded-lg bg-rose-50 border border-rose-100 px-4 py-3 text-sm text-neutral-700">
                Tanggal dipilih: <span id="tanggal-terpilih" class="font-semibold">belum dipilih</span>
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Jam Sesi</label>
                <select name="jam" id="input-jam" required class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">-- Pilih tanggal dulu --</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Jenis Acara</label>
                <select name="jenis_acara" required class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">-- Pilih jenis acara --</option>
                    @foreach ($jenisAcara as $jenis)
                        <option value="{{ $jenis }}">{{ $jenis }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Lokasi Pemotretan</label>
                <input type="text" name="lokasi" required placeholder="Contoh: Malioboro, Jogja" class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Estimasi Jumlah Orang</label>
                    <input type="number" name="estimasi_orang" min="1" class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Paket (opsional)</label>
                    <select name="paket" class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                        <option value="">-- Belum menentukan --</option>
                        @foreach ($paket as $p)
                            <option value="{{ $p['id'] }}">{{ $p['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Catatan Tambahan</label>
                <textarea name="catatan" rows="3" class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500"></textarea>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Nama</label>
                    <input type="text" name="nama" required class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">No HP / WhatsApp</label>
                    <input type="text" name="no_hp" required class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
                </div>
            </div>

            <p id="form-error" class="hidden text-sm text-red-600"></p>

            <button type="submit" id="btn-submit"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-3 font-semibold text-white hover:bg-emerald-600 transition">
                Booking via WhatsApp
            </button>
        </form>
    </div>
</section>
@endsection
