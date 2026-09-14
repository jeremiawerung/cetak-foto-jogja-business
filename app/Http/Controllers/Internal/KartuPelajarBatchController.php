<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\KartuPelajarBatch;
use App\Models\KartuPelajarRow;
use App\Services\KartuPelajar\ActivityLogger;
use App\Services\KartuPelajar\BatchResolver;
use App\Services\KartuPelajar\ExportBuilder;
use App\Services\KartuPelajar\VisualQaChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class KartuPelajarBatchController extends Controller
{
    public function index(): View
    {
        $batches = KartuPelajarBatch::withCount([
            'rows',
            'rows as belum_diproses_count' => fn ($q) => $q->where('matching_status', 'unprocessed'),
            'rows as perlu_perhatian_count' => fn ($q) => $q->whereIn('matching_status', ['conflict', 'fuzzy_candidate']),
        ])->latest('id')->paginate(20);

        return view('internal.kartu-pelajar.batch.index', compact('batches'));
    }

    public function show(KartuPelajarBatch $batch): View
    {
        $rows = $batch->rows()->with('matchedRow')->orderBy('source')->orderBy('source_row_number')->get();

        $needsAttention = $rows->whereIn('matching_status', ['conflict', 'fuzzy_candidate', 'unprocessed'])
            ->sortBy([['source', 'asc'], ['source_row_number', 'asc']])
            ->values();

        $resolved = $rows->whereIn('matching_status', ['auto_matched', 'manual_resolved']);

        $unresolvedStatuses = ['unprocessed', 'conflict', 'fuzzy_candidate'];

        $linkableBySource = [
            'excel' => $rows->where('source', 'excel')->whereIn('matching_status', $unresolvedStatuses)->values(),
            'gform' => $rows->where('source', 'gform')->whereIn('matching_status', $unresolvedStatuses)->values(),
        ];

        return view('internal.kartu-pelajar.batch.show', [
            'batch' => $batch,
            'needsAttention' => $needsAttention,
            'resolved' => $resolved,
            'linkableBySource' => $linkableBySource,
            'activityLogs' => $batch->activityLogs()->with('user')->limit(30)->get(),
        ]);
    }

    public function confirm(KartuPelajarBatch $batch, KartuPelajarRow $row, BatchResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertRowBelongsToBatch($batch, $row);

        try {
            $resolver->confirm($row);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['resolusi' => $e->getMessage()]);
        }

        $logger->log($batch, 'row_confirmed', "Konfirmasi pencocokan: {$row->source} #{$row->source_row_number} ({$row->nama})");

        return back()->with('status', 'Pencocokan dikonfirmasi.');
    }

    public function reject(KartuPelajarBatch $batch, KartuPelajarRow $row, BatchResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertRowBelongsToBatch($batch, $row);

        $resolver->reject($row);

        $logger->log($batch, 'row_rejected', "Tolak saran pencocokan: {$row->source} #{$row->source_row_number} ({$row->nama})");

        return back()->with('status', 'Saran pencocokan ditolak, baris dikembalikan ke status belum diproses.');
    }

    public function link(Request $request, KartuPelajarBatch $batch, KartuPelajarRow $row, BatchResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertRowBelongsToBatch($batch, $row);

        $validated = $request->validate([
            'target_row_id' => ['required', 'integer'],
        ]);

        $target = KartuPelajarRow::where('batch_id', $batch->id)->find($validated['target_row_id']);

        if ($target === null) {
            return back()->withErrors(['resolusi' => 'Baris tujuan tidak ditemukan di batch ini.']);
        }

        try {
            $resolver->linkManually($row, $target);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['resolusi' => $e->getMessage()]);
        }

        $logger->log($batch, 'row_linked', "Hubungkan manual: {$row->source} #{$row->source_row_number} ({$row->nama}) <-> {$target->source} #{$target->source_row_number} ({$target->nama})");

        return back()->with('status', 'Baris berhasil dihubungkan manual.');
    }

    public function noPair(KartuPelajarBatch $batch, KartuPelajarRow $row, BatchResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $this->assertRowBelongsToBatch($batch, $row);

        $resolver->markNoCounterpart($row);

        $logger->log($batch, 'row_no_pair', "Tandai tanpa pasangan: {$row->source} #{$row->source_row_number} ({$row->nama})");

        return back()->with('status', 'Baris ditandai tidak punya pasangan.');
    }

    /**
     * Proses SEMUA baris di section "Perlu Perhatian" dalam 1 submit — setiap baris punya 1
     * dropdown aksi (lewati/konfirmasi/tolak/hubungkan/tanpa-pasangan), admin isi sebanyak yang
     * mau lalu klik 1 tombol di akhir. Ini supaya tidak perlu klik submit terpisah per baris
     * (yang bikin reload halaman & mereset pilihan di baris lain yang belum disubmit).
     */
    public function bulkResolve(Request $request, KartuPelajarBatch $batch, BatchResolver $resolver, ActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['nullable', 'array'],
            'action.*' => ['nullable', 'string'],
        ]);

        $actions = array_filter($validated['action'] ?? [], fn ($v) => $v !== null && $v !== '');

        $counts = ['confirm' => 0, 'reject' => 0, 'link' => 0, 'no_pair' => 0];
        $errors = [];

        foreach ($actions as $rowId => $action) {
            $row = KartuPelajarRow::where('batch_id', $batch->id)->find((int) $rowId);

            if ($row === null) {
                continue;
            }

            try {
                if ($action === 'confirm') {
                    $resolver->confirm($row);
                    $counts['confirm']++;
                } elseif ($action === 'reject') {
                    $resolver->reject($row);
                    $counts['reject']++;
                } elseif ($action === 'no_pair') {
                    $resolver->markNoCounterpart($row);
                    $counts['no_pair']++;
                } elseif (str_starts_with($action, 'link:')) {
                    $target = KartuPelajarRow::where('batch_id', $batch->id)->find((int) substr($action, 5));

                    if ($target === null) {
                        $errors[] = "Baris #{$row->source_row_number} ({$row->nama}): baris tujuan tidak ditemukan.";

                        continue;
                    }

                    $resolver->linkManually($row, $target);
                    $counts['link']++;
                }
            } catch (\InvalidArgumentException $e) {
                $errors[] = "Baris #{$row->source_row_number} ({$row->nama}): {$e->getMessage()}";
            }
        }

        $total = array_sum($counts);

        if ($total > 0) {
            $logger->log(
                $batch,
                'bulk_resolve',
                "Proses massal: {$counts['confirm']} dikonfirmasi, {$counts['reject']} ditolak, {$counts['link']} dihubungkan manual, {$counts['no_pair']} tanpa-pasangan",
                $counts
            );
        }

        if ($errors !== []) {
            return back()->withErrors(['resolusi' => implode(' | ', $errors)]);
        }

        if ($total === 0) {
            return back()->withErrors(['resolusi' => 'Tidak ada aksi yang dipilih.']);
        }

        return back()->with('status', "Berhasil memproses {$total} baris ({$counts['confirm']} dikonfirmasi, {$counts['reject']} ditolak, {$counts['link']} dihubungkan, {$counts['no_pair']} tanpa-pasangan).");
    }

    public function exportPreview(KartuPelajarBatch $batch, ExportBuilder $exportBuilder): View
    {
        return view('internal.kartu-pelajar.batch.ekspor', [
            'batch' => $batch,
            'pairs' => $exportBuilder->buildPreview($batch),
        ]);
    }

    public function exportDownload(KartuPelajarBatch $batch, ExportBuilder $exportBuilder, ActivityLogger $logger): Response
    {
        $csv = $exportBuilder->toCsv($batch);

        $logger->log($batch, 'export_downloaded', 'CSV ekspor diunduh');

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="kartu-pelajar-batch-'.$batch->id.'-'.now()->format('Ymd-His').'.csv"',
        ]);
    }

    public function visualQa(KartuPelajarBatch $batch, VisualQaChecker $checker): View
    {
        return view('internal.kartu-pelajar.batch.visual-qa', [
            'batch' => $batch,
            'report' => $checker->report($batch),
        ]);
    }

    public function visualQaUpload(Request $request, KartuPelajarBatch $batch, VisualQaChecker $checker, ActivityLogger $logger): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'mimes:psd,jpg,jpeg,png', 'max:30720'],
        ]);

        $result = $checker->ingest($batch, $request->file('files'));

        $logger->log($batch, 'visual_qa_uploaded', "Upload file visual QA: {$result['matched']} cocok, {$result['orphan']} tidak dikenali", $result);

        return back()->with('status', "Upload selesai: {$result['matched']} file cocok, {$result['orphan']} tidak dikenali.");
    }

    private function assertRowBelongsToBatch(KartuPelajarBatch $batch, KartuPelajarRow $row): void
    {
        abort_unless($row->batch_id === $batch->id, 404);
    }
}
