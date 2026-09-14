<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifikasiSiswa extends Model
{
    protected $fillable = [
        'proyek_id',
        'kelas',
        'nama',
        'nis',
        'nisn',
        'status',
        'matched_form_response_id',
        'is_locked',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'data' => 'array',
        ];
    }

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(VerifikasiSiswaProyek::class, 'proyek_id');
    }

    public function matchedFormResponse(): BelongsTo
    {
        return $this->belongsTo(VerifikasiSiswaFormResponse::class, 'matched_form_response_id');
    }

    /** ID stabil untuk penamaan file Photoshop, mis. "KP-000123". */
    public function idFile(): string
    {
        return sprintf('KP-%06d', $this->id);
    }
}
