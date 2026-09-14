<?php

namespace App\Services\KartuPelajar;

use App\Models\KartuPelajarRow;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Aksi resolusi manual oleh admin atas hasil MatchingEngine — konfirmasi/tolak saran
 * (conflict dengan kandidat, atau fuzzy_candidate), hubungkan manual (untuk baris yang
 * belum ada kandidat sama sekali, atau konflik ambigu), atau tandai tidak ada pasangan.
 */
class BatchResolver
{
    public function confirm(KartuPelajarRow $row): void
    {
        if ($row->matched_row_id === null) {
            throw new InvalidArgumentException('Baris ini tidak punya kandidat pasangan untuk dikonfirmasi.');
        }

        DB::transaction(function () use ($row) {
            $row->update(['matching_status' => 'manual_resolved']);
            $row->matchedRow?->update(['matching_status' => 'manual_resolved']);
        });
    }

    public function reject(KartuPelajarRow $row): void
    {
        DB::transaction(function () use ($row) {
            $partner = $row->matchedRow;

            $row->update(['matching_status' => 'unprocessed', 'matched_via' => null, 'matched_row_id' => null]);
            $partner?->update(['matching_status' => 'unprocessed', 'matched_via' => null, 'matched_row_id' => null]);
        });
    }

    public function linkManually(KartuPelajarRow $row, KartuPelajarRow $target): void
    {
        if ($row->batch_id !== $target->batch_id) {
            throw new InvalidArgumentException('Kedua baris harus berada di batch yang sama.');
        }

        if ($row->source === $target->source) {
            throw new InvalidArgumentException('Tidak bisa menghubungkan 2 baris dari sumber yang sama.');
        }

        DB::transaction(function () use ($row, $target) {
            $row->update(['matching_status' => 'manual_resolved', 'matched_via' => 'manual', 'matched_row_id' => $target->id]);
            $target->update(['matching_status' => 'manual_resolved', 'matched_via' => 'manual', 'matched_row_id' => $row->id]);
        });
    }

    public function markNoCounterpart(KartuPelajarRow $row): void
    {
        $row->update(['matching_status' => 'manual_resolved', 'matched_via' => 'none', 'matched_row_id' => null]);
    }
}
