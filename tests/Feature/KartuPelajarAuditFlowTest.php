<?php

namespace Tests\Feature;

use App\Models\KartuPelajarRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;

class KartuPelajarAuditFlowTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_DIR = 'Sample/SMPN 15 YOGYAKARTA/DATA';

    public function test_full_upload_and_audit_flow_with_real_sample_files(): void
    {
        if (! (new ExecutableFinder)->find('pdftotext')) {
            $this->markTestSkipped('pdftotext tidak tersedia di environment ini.');
        }

        $excelPath = base_path(self::SAMPLE_DIR.'/BIODATA SISWA SMPN 15 YOGYAKARTA.xlsx');
        $pdfPath = base_path(self::SAMPLE_DIR.'/BIODATA KARTU SISWA SMPN 15 YOGYAKARTA (Jawaban) - Form Responses 1-3.pdf');

        if (! is_file($excelPath) || ! is_file($pdfPath)) {
            $this->markTestSkipped('File sample tidak ditemukan.');
        }

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('internal.kartu-pelajar.audit'), [
            'file_excel' => new UploadedFile($excelPath, 'master.xlsx', null, null, true),
            'file_gform' => new UploadedFile($pdfPath, 'gform.pdf', null, null, true),
        ]);

        $response->assertOk();
        $response->assertViewIs('internal.kartu-pelajar.hasil');
        $response->assertViewHas('summaryExcel', fn ($summary) => $summary['total'] > 0);
        $response->assertViewHas('summaryGform', fn ($summary) => $summary['total'] > 0);

        $batch = $response->viewData('batch');
        $this->assertNotNull($batch);
        $this->assertDatabaseHas('kartu_pelajar_batches', ['id' => $batch->id]);
        $response->assertSee("Batch #{$batch->id}");
        $this->assertSame(
            $response->viewData('summaryExcel')['total'] + $response->viewData('summaryGform')['total'],
            KartuPelajarRow::where('batch_id', $batch->id)->count()
        );

        $csvResponse = $this->actingAs($user)->get(route('internal.kartu-pelajar.audit.unduh', 'excel'));
        $csvResponse->assertOk();
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_full_upload_and_audit_flow_with_gform_as_excel(): void
    {
        $excelPath = base_path(self::SAMPLE_DIR.'/BIODATA SISWA SMPN 15 YOGYAKARTA.xlsx');
        $gformExcelPath = base_path(self::SAMPLE_DIR.'/BIODATA KARTU SISWA SMPN 15 YOGYAKARTA (Jawaban).xlsx');

        if (! is_file($excelPath) || ! is_file($gformExcelPath)) {
            $this->markTestSkipped('File sample tidak ditemukan.');
        }

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('internal.kartu-pelajar.audit'), [
            'file_excel' => new UploadedFile($excelPath, 'master.xlsx', null, null, true),
            'file_gform' => new UploadedFile($gformExcelPath, 'gform.xlsx', null, null, true),
        ]);

        $response->assertOk();
        $response->assertViewIs('internal.kartu-pelajar.hasil');

        // Sumber GForm-as-xlsx jauh lebih bersih daripada PDF -> mayoritas harus clean/warning, bukan needs_manual_review.
        $summaryGform = $response->viewData('summaryGform');
        $this->assertGreaterThan($summaryGform['needs_manual_review'], $summaryGform['clean']);
    }

    public function test_guest_cannot_access_audit_page(): void
    {
        $this->get(route('internal.kartu-pelajar.index'))
            ->assertRedirect(route('internal.login'));
    }
}
