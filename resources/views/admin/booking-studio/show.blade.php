@extends('layouts.internal')

@section('title', "Booking #{$photographerBooking->id} - Internal")

@section('content')
@include('admin.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">Booking #{{ $photographerBooking->id }}</h1>
            <p class="text-xs text-neutral-400 mt-1">Dibuat {{ $photographerBooking->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('admin.booking-studio.index') }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Kembali</a>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900 mb-3">Detail Booking</h2>
        <dl class="grid gap-3 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-neutral-500">Nama</dt>
                <dd class="font-medium text-neutral-800">{{ $photographerBooking->nama }}</dd>
            </div>
            <div>
                <dt class="text-neutral-500">No HP</dt>
                <dd class="font-medium text-neutral-800">{{ $photographerBooking->no_hp }}</dd>
            </div>
            <div>
                <dt class="text-neutral-500">Tanggal</dt>
                <dd class="font-medium text-neutral-800">{{ $photographerBooking->tanggal->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-neutral-500">Jam</dt>
                <dd class="font-medium text-neutral-800">{{ substr($photographerBooking->jam, 0, 5) }}</dd>
            </div>
            <div>
                <dt class="text-neutral-500">Lokasi</dt>
                <dd class="font-medium text-neutral-800">{{ $photographerBooking->lokasi }}</dd>
            </div>
            <div>
                <dt class="text-neutral-500">Estimasi Orang</dt>
                <dd class="font-medium text-neutral-800">{{ $photographerBooking->estimasi_orang ?: '-' }}</dd>
            </div>
            @if ($photographerBooking->catatan)
                <div class="sm:col-span-2">
                    <dt class="text-neutral-500">Catatan</dt>
                    <dd class="font-medium text-neutral-800 whitespace-pre-line">{{ $photographerBooking->catatan }}</dd>
                </div>
            @endif
        </dl>
    </section>

    <section class="rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-sm font-bold text-neutral-900 mb-3">Status Booking</h2>
        <form method="POST" action="{{ route('admin.booking-studio.status', $photographerBooking) }}" class="flex flex-wrap items-center gap-2">
            @csrf
            <select name="status" class="rounded-lg border-neutral-300 text-sm">
                @foreach (\App\Models\PhotographerBooking::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ $photographerBooking->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Simpan Status
            </button>
        </form>
    </section>
</main>
@endsection
