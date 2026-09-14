@extends('layouts.internal')

@section('title', "Pratinjau Ekspor - Batch #{$batch->id}")

@php
    $fieldLabel = [
        'nama' => 'Nama', 'kelas' => 'Kelas', 'tanggal_lahir' => 'Tgl Lahir', 'tempat_lahir' => 'Tempat Lahir',
        'alamat' => 'Alamat', 'agama' => 'Agama', 'jenis_kelamin' => 'Jenis Kelamin', 'nis' => 'NIS', 'nisn' => 'NISN',
    ];
@endphp

@section('content')
@include('internal.partials.header')

<main class="max-w-6xl mx-auto px-4 sm:px-6 py-10 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">Pratinjau Ekspor — Batch #{{ $batch->id }}</h1>
            <p class="text-xs text-neutral-400 mt-1">{{ count($pairs) }} siswa siap diekspor (hanya yang tercocok dari Excel &amp; GForm).</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('internal.kartu-pelajar.batch.export.download', $batch) }}" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                Unduh CSV
            </a>
            <a href="{{ route('internal.kartu-pelajar.batch.show', $batch) }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Kembali</a>
        </div>
    </div>

    <p class="text-xs text-neutral-400">
        Data Excel diutamakan untuk nilai akhir. Baris dengan latar <span class="bg-amber-50 text-amber-700 px-1 rounded">kuning</span>
        berarti Excel &amp; GForm punya nilai berbeda untuk field itu — nilai Excel yang dipakai, tapi periksa dulu mana yang benar.
    </p>

    @if (empty($pairs))
        <p class="text-sm text-neutral-400">Belum ada siswa yang tercocok dari kedua sumber di batch ini. Selesaikan resolusi dulu di halaman batch.</p>
    @endif

    <div class="space-y-3">
        @foreach ($pairs as $pair)
            <div class="rounded-xl border border-neutral-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-neutral-400">{{ $pair['id_file'] }}</span>
                    @if (! empty($pair['discrepancies']))
                        <span class="inline-block rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                            {{ count($pair['discrepancies']) }} field beda antar sumber
                        </span>
                    @endif
                </div>

                <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2 text-sm">
                    @foreach ($fieldLabel as $field => $label)
                        @php $isDiscrepant = in_array($field, $pair['discrepancies'], true); @endphp
                        <div class="{{ $isDiscrepant ? 'bg-amber-50 rounded-lg px-2 py-1 -mx-2' : '' }}">
                            <p class="text-xs text-neutral-400">{{ $label }}</p>
                            <p class="font-medium text-neutral-800">{{ $pair['merged'][$field] ?? '—' }}</p>
                            @if ($isDiscrepant)
                                <p class="text-xs text-amber-600 mt-0.5">
                                    GForm: {{ $field === 'tanggal_lahir' ? $pair['gform_row']->tanggal_lahir?->format('Y-m-d') : $pair['gform_row']->{$field} }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</main>
@endsection
