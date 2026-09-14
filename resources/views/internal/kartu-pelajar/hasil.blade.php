@extends('layouts.internal')

@section('title', 'Hasil Audit Kartu Pelajar - Internal')

@php
    $statusBadge = [
        'clean' => ['label' => 'Bersih', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'normalized_with_warning' => ['label' => 'Perlu Diperiksa', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        'needs_manual_review' => ['label' => 'Perlu Rekonstruksi Manual', 'class' => 'bg-red-50 text-red-700 border-red-200'],
        'duplicate_collapsed' => ['label' => 'Duplikat (Digabung)', 'class' => 'bg-neutral-100 text-neutral-500 border-neutral-200'],
    ];

    $sections = [
        ['label' => 'Excel Master', 'sumber' => 'excel', 'results' => $excelResults, 'summary' => $summaryExcel, 'opinions' => $excelOpinions],
        ['label' => 'Google Form (PDF)', 'sumber' => 'gform', 'results' => $gformResults, 'summary' => $summaryGform, 'opinions' => $gformOpinions],
    ];

    $matchingBadge = [
        'unprocessed' => ['label' => 'Belum Diproses', 'class' => 'bg-neutral-100 text-neutral-500 border-neutral-200'],
        'auto_matched' => ['label' => 'Tercocok Otomatis', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'fuzzy_candidate' => ['label' => 'Kandidat (Perlu Konfirmasi)', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
        'conflict' => ['label' => 'Konflik', 'class' => 'bg-red-50 text-red-700 border-red-200'],
        'manual_resolved' => ['label' => 'Diselesaikan Manual', 'class' => 'bg-violet-50 text-violet-700 border-violet-200'],
    ];

    $matchingBySourceRow = collect($matchingRows ?? [])->keyBy(fn ($r) => "{$r->source}:{$r->source_row_number}");
@endphp

@section('content')
@include('internal.partials.header')

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-10">
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-bold text-neutral-900">Hasil Audit</h1>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('internal.kartu-pelajar.verifikasi-ai') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-50">
                        Verifikasi dengan AI
                    </button>
                </form>
                <a href="{{ route('internal.kartu-pelajar.index') }}" class="text-sm font-medium text-rose-600 hover:underline">&larr; Upload ulang</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        @if (! empty($reUploadWarnings))
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">
                <strong>Kemungkinan re-upload:</strong>
                @foreach ($reUploadWarnings as $warning)
                    Batch ini {{ $warning['overlap_percent'] }}% mirip dengan
                    <a href="{{ route('internal.kartu-pelajar.batch.show', $warning['batch']) }}" class="underline font-medium">Batch #{{ $warning['batch']->id }}</a>
                    yang sudah ada.
                @endforeach
                Periksa dulu apakah ini memang data baru, atau tidak sengaja upload ulang.
            </div>
        @endif

        <p class="text-xs text-neutral-400">
            Verifikasi AI adalah opini tambahan (cek logika data, bukan format) untuk baris berstatus Bersih/Perlu Diperiksa — hasilnya saran, bukan keputusan final.
        </p>

        @if ($batch)
            <p class="text-xs text-neutral-400">
                Tersimpan sebagai <span class="font-medium text-neutral-600">Batch #{{ $batch->id }}</span>
                ({{ $batch->created_at->translatedFormat('d M Y, H:i') }}) —
                <a href="{{ route('internal.kartu-pelajar.batch.show', $batch) }}" class="text-rose-600 font-medium hover:underline">buka halaman resolusi konflik &rarr;</a>
            </p>
        @endif

        @if (! empty($matchingSummary))
            <div>
                <p class="text-xs font-medium text-neutral-500 mb-1.5">Hasil Pencocokan Excel &harr; Google Form</p>
                <div class="flex flex-wrap gap-2 text-xs">
                    @foreach ($matchingBadge as $key => $badge)
                        <span class="rounded-full border px-3 py-1 font-medium {{ $badge['class'] }}">
                            {{ $badge['label'] }}: {{ $matchingSummary[$key] ?? 0 }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @foreach ($sections as $section)
        <section>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-neutral-900">{{ $section['label'] }}</h2>
                <a href="{{ route('internal.kartu-pelajar.audit.unduh', $section['sumber']) }}"
                   class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                    Unduh CSV
                </a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 font-medium text-neutral-600">Total: {{ $section['summary']['total'] }}</span>
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 font-medium text-emerald-700">Bersih: {{ $section['summary']['clean'] }}</span>
                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 font-medium text-amber-700">Perlu Diperiksa: {{ $section['summary']['normalized_with_warning'] }}</span>
                <span class="rounded-full border border-red-200 bg-red-50 px-3 py-1 font-medium text-red-700">Perlu Rekonstruksi: {{ $section['summary']['needs_manual_review'] }}</span>
                <span class="rounded-full border border-neutral-200 bg-neutral-100 px-3 py-1 font-medium text-neutral-500">Duplikat Digabung: {{ $section['summary']['duplicate_collapsed'] }}</span>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-neutral-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-neutral-500 bg-neutral-50 border-b border-neutral-200">
                            <th class="py-2 px-3">Baris</th>
                            <th class="py-2 px-3">Status</th>
                            <th class="py-2 px-3">Nama</th>
                            <th class="py-2 px-3">Kelas</th>
                            <th class="py-2 px-3">Tgl Lahir</th>
                            <th class="py-2 px-3">NISN</th>
                            <th class="py-2 px-3">Catatan</th>
                            <th class="py-2 px-3">Opini AI</th>
                            <th class="py-2 px-3">Pencocokan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($section['results'] as $row)
                            @php
                                $opinion = $section['opinions'][$row->rowNumber] ?? null;
                                $matchingRow = $matchingBySourceRow->get("{$section['sumber']}:{$row->rowNumber}");
                            @endphp
                            <tr class="border-b border-neutral-100 align-top">
                                <td class="py-2 px-3 text-neutral-500">#{{ $row->rowNumber }}</td>
                                <td class="py-2 px-3">
                                    <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium {{ $statusBadge[$row->status]['class'] ?? '' }}">
                                        {{ $statusBadge[$row->status]['label'] ?? $row->status }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 font-medium text-neutral-800">{{ $row->fieldValue('nama') ?? '—' }}</td>
                                <td class="py-2 px-3 text-neutral-600">{{ $row->fieldValue('kelas') ?? '—' }}</td>
                                <td class="py-2 px-3 text-neutral-600">{{ $row->fieldValue('tanggal_lahir') ?? '—' }}</td>
                                <td class="py-2 px-3 text-neutral-600">{{ $row->fieldValue('nisn') ?? '—' }}</td>
                                <td class="py-2 px-3 text-xs text-neutral-500 max-w-xs">{{ implode(' | ', $row->reasons) ?: '—' }}</td>
                                <td class="py-2 px-3 text-xs max-w-xs">
                                    @if ($opinion === null)
                                        <span class="text-neutral-300">—</span>
                                    @elseif ($opinion['flagged'])
                                        <span class="text-amber-700">⚠ {{ $opinion['note'] ?? 'Perlu dicek' }}</span>
                                    @else
                                        <span class="text-emerald-600">✓ OK</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-xs">
                                    @if ($matchingRow)
                                        <span class="inline-block rounded-full border px-2 py-0.5 font-medium {{ $matchingBadge[$matchingRow->matching_status]['class'] ?? '' }}">
                                            {{ $matchingBadge[$matchingRow->matching_status]['label'] ?? $matchingRow->matching_status }}
                                        </span>
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-4 px-3 text-center text-neutral-400">Tidak ada baris data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</main>
@endsection
