@extends('layouts.internal')

@section('title', "Batch #{$batch->id} - Internal")

@php
    $matchingBadge = [
        'unprocessed' => ['label' => 'Belum Diproses', 'class' => 'bg-neutral-100 text-neutral-500 border-neutral-200'],
        'auto_matched' => ['label' => 'Tercocok Otomatis', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'fuzzy_candidate' => ['label' => 'Kandidat (Perlu Konfirmasi)', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
        'conflict' => ['label' => 'Konflik', 'class' => 'bg-red-50 text-red-700 border-red-200'],
        'manual_resolved' => ['label' => 'Diselesaikan Manual', 'class' => 'bg-violet-50 text-violet-700 border-violet-200'],
    ];

    $sourceLabel = ['excel' => 'Excel', 'gform' => 'GForm'];
@endphp

@section('content')
@include('internal.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">Batch #{{ $batch->id }}</h1>
            <p class="text-xs text-neutral-400 mt-1">{{ $batch->created_at->translatedFormat('d M Y, H:i') }} — {{ $batch->sumber_file_excel }} &amp; {{ $batch->sumber_file_gform }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('internal.kartu-pelajar.batch.visual-qa', $batch) }}" class="rounded-lg border border-neutral-300 px-3 py-1.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-50">
                Visual QA
            </a>
            <a href="{{ route('internal.kartu-pelajar.batch.export.preview', $batch) }}" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                Pratinjau Ekspor
            </a>
            <a href="{{ route('internal.kartu-pelajar.batch.index') }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Daftar Batch</a>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
            {{ $errors->first('resolusi') }}
        </div>
    @endif

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Perlu Perhatian ({{ $needsAttention->count() }})</h2>

        @if ($needsAttention->isEmpty())
            <p class="mt-3 text-sm text-neutral-400">Semua baris sudah tercocok/diselesaikan. Tidak ada yang perlu ditinjau.</p>
        @else
            <p class="mt-1 text-xs text-neutral-400">
                Pilih aksi untuk sebanyak apa pun baris di bawah, lalu klik <strong>Proses Semua</strong> sekali di akhir — tidak perlu submit satu-satu.
            </p>

            <form method="POST" action="{{ route('internal.kartu-pelajar.batch.rows.bulk-resolve', $batch) }}" class="mt-3">
                @csrf

                <div class="space-y-3">
                    @foreach ($needsAttention as $row)
                        <div class="rounded-xl border border-neutral-200 bg-white p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium {{ $matchingBadge[$row->matching_status]['class'] }}">
                                    {{ $matchingBadge[$row->matching_status]['label'] }}
                                </span>
                                <span class="text-xs text-neutral-400">{{ $sourceLabel[$row->source] }} #{{ $row->source_row_number }}</span>
                            </div>

                            <div class="mt-2 text-sm">
                                <p class="font-medium text-neutral-800">{{ $row->nama ?? '—' }}</p>
                                <p class="text-neutral-500 text-xs mt-0.5">
                                    Kelas: {{ $row->kelas ?? '—' }} &middot;
                                    Tgl Lahir: {{ $row->tanggal_lahir?->format('Y-m-d') ?? '—' }} &middot;
                                    NISN: {{ $row->nisn ?? '—' }}
                                </p>
                            </div>

                            @if ($row->matchedRow)
                                <div class="mt-3 rounded-lg bg-neutral-50 border border-neutral-200 p-3">
                                    <p class="text-xs text-neutral-400 mb-1">Kandidat pasangan — {{ $sourceLabel[$row->matchedRow->source] }} #{{ $row->matchedRow->source_row_number }}:</p>
                                    <p class="text-sm font-medium text-neutral-800">{{ $row->matchedRow->nama ?? '—' }}</p>
                                    <p class="text-xs text-neutral-500 mt-0.5">
                                        Kelas: {{ $row->matchedRow->kelas ?? '—' }} &middot;
                                        Tgl Lahir: {{ $row->matchedRow->tanggal_lahir?->format('Y-m-d') ?? '—' }} &middot;
                                        NISN: {{ $row->matchedRow->nisn ?? '—' }}
                                    </p>
                                </div>

                                <select name="action[{{ $row->id }}]" class="mt-3 w-full rounded-lg border-neutral-300 text-xs focus:border-rose-500 focus:ring-rose-500">
                                    <option value="">-- Lewati baris ini --</option>
                                    <option value="confirm">Konfirmasi Cocok</option>
                                    <option value="reject">Tolak</option>
                                </select>
                            @else
                                @php $candidates = $linkableBySource[$row->source === 'excel' ? 'gform' : 'excel']; @endphp
                                <select name="action[{{ $row->id }}]" class="mt-3 w-full rounded-lg border-neutral-300 text-xs focus:border-rose-500 focus:ring-rose-500">
                                    <option value="">-- Lewati baris ini --</option>
                                    <option value="no_pair">Tandai Tanpa Pasangan</option>
                                    @foreach ($candidates as $candidate)
                                        <option value="link:{{ $candidate->id }}">
                                            Hubungkan ke {{ $sourceLabel[$candidate->source] }} #{{ $candidate->source_row_number }} — {{ $candidate->nama ?? '(nama kosong)' }} ({{ $candidate->kelas ?? '?' }}, {{ $candidate->tanggal_lahir?->format('Y-m-d') ?? '?' }})
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="mt-4 w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                    Proses Semua
                </button>
            </form>
        @endif
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Sudah Selesai ({{ $resolved->count() }})</h2>

        <div class="mt-3 overflow-x-auto rounded-xl border border-neutral-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                        <th class="py-2 px-3">Sumber</th>
                        <th class="py-2 px-3">Nama</th>
                        <th class="py-2 px-3">Status</th>
                        <th class="py-2 px-3">Via</th>
                        <th class="py-2 px-3">ID File</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resolved as $row)
                        <tr class="border-b border-neutral-100">
                            <td class="py-2 px-3 text-neutral-500 text-xs">{{ $sourceLabel[$row->source] }} #{{ $row->source_row_number }}</td>
                            <td class="py-2 px-3 font-medium text-neutral-800">{{ $row->nama ?? '—' }}</td>
                            <td class="py-2 px-3">
                                <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium {{ $matchingBadge[$row->matching_status]['class'] }}">
                                    {{ $matchingBadge[$row->matching_status]['label'] }}
                                </span>
                            </td>
                            <td class="py-2 px-3 text-neutral-500 text-xs">{{ $row->matched_via ?? '—' }}</td>
                            <td class="py-2 px-3 text-neutral-500 text-xs">{{ $row->idFile() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 px-3 text-center text-neutral-400">Belum ada yang selesai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-bold text-neutral-900">Log Aktivitas</h2>
        <div class="mt-3 space-y-2">
            @forelse ($activityLogs as $log)
                <div class="flex items-start gap-3 text-sm">
                    <span class="text-xs text-neutral-400 shrink-0 w-32">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                    <div>
                        <p class="text-neutral-700">{{ $log->description }}</p>
                        <p class="text-xs text-neutral-400">{{ $log->user->name ?? 'Sistem' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-neutral-400">Belum ada aktivitas tercatat.</p>
            @endforelse
        </div>
    </section>
</main>
@endsection
