<?php

namespace Tests\Feature;

use App\Models\KartuPelajarRow;
use App\Services\KartuPelajar\BatchPersister;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\MatchingEngine;
use App\Services\KartuPelajar\RowAuditResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuPelajarMatchingEngineTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(string $source, int $rowNumber, string $nama, string $kelas, string $tanggalLahir, ?string $nisn, string $status = 'clean'): RowAuditResult
    {
        $fields = [
            'nama' => FieldResult::clean($nama),
            'kelas' => FieldResult::clean($kelas),
            'tanggal_lahir' => FieldResult::clean($tanggalLahir),
            'nisn' => $nisn !== null ? FieldResult::clean($nisn) : FieldResult::invalid(null, 'NISN kosong'),
        ];

        return new RowAuditResult($source, $rowNumber, $fields, $status, []);
    }

    private function persistAndMatch(array $excelResults, array $gformResults): array
    {
        $batch = (new BatchPersister)->persist($excelResults, $gformResults);
        $summary = (new MatchingEngine)->matchBatch($batch->id);

        return [$batch, $summary];
    }

    public function test_exact_nisn_match_with_similar_name(): void
    {
        [$batch] = $this->persistAndMatch(
            [$this->makeRow('excel', 1, 'Adinda Nindya Kirana', '7A', '2013-09-01', '0133204197')],
            [$this->makeRow('gform', 1, 'Adinda Nindya Kirana', '7A', '2013-09-01', '0133204197')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();

        $this->assertSame('auto_matched', $excelRow->matching_status);
        $this->assertSame('nisn', $excelRow->matched_via);
        $this->assertSame($gformRow->id, $excelRow->matched_row_id);
        $this->assertSame($excelRow->id, $gformRow->matched_row_id);
    }

    public function test_same_nisn_but_wildly_different_name_becomes_conflict(): void
    {
        [$batch] = $this->persistAndMatch(
            [$this->makeRow('excel', 1, 'Adinda Nindya Kirana', '7A', '2013-09-01', '0133204197')],
            [$this->makeRow('gform', 1, 'Bagas Setiawan Putra', '7A', '2013-09-01', '0133204197')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();

        $this->assertSame('conflict', $excelRow->matching_status);
    }

    public function test_ambiguous_nisn_shared_by_two_rows_becomes_conflict(): void
    {
        [$batch] = $this->persistAndMatch(
            [
                $this->makeRow('excel', 1, 'Siswa Satu', '7A', '2013-01-01', '0111111111'),
                $this->makeRow('excel', 2, 'Siswa Dua', '7B', '2013-02-02', '0111111111'),
            ],
            [$this->makeRow('gform', 1, 'Siswa Satu', '7A', '2013-01-01', '0111111111')],
        );

        $rows = KartuPelajarRow::where('batch_id', $batch->id)->get();

        $this->assertTrue($rows->every(fn ($r) => $r->matching_status === 'conflict'));
    }

    public function test_fallback_composite_match_when_nisn_missing(): void
    {
        [$batch] = $this->persistAndMatch(
            [$this->makeRow('excel', 1, 'Belfa Lia Abdul Gafar', '7C', '2013-04-04', '0138215118')],
            [$this->makeRow('gform', 1, 'Belfa Lia Abdul Gafar', '7C', '2013-04-04', null, 'needs_manual_review')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();

        $this->assertSame('auto_matched', $excelRow->matching_status);
        $this->assertSame('fallback', $excelRow->matched_via);
    }

    public function test_very_high_name_similarity_is_auto_matched_without_confirmation(): void
    {
        // "Muhammad" vs "Muhamad" cuma beda 1 huruf -> similarity ~95.2%, di atas ambang
        // auto-match (95%) -> langsung auto_matched, admin tidak perlu konfirmasi manual.
        [$batch] = $this->persistAndMatch(
            [$this->makeRow('excel', 1, 'Muhammad Rizki Ananda', '7B', '2013-05-05', '0122223333')],
            [$this->makeRow('gform', 1, 'Muhamad Rizki Ananda', '7B', '2013-05-05', null, 'needs_manual_review')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();

        $this->assertSame('auto_matched', $excelRow->matching_status);
        $this->assertSame('fuzzy', $excelRow->matched_via);
        $this->assertNotNull($excelRow->matched_row_id);
    }

    public function test_moderate_name_similarity_stays_fuzzy_candidate_needing_confirmation(): void
    {
        // "Setiawan" vs "Setiawon" + "Bagas" vs "Bagos" -> similarity ~90%, di atas ambang
        // saran (75%) tapi di bawah ambang auto-match (95%) -> tetap perlu konfirmasi manual.
        [$batch] = $this->persistAndMatch(
            [$this->makeRow('excel', 1, 'Bagas Setiawan Putra', '7B', '2013-05-05', '0122223333')],
            [$this->makeRow('gform', 1, 'Bagos Setiawon Putra', '7B', '2013-05-05', null, 'needs_manual_review')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();

        $this->assertSame('fuzzy_candidate', $excelRow->matching_status);
        $this->assertSame('fuzzy', $excelRow->matched_via);
        $this->assertNotNull($excelRow->matched_row_id);
    }

    public function test_no_candidate_at_all_remains_unprocessed(): void
    {
        [$batch] = $this->persistAndMatch(
            [$this->makeRow('excel', 1, 'Siswa Tanpa Pasangan', '7A', '2013-01-01', '0199999999')],
            [],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->first();

        $this->assertSame('unprocessed', $excelRow->matching_status);
        $this->assertNull($excelRow->matched_row_id);
    }

    public function test_ambiguous_fuzzy_candidates_stay_unprocessed(): void
    {
        // Dua kandidat GForm dengan tanggal lahir & kelas sama persis, dan skor kemiripan nama nyaris identik
        // terhadap baris Excel -> tidak boleh disarankan otomatis karena terlalu ambigu.
        [$batch] = $this->persistAndMatch(
            [$this->makeRow('excel', 1, 'Randi Saputra', '7D', '2013-06-06', '0155556666')],
            [
                $this->makeRow('gform', 1, 'Randi Saputro', '7D', '2013-06-06', null, 'needs_manual_review'),
                $this->makeRow('gform', 2, 'Randi Saputri', '7D', '2013-06-06', null, 'needs_manual_review'),
            ],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();

        $this->assertSame('unprocessed', $excelRow->matching_status);
    }
}
