@extends('layouts.app')

@section('title', 'Sewa Fotografer - Cetak Foto Jogja')

@section('content')
<section class="bg-rose-50 border-b border-rose-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900">Sewa / Panggilan Fotografer</h1>
        <p class="mt-2 text-neutral-600 max-w-2xl">Fotografer datang ke lokasi acara kamu. Pilih tanggal & jam sesi, isi detail acara, lalu booking langsung lewat WhatsApp.</p>
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
                <label class="block text-sm font-medium text-neutral-700">Lokasi Pemotretan</label>
                <input type="text" name="lokasi" required placeholder="Contoh: Malioboro, Jogja" class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700">Estimasi Jumlah Orang</label>
                <input type="number" name="estimasi_orang" min="1" class="mt-1 w-full rounded-lg border-neutral-300 focus:border-rose-500 focus:ring-rose-500">
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
