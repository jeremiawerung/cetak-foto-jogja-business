<?php

namespace Tests\Feature;

use App\Services\KartuPelajar\BatchPersister;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\ReUploadDetector;
use App\Services\KartuPelajar\RowAuditResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuPelajarReUploadDetectorTest extends TestCase
{
    use RefreshDatabase;

    private function makeExcelRow(int $rowNumber, string $nama, string $nisn): RowAuditResult
    {
        return new RowAuditResult('excel', $rowNumber, [
            'nama' => FieldResult::clean($nama),
            'kelas' => FieldResult::clean('7A'),
            'tanggal_lahir' => FieldResult::clean('2013-01-01'),
            'nisn' => FieldResult::clean($nisn),
        ], 'clean', []);
    }

    public function test_detects_significant_nisn_overlap_with_existing_batch(): void
    {
        $persister = new BatchPersister;

        $firstBatch = $persister->persist([
            $this->makeExcelRow(1, 'Siswa A', '0111111111'),
            $this->makeExcelRow(2, 'Siswa B', '0122222222'),
            $this->makeExcelRow(3, 'Siswa C', '0133333333'),
        ], []);

        $secondBatch = $persister->persist([
            $this->makeExcelRow(1, 'Siswa A', '0111111111'),
            $this->makeExcelRow(2, 'Siswa B', '0122222222'),
        ], []);

        $warnings = (new ReUploadDetector)->detect($secondBatch);

        $this->assertCount(1, $warnings);
        $this->assertSame($firstBatch->id, $warnings[0]['batch']->id);
        $this->assertSame(100.0, $warnings[0]['overlap_percent']);
    }

    public function test_no_warning_when_overlap_below_threshold(): void
    {
        $persister = new BatchPersister;

        $persister->persist([
            $this->makeExcelRow(1, 'Siswa A', '0111111111'),
        ], []);

        $secondBatch = $persister->persist([
            $this->makeExcelRow(1, 'Siswa X', '0199999999'),
            $this->makeExcelRow(2, 'Siswa Y', '0188888888'),
            $this->makeExcelRow(3, 'Siswa Z', '0177777777'),
            $this->makeExcelRow(4, 'Siswa W', '0166666666'),
        ], []);

        $warnings = (new ReUploadDetector)->detect($secondBatch);

        $this->assertCount(0, $warnings);
    }

    public function test_no_warning_for_first_batch_with_no_prior_data(): void
    {
        $batch = (new BatchPersister)->persist([
            $this->makeExcelRow(1, 'Siswa A', '0111111111'),
        ], []);

        $warnings = (new ReUploadDetector)->detect($batch);

        $this->assertCount(0, $warnings);
    }
}
