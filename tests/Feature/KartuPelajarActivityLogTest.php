<?php

namespace Tests\Feature;

use App\Models\KartuPelajarRow;
use App\Models\User;
use App\Services\KartuPelajar\BatchPersister;
use App\Services\KartuPelajar\FieldResult;
use App\Services\KartuPelajar\RowAuditResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuPelajarActivityLogTest extends TestCase
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

    public function test_resolution_actions_are_logged_and_shown_on_batch_page(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Satu')],
            []
        );

        $row = KartuPelajarRow::where('batch_id', $batch->id)->first();
        $user = User::factory()->create(['name' => 'Admin Tes']);

        $this->actingAs($user)->post(route('internal.kartu-pelajar.batch.rows.no-pair', [$batch, $row]));

        $this->assertDatabaseHas('kartu_pelajar_activity_logs', [
            'batch_id' => $batch->id,
            'action' => 'row_no_pair',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('internal.kartu-pelajar.batch.show', $batch))
            ->assertSee('Admin Tes')
            ->assertSee('Tandai tanpa pasangan', false);
    }

    public function test_export_download_is_logged(): void
    {
        $batch = (new BatchPersister)->persist(
            [$this->makeRow('excel', 1, 'Siswa Satu')],
            [$this->makeRow('gform', 1, 'Siswa Satu')],
        );

        $rows = KartuPelajarRow::where('batch_id', $batch->id)->get();
        $rows[0]->update(['matching_status' => 'auto_matched', 'matched_row_id' => $rows[1]->id]);
        $rows[1]->update(['matching_status' => 'auto_matched', 'matched_row_id' => $rows[0]->id]);

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('internal.kartu-pelajar.batch.export.download', $batch));

        $this->assertDatabaseHas('kartu_pelajar_activity_logs', [
            'batch_id' => $batch->id,
            'action' => 'export_downloaded',
        ]);
    }
}
