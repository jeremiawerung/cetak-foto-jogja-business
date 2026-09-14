<?php

namespace App\Services\KartuPelajar;

use App\Models\KartuPelajarBatch;
use App\Models\KartuPelajarRow;

/**
 * Deteksi kemungkinan re-upload: batch baru yang NISN-nya (sisi Excel) tumpang tindih
 * signifikan dengan batch LAIN yang sudah ada — indikasi file yang sama/serupa
 * tidak sengaja diproses ulang jadi batch baru, bukan melanjutkan yang lama.
 */
class ReUploadDetector
{
    /**
     * @return list<array{batch: KartuPelajarBatch, overlap_count: int, overlap_percent: float}>
     */
    public function detect(KartuPelajarBatch $newBatch, float $thresholdPercent = 50.0): array
    {
        $newNisns = KartuPelajarRow::where('batch_id', $newBatch->id)
            ->where('source', 'excel')
            ->whereNotNull('nisn')
            ->pluck('nisn')
            ->unique();

        if ($newNisns->isEmpty()) {
            return [];
        }

        $overlapsByBatch = KartuPelajarRow::where('batch_id', '!=', $newBatch->id)
            ->where('source', 'excel')
            ->whereIn('nisn', $newNisns->all())
            ->get(['batch_id', 'nisn'])
            ->groupBy('batch_id');

        $results = [];

        foreach ($overlapsByBatch as $batchId => $rows) {
            $overlapCount = $rows->pluck('nisn')->unique()->count();
            $percent = ($overlapCount / $newNisns->count()) * 100;

            if ($percent < $thresholdPercent) {
                continue;
            }

            $otherBatch = KartuPelajarBatch::find($batchId);

            if ($otherBatch === null) {
                continue;
            }

            $results[] = [
                'batch' => $otherBatch,
                'overlap_count' => $overlapCount,
                'overlap_percent' => round($percent, 1),
            ];
        }

        return $results;
    }
}
