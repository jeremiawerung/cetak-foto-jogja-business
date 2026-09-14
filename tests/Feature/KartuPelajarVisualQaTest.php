<?php

namespace Tests\Feature;

use App\Models\KartuPelajarRow;
use App\Models\User;
use App\Services\KartuPelajar\BatchPersister;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\RowAuditResult;
use App\Services\KartuPelajar\VisualQaChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KartuPelajarVisualQaTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(string $source, int $rowNumber, string $nama): RowAuditResult
    {
        return new RowAuditResult($source, $rowNumber, [
            'nama' => FieldResult::clean($nama),
            'kelas' => FieldResult::clean('7A'),
            'tanggal_lahir' => FieldResult::clean('2013-01-01'),
            'nisn' => FieldResult::invalid(null, 'NISN kosong'),
        ], 'needs_manual_review', []);
    }

    private function makeMatchedBatch()
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Satu')],
            [$this->makeRow('gform', 1, 'Siswa Satu')],
        );

        $rows = KartuPelajarRow::where('batch_id', $batch->id)->get();
        $rows[0]->update(['matching_status' => 'auto_matched', 'matched_row_id' => $rows[1]->id]);
        $rows[1]->update(['matching_status' => 'auto_matched', 'matched_row_id' => $rows[0]->id]);

        return [$batch, $rows[0]->fresh()];
    }

    public function test_file_matching_expected_id_file_is_marked_matched(): void
    {
        Storage::fake('public');

        [$batch, $excelRow] = $this->makeMatchedBatch();
        $idFile = $excelRow->idFile();

        $file = UploadedFile::fake()->image("{$idFile}.jpg");

        $result = (new VisualQaChecker)->ingest($batch, [$file]);

        $this->assertSame(1, $result['matched']);
        $this->assertSame(0, $result['orphan']);
        $this->assertDatabaseHas('kartu_pelajar_visual_files', [
            'batch_id' => $batch->id,
            'id_file' => $idFile,
            'status' => 'matched',
        ]);
    }

    public function test_file_with_unrecognized_name_is_marked_orphan(): void
    {
        Storage::fake('public');

        [$batch] = $this->makeMatchedBatch();

        $file = UploadedFile::fake()->image('foto_acak.jpg');

        $result = (new VisualQaChecker)->ingest($batch, [$file]);

        $this->assertSame(0, $result['matched']);
        $this->assertSame(1, $result['orphan']);
    }

    public function test_report_lists_missing_id_files_not_yet_uploaded(): void
    {
        Storage::fake('public');

        [$batch, $excelRow] = $this->makeMatchedBatch();

        $report = (new VisualQaChecker)->report($batch);

        $this->assertContains($excelRow->idFile(), $report['hilang']);
        $this->assertCount(0, $report['ditemukan']);
    }

    public function test_report_no_longer_lists_missing_after_upload(): void
    {
        Storage::fake('public');

        [$batch, $excelRow] = $this->makeMatchedBatch();
        $idFile = $excelRow->idFile();

        (new VisualQaChecker)->ingest($batch, [UploadedFile::fake()->image("{$idFile}.jpg")]);

        $report = (new VisualQaChecker)->report($batch);

        $this->assertNotContains($idFile, $report['hilang']);
        $this->assertCount(1, $report['ditemukan']);
    }

    public function test_psd_upload_extracts_embedded_thumbnail_and_preview_url_uses_it(): void
    {
        Storage::fake('public');

        $samplePsd = base_path('Sample/SMPN 15 YOGYAKARTA/KARTU BELAKANG/VII A/1_0137272203.psd');

        if (! is_file($samplePsd)) {
            $this->markTestSkipped('File sample PSD tidak ditemukan.');
        }

        [$batch, $excelRow] = $this->makeMatchedBatch();
        $idFile = $excelRow->idFile();

        $file = new UploadedFile($samplePsd, "{$idFile}.psd", null, null, true);

        (new VisualQaChecker)->ingest($batch, [$file]);

        $visualFile = $batch->visualFiles()->first();

        $this->assertSame('matched', $visualFile->status);
        $this->assertNotNull($visualFile->thumbnail_path);
        Storage::disk('public')->assertExists($visualFile->thumbnail_path);
        $this->assertSame($visualFile->previewUrl(), Storage::disk('public')->url($visualFile->thumbnail_path));
    }

    public function test_visual_qa_upload_route_works_end_to_end(): void
    {
        Storage::fake('public');

        [$batch, $excelRow] = $this->makeMatchedBatch();
        $idFile = $excelRow->idFile();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('internal.kartu-pelajar.batch.visual-qa.upload', $batch),
            ['files' => [UploadedFile::fake()->image("{$idFile}.jpg")]]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('kartu_pelajar_activity_logs', [
            'batch_id' => $batch->id,
            'action' => 'visual_qa_uploaded',
        ]);

        $this->actingAs($user)
            ->get(route('internal.kartu-pelajar.batch.visual-qa', $batch))
            ->assertOk()
            ->assertSee($idFile);
    }
}
