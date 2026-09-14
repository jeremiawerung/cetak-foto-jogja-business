<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KartuPelajarRow extends Model
{
    protected $fillable = [
        'batch_id',
        'source',
        'source_row_number',
        'status',
        'reasons',
        'nama',
        'kelas',
        'tanggal_lahir',
        'tempat_lahir',
        'nis',
        'nisn',
        'alamat',
        'agama',
        'jenis_kelamin',
        'matching_key_nisn',
        'matching_key_composite',
        'matching_status',
        'matched_via',
        'matched_row_id',
    ];

    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'tanggal_lahir' => 'date:Y-m-d',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(KartuPelajarBatch::class, 'batch_id');
    }

    public function matchedRow(): BelongsTo
    {
        return $this->belongsTo(self::class, 'matched_row_id');
    }

    /**
     * ID_FILE: pengganti nama siswa sebagai nama file, diturunkan dari ID baris permanen
     * di database (bukan dari nama/NISN) — supaya tetap stabil meski data lain direvisi,
     * dan otomatis aman dari karakter aneh (tanda kutip, dsb) karena tidak pernah menyentuh nama asli.
     */
    public function idFile(): string
    {
        return sprintf('KP-%06d', $this->id);
    }
}
