<?php

namespace App\Services\KartuPelajar;

use App\Models\KartuPelajarBatch;
use Illuminate\Support\Facades\Auth;

/**
 * Catat jejak aktivitas per batch (siapa ngapain kapan) — upload, proses pencocokan,
 * aksi resolusi, verifikasi AI, unduh ekspor, upload visual QA.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(KartuPelajarBatch $batch, string $action, string $description, array $meta = []): void
    {
        $batch->activityLogs()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
