<?php

namespace App\Services\VerifikasiSiswa;

use App\Models\VerifikasiSiswaProyek;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(VerifikasiSiswaProyek $proyek, string $action, string $description, array $meta = []): void
    {
        $proyek->activityLogs()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
