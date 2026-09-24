<?php

namespace Tests\Feature;

use App\Models\KartuPelajarRow;
use App\Models\User;
use App\Services\KartuPelajar\BatchPersister;
use App\Services\KartuPelajar\ExportBuilder;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\RowAuditResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuPelajarExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('kartu-pelajar routes are dormant (commented out in routes/web.php, superseded by verifikasi-siswa) — see the comment above the route group.');
    }

    private function makeRow(
        string $source,
        int $rowNumber,
        string $nama,
        string $kelas,
        string $tanggalLahir,
        ?string $alamat = null,
        ?string $tempatLahir = null,
        ?string $agama = null,
        ?string $jenisKelamin = null,
        ?string $nis = null,
        ?string $nisn = null,
    ): RowAuditResult {
        $fields = [
            'nama' => FieldResult::clean($nama),
            'kelas' => FieldResult::clean($kelas),
            'tanggal_lahir' => FieldResult::clean($tanggalLahir),
            'alamat' => $alamat !== null ? FieldResult::clean($alamat) : FieldResult::warning(null, 'Alamat kosong'),
            'nisn' => $nisn !== null ? FieldResult::clean($nisn) : FieldResult::invalid(null, 'NISN kosong'),
        ];

        if ($nis !== null) {
            $fields['nis'] = FieldResult::clean($nis);
        }

        return new RowAuditResult($source, $rowNumber, $fields, 'clean', [], tempatLahir: $tempatLahir, agama: $agama, jenisKelamin: $jenisKelamin);
    }

    private function markMatched(KartuPelajarRow $a, KartuPelajarRow $b, string $status = 'auto_matched'): void
    {
        $a->update(['matching_status' => $status, 'matched_via' => 'nisn', 'matched_row_id' => $b->id]);
        $b->update(['matching_status' => $status, 'matched_via' => 'nisn', 'matched_row_id' => $a->id]);
    }

    public function test_matched_pair_with_agreeing_fields_exports_cleanly(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Adinda Nindya Kirana', '7A', '2013-09-01', 'Jl Contoh No 1', 'Yogyakarta', 'Islam', 'Perempuan', '11454', '0133204197')],
            [$this->makeRow('gform', 1, 'Adinda Nindya Kirana', '7A', '2013-09-01', 'Jl Contoh No 1', 'Yogyakarta', 'Islam', null, null, '0133204197')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();
        $this->markMatched($excelRow, $gformRow);

        $pairs = (new ExportBuilder)->buildPreview($batch->fresh());

        $this->assertCount(1, $pairs);
        $this->assertSame([], $pairs[0]['discrepancies']);
        $this->assertSame('Adinda Nindya Kirana', $pairs[0]['merged']['nama']);
        $this->assertSame('11454', $pairs[0]['merged']['nis']);
        $this->assertSame('Perempuan', $pairs[0]['merged']['jenis_kelamin']);
        $this->assertSame('KP-'.str_pad((string) $excelRow->id, 6, '0', STR_PAD_LEFT), $pairs[0]['id_file']);
    }

    public function test_discrepancy_detected_when_alamat_differs(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Satu', '7A', '2013-01-01', 'Alamat Excel', 'Yogyakarta')],
            [$this->makeRow('gform', 1, 'Siswa Satu', '7A', '2013-01-01', 'Alamat GForm Berbeda', 'Yogyakarta')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();
        $this->markMatched($excelRow, $gformRow);

        $pairs = (new ExportBuilder)->buildPreview($batch->fresh());

        $this->assertContains('alamat', $pairs[0]['discrepancies']);
        // Excel tetap diutamakan untuk nilai akhir walau berbeda.
        $this->assertSame('Alamat Excel', $pairs[0]['merged']['alamat']);
    }

    public function test_gform_fills_gap_when_excel_field_missing(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Dua', '7B', '2013-02-02', null)],
            [$this->makeRow('gform', 1, 'Siswa Dua', '7B', '2013-02-02', 'Alamat Dari GForm')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();
        $this->markMatched($excelRow, $gformRow);

        $pairs = (new ExportBuilder)->buildPreview($batch->fresh());

        $this->assertSame('Alamat Dari GForm', $pairs[0]['merged']['alamat']);
        $this->assertNotContains('alamat', $pairs[0]['discrepancies']);
    }

    public function test_no_counterpart_row_excluded_from_export(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Sendirian', '7A', '2013-01-01')],
            []
        );

        $row = KartuPelajarRow::where('batch_id', $batch->id)->first();
        $row->update(['matching_status' => 'manual_resolved', 'matched_via' => 'none', 'matched_row_id' => null]);

        $pairs = (new ExportBuilder)->buildPreview($batch->fresh());

        $this->assertCount(0, $pairs);
    }

    public function test_unresolved_rows_excluded_from_export(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Belum Cocok', '7A', '2013-01-01')],
            []
        );

        $pairs = (new ExportBuilder)->buildPreview($batch->fresh());

        $this->assertCount(0, $pairs);
    }

    public function test_ttl_combines_tempat_and_formatted_tanggal(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Tiga', '7A', '2013-09-01', 'Jl Contoh', 'Yogyakarta')],
            [$this->makeRow('gform', 1, 'Siswa Tiga', '7A', '2013-09-01', 'Jl Contoh', 'Yogyakarta')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();
        $this->markMatched($excelRow, $gformRow);

        $csv = (new ExportBuilder)->toCsv($batch->fresh());

        $this->assertStringContainsString('Yogyakarta', $csv);
        $this->assertStringContainsString('September 2013', $csv);
    }

    public function test_csv_download_route_returns_correct_headers(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Empat', '7A', '2013-01-01')],
            [$this->makeRow('gform', 1, 'Siswa Empat', '7A', '2013-01-01')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();
        $this->markMatched($excelRow, $gformRow);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('internal.kartu-pelajar.batch.export.download', $batch));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('NAMA', false);
        $response->assertSee('ID_FILE', false);
    }

    public function test_export_preview_page_renders(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Lima', '7A', '2013-01-01')],
            []
        );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('internal.kartu-pelajar.batch.export.preview', $batch))
            ->assertOk()
            ->assertSee('Pratinjau Ekspor');
    }
}
