<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifikasiSiswaFormResponse extends Model
{
    protected $fillable = [
        'proyek_id',
        'sheet_row_number',
        'submitted_at',
        'kelas',
        'nama',
        'nis',
        'nisn',
        'status',
        'matched_siswa_id',
        'discrepancies',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'discrepancies' => 'array',
            'data' => 'array',
        ];
    }

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(VerifikasiSiswaProyek::class, 'proyek_id');
    }

    public function matchedSiswa(): BelongsTo
    {
        return $this->belongsTo(VerifikasiSiswa::class, 'matched_siswa_id');
    }
}
