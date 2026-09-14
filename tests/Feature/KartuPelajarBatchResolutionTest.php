<?php

namespace Tests\Feature;

use App\Models\KartuPelajarRow;
use App\Models\User;
use App\Services\KartuPelajar\BatchPersister;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\RowAuditResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuPelajarBatchResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(string $source, int $rowNumber, string $nama, string $kelas, string $tanggalLahir): RowAuditResult
    {
        return new RowAuditResult($source, $rowNumber, [
            'nama' => FieldResult::clean($nama),
            'kelas' => FieldResult::clean($kelas),
            'tanggal_lahir' => FieldResult::clean($tanggalLahir),
            'nisn' => FieldResult::invalid(null, 'NISN kosong'),
        ], 'needs_manual_review', []);
    }

    public function test_guest_cannot_access_batch_pages(): void
    {
        $this->get(route('internal.kartu-pelajar.batch.index'))
            ->assertRedirect(route('internal.login'));
    }

    public function test_batch_index_lists_batches(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Satu', '7A', '2013-01-01')],
            []
        );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('internal.kartu-pelajar.batch.index'))
            ->assertOk()
            ->assertSee("#{$batch->id}");
    }

    public function test_batch_show_lists_rows_needing_attention_and_resolved(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Belum Cocok', '7A', '2013-01-01')],
            []
        );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('internal.kartu-pelajar.batch.show', $batch))
            ->assertOk()
            ->assertSee('Siswa Belum Cocok')
            ->assertSee('Tandai Tanpa Pasangan');
    }

    public function test_confirm_resolves_both_sides_when_candidate_exists(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Satu', '7A', '2013-01-01')],
            [$this->makeRow('gform', 1, 'Siswa Satu', '7A', '2013-01-01')],
        );

        // Simulasikan hasil MatchingEngine: sudah ada kandidat (fuzzy_candidate) yang perlu dikonfirmasi.
        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();
        $excelRow->update(['matching_status' => 'fuzzy_candidate', 'matched_via' => 'fuzzy', 'matched_row_id' => $gformRow->id]);
        $gformRow->update(['matching_status' => 'fuzzy_candidate', 'matched_via' => 'fuzzy', 'matched_row_id' => $excelRow->id]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.kartu-pelajar.batch.rows.confirm', [$batch, $excelRow]))
            ->assertRedirect();

        $this->assertSame('manual_resolved', $excelRow->fresh()->matching_status);
        $this->assertSame('manual_resolved', $gformRow->fresh()->matching_status);
    }

    public function test_confirm_fails_gracefully_when_no_candidate(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Tanpa Kandidat', '7A', '2013-01-01')],
            []
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->first();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.kartu-pelajar.batch.rows.confirm', [$batch, $excelRow]))
            ->assertSessionHasErrors('resolusi');

        $this->assertSame('unprocessed', $excelRow->fresh()->matching_status);
    }

    public function test_reject_resets_both_sides_to_unprocessed(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Satu', '7A', '2013-01-01')],
            [$this->makeRow('gform', 1, 'Siswa Beda', '7A', '2013-01-01')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();
        $excelRow->update(['matching_status' => 'fuzzy_candidate', 'matched_via' => 'fuzzy', 'matched_row_id' => $gformRow->id]);
        $gformRow->update(['matching_status' => 'fuzzy_candidate', 'matched_via' => 'fuzzy', 'matched_row_id' => $excelRow->id]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.kartu-pelajar.batch.rows.reject', [$batch, $excelRow]));

        $this->assertSame('unprocessed', $excelRow->fresh()->matching_status);
        $this->assertNull($excelRow->fresh()->matched_row_id);
        $this->assertSame('unprocessed', $gformRow->fresh()->matching_status);
        $this->assertNull($gformRow->fresh()->matched_row_id);
    }

    public function test_manual_link_connects_two_unmatched_rows(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Excel', '7A', '2013-01-01')],
            [$this->makeRow('gform', 1, 'Siswa GForm', '7B', '2013-02-02')],
        );

        $excelRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->first();
        $gformRow = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->first();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.kartu-pelajar.batch.rows.link', [$batch, $excelRow]), [
                'target_row_id' => $gformRow->id,
            ])
            ->assertRedirect();

        $excelRow->refresh();
        $gformRow->refresh();

        $this->assertSame('manual_resolved', $excelRow->matching_status);
        $this->assertSame('manual', $excelRow->matched_via);
        $this->assertSame($gformRow->id, $excelRow->matched_row_id);
        $this->assertSame($excelRow->id, $gformRow->matched_row_id);
    }

    public function test_manual_link_rejects_same_source_target(): void
    {
        $batch = (new BatchPersister)->persist(
            [
                $this->makeRow('excel', 1, 'Siswa A', '7A', '2013-01-01'),
                $this->makeRow('excel', 2, 'Siswa B', '7B', '2013-02-02'),
            ],
            []
        );

        $rows = KartuPelajarRow::where('batch_id', $batch->id)->get();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.kartu-pelajar.batch.rows.link', [$batch, $rows[0]]), [
                'target_row_id' => $rows[1]->id,
            ])
            ->assertSessionHasErrors('resolusi');

        $this->assertSame('unprocessed', $rows[0]->fresh()->matching_status);
    }

    public function test_mark_no_counterpart(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Sendiri', '7A', '2013-01-01')],
            []
        );

        $row = KartuPelajarRow::where('batch_id', $batch->id)->first();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('internal.kartu-pelajar.batch.rows.no-pair', [$batch, $row]));

        $row->refresh();
        $this->assertSame('manual_resolved', $row->matching_status);
        $this->assertSame('none', $row->matched_via);
        $this->assertNull($row->matched_row_id);
    }

    public function test_row_from_different_batch_returns_404(): void
    {
        $batchA = (new BatchPersister)->persist([$this->makeRow('excel', 1, 'Siswa A', '7A', '2013-01-01')], []);
        $batchB = (new BatchPersister)->persist([$this->makeRow('excel', 1, 'Siswa B', '7B', '2013-02-02')], []);

        $rowFromBatchB = KartuPelajarRow::where('batch_id', $batchB->id)->first();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.kartu-pelajar.batch.rows.no-pair', [$batchA, $rowFromBatchB]))
            ->assertNotFound();
    }

    public function test_bulk_resolve_processes_multiple_rows_in_one_submit(): void
    {
        $batch = (new BatchPersister)->persist(
            [
                $this->makeRow('excel', 1, 'Siswa Konfirmasi', '7A', '2013-01-01'),
                $this->makeRow('excel', 2, 'Siswa Tolak', '7B', '2013-02-02'),
                $this->makeRow('excel', 3, 'Siswa Hubung Manual', '7C', '2013-03-03'),
                $this->makeRow('excel', 4, 'Siswa Tanpa Pasangan', '7D', '2013-04-04'),
            ],
            [
                $this->makeRow('gform', 1, 'Siswa Konfirmasi', '7A', '2013-01-01'),
                $this->makeRow('gform', 2, 'Siswa Tolak Beda', '7B', '2013-02-02'),
                $this->makeRow('gform', 3, 'Siswa Hubung Manual GForm', '7C', '2013-03-03'),
            ],
        );

        $excelRows = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'excel')->orderBy('source_row_number')->get();
        $gformRows = KartuPelajarRow::where('batch_id', $batch->id)->where('source', 'gform')->orderBy('source_row_number')->get();

        // Baris 1 & 2 (excel) sudah ada kandidat (simulasi hasil MatchingEngine).
        $excelRows[0]->update(['matching_status' => 'fuzzy_candidate', 'matched_via' => 'fuzzy', 'matched_row_id' => $gformRows[0]->id]);
        $gformRows[0]->update(['matching_status' => 'fuzzy_candidate', 'matched_via' => 'fuzzy', 'matched_row_id' => $excelRows[0]->id]);
        $excelRows[1]->update(['matching_status' => 'fuzzy_candidate', 'matched_via' => 'fuzzy', 'matched_row_id' => $gformRows[1]->id]);
        $gformRows[1]->update(['matching_status' => 'fuzzy_candidate', 'matched_via' => 'fuzzy', 'matched_row_id' => $excelRows[1]->id]);
        // Baris 3 & 4 (excel) belum ada kandidat sama sekali.

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('internal.kartu-pelajar.batch.rows.bulk-resolve', $batch), [
            'action' => [
                $excelRows[0]->id => 'confirm',
                $excelRows[1]->id => 'reject',
                $excelRows[2]->id => 'link:'.$gformRows[2]->id,
                $excelRows[3]->id => 'no_pair',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame('manual_resolved', $excelRows[0]->fresh()->matching_status);
        $this->assertSame('manual_resolved', $gformRows[0]->fresh()->matching_status);

        $this->assertSame('unprocessed', $excelRows[1]->fresh()->matching_status);
        $this->assertNull($excelRows[1]->fresh()->matched_row_id);
        $this->assertSame('unprocessed', $gformRows[1]->fresh()->matching_status);

        $this->assertSame('manual_resolved', $excelRows[2]->fresh()->matching_status);
        $this->assertSame('manual', $excelRows[2]->fresh()->matched_via);
        $this->assertSame($gformRows[2]->id, $excelRows[2]->fresh()->matched_row_id);

        $this->assertSame('manual_resolved', $excelRows[3]->fresh()->matching_status);
        $this->assertSame('none', $excelRows[3]->fresh()->matched_via);

        $this->assertDatabaseHas('kartu_pelajar_activity_logs', [
            'batch_id' => $batch->id,
            'action' => 'bulk_resolve',
        ]);
    }

    public function test_bulk_resolve_skips_rows_left_blank(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Dilewati', '7A', '2013-01-01')],
            []
        );

        $row = KartuPelajarRow::where('batch_id', $batch->id)->first();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internal.kartu-pelajar.batch.rows.bulk-resolve', $batch), [
                'action' => [$row->id => ''],
            ])
            ->assertSessionHasErrors('resolusi');

        $this->assertSame('unprocessed', $row->fresh()->matching_status);
    }
}
