<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\KartuPelajarBatch;
use App\Models\KartuPelajarRow;
use App\Services\KartuPelajar\ActivityLogger;
use App\Services\KartuPelajar\AiRowVerifier;
use App\Services\KartuPelajar\AuditReportBuilder;
use App\Services\KartuPelajar\BatchPersister;
use App\Services\KartuPelajar\DuplicateDetector;
use App\Services\KartuPelajar\MatchingEngine;
use App\Services\KartuPelajar\Readers\ExcelMasterReader;
use App\Services\KartuPelajar\Readers\GFormExcelReader;
use App\Services\KartuPelajar\Readers\GFormPdfReader;
use App\Services\KartuPelajar\ReUploadDetector;
use App\Services\KartuPelajar\RowAuditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class KartuPelajarController extends Controller
{
    public function index(): View
    {
        return view('internal.kartu-pelajar.index');
    }

    public function audit(
        Request $request,
        ExcelMasterReader $excelReader,
        GFormPdfReader $pdfReader,
        GFormExcelReader $gformExcelReader,
        RowAuditor $auditor,
        DuplicateDetector $dupDetector,
        AuditReportBuilder $reportBuilder,
        BatchPersister $batchPersister,
        MatchingEngine $matchingEngine,
        ActivityLogger $activityLogger,
        ReUploadDetector $reUploadDetector,
    ): View|RedirectResponse {
        $request->validate([
            'file_excel' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'file_gform' => ['required', 'file', 'mimes:pdf,xlsx', 'max:20480'],
        ]);

        $excelFileName = $request->file('file_excel')->getClientOriginalName();
        $gformFileName = $request->file('file_gform')->getClientOriginalName();
        $gformIsExcel = strtolower($request->file('file_gform')->getClientOriginalExtension()) === 'xlsx';

        $excelPath = $request->file('file_excel')->store('kartu-pelajar/tmp');
        $gformPath = $request->file('file_gform')->store('kartu-pelajar/tmp');

        try {
            $excelRows = $excelReader->read(Storage::path($excelPath));
            $gformRows = $gformIsExcel
                ? $gformExcelReader->read(Storage::path($gformPath))
                : $pdfReader->read(Storage::path($gformPath));
        } catch (\Throwable $e) {
            return back()->withErrors(['file_excel' => 'Gagal memproses file: '.$e->getMessage()]);
        } finally {
            Storage::delete([$excelPath, $gformPath]);
        }

        $excelResults = collect($excelRows)
            ->map(fn (array $row) => $auditor->auditRow('excel', $row['row_number'], $row))
            ->all();

        $gformAudited = collect($gformRows)
            ->map(fn (array $row) => [
                'timestamp' => $row['timestamp'],
                'result' => $auditor->auditRow('gform', $row['row_number'], $row),
            ])
            ->all();
        $gformResults = $dupDetector->annotate($gformAudited);

        $batch = $batchPersister->persist($excelResults, $gformResults, $excelFileName, $gformFileName);

        $activityLogger->log($batch, 'upload', "Upload & audit: {$excelFileName} + {$gformFileName}", [
            'total_excel' => count($excelResults),
            'total_gform' => count($gformResults),
        ]);

        $matchingSummary = $matchingEngine->matchBatch($batch->id);

        $activityLogger->log($batch, 'matching_run', 'Mesin pencocokan dijalankan', $matchingSummary);

        $reUploadWarnings = $reUploadDetector->detect($batch);

        if ($reUploadWarnings !== []) {
            $batchIds = implode(', ', array_map(fn ($w) => '#'.$w['batch']->id, $reUploadWarnings));
            $activityLogger->log($batch, 're_upload_warning', "Kemungkinan re-upload terdeteksi (tumpang tindih dengan batch {$batchIds})", [
                'overlaps' => array_map(fn ($w) => ['batch_id' => $w['batch']->id, 'overlap_percent' => $w['overlap_percent']], $reUploadWarnings),
            ]);
        }

        $csvExcel = $reportBuilder->toCsv($excelResults);
        $csvGform = $reportBuilder->toCsv($gformResults);

        session([
            'kartu_pelajar.csv.excel' => $csvExcel,
            'kartu_pelajar.csv.gform' => $csvGform,
            'kartu_pelajar.results.excel' => $excelResults,
            'kartu_pelajar.results.gform' => $gformResults,
            'kartu_pelajar.batch_id' => $batch->id,
        ]);

        return view('internal.kartu-pelajar.hasil', [
            'excelResults' => $excelResults,
            'gformResults' => $gformResults,
            'summaryExcel' => $reportBuilder->summarize($excelResults),
            'summaryGform' => $reportBuilder->summarize($gformResults),
            'excelOpinions' => [],
            'gformOpinions' => [],
            'batch' => $batch,
            'matchingSummary' => $matchingSummary,
            'matchingRows' => KartuPelajarRow::where('batch_id', $batch->id)->get(),
            'reUploadWarnings' => $reUploadWarnings,
        ]);
    }

    public function unduhCsv(string $sumber): Response
    {
        $csv = session("kartu_pelajar.csv.{$sumber}");

        abort_if(blank($csv), 404, 'Belum ada hasil audit di sesi ini, silakan upload ulang.');

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-kartu-pelajar-'.$sumber.'-'.now()->format('Ymd-His').'.csv"',
        ]);
    }

    public function verifikasiAi(AiRowVerifier $verifier, AuditReportBuilder $reportBuilder, ActivityLogger $activityLogger): View|RedirectResponse
    {
        $excelResults = session('kartu_pelajar.results.excel');
        $gformResults = session('kartu_pelajar.results.gform');

        if (blank($excelResults) || blank($gformResults)) {
            return redirect()->route('internal.kartu-pelajar.index')
                ->withErrors(['file_excel' => 'Sesi hasil audit sudah tidak ada, silakan upload ulang.']);
        }

        if (! $verifier->isConfigured()) {
            return back()->withErrors(['file_excel' => 'Verifikasi AI belum dikonfigurasi. Tambahkan DEEPSEEK_API_KEY di file .env lalu coba lagi.']);
        }

        $eligible = fn (array $results) => array_values(array_filter(
            $results,
            fn ($r) => in_array($r->status, ['clean', 'normalized_with_warning'], true)
        ));

        try {
            $excelOpinions = $verifier->verifyBatch($eligible($excelResults));
            $gformOpinions = $verifier->verifyBatch($eligible($gformResults));
        } catch (\Throwable $e) {
            return back()->withErrors(['file_excel' => 'Verifikasi AI gagal: '.$e->getMessage()]);
        }

        $batchId = session('kartu_pelajar.batch_id');
        $batch = KartuPelajarBatch::find($batchId);

        if ($batch !== null) {
            $activityLogger->log($batch, 'ai_verification', 'Verifikasi AI dijalankan', [
                'excel_flagged' => count(array_filter($excelOpinions, fn ($o) => $o['flagged'])),
                'gform_flagged' => count(array_filter($gformOpinions, fn ($o) => $o['flagged'])),
            ]);
        }

        return view('internal.kartu-pelajar.hasil', [
            'excelResults' => $excelResults,
            'gformResults' => $gformResults,
            'summaryExcel' => $reportBuilder->summarize($excelResults),
            'summaryGform' => $reportBuilder->summarize($gformResults),
            'excelOpinions' => $excelOpinions,
            'gformOpinions' => $gformOpinions,
            'batch' => $batch,
            'matchingSummary' => KartuPelajarRow::where('batch_id', $batchId)
                ->selectRaw('matching_status, count(*) as total')
                ->groupBy('matching_status')
                ->pluck('total', 'matching_status')
                ->all(),
            'matchingRows' => KartuPelajarRow::where('batch_id', $batchId)->get(),
            'reUploadWarnings' => [],
        ]);
    }
}
