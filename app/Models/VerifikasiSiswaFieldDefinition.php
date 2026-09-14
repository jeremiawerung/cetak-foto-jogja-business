<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 baris = 1 field data siswa yang dipakai proyek ini (mis. "agama", "nomor_sekolah").
 * "nama"/"kelas" (is_core=true) tetap kolom asli di VerifikasiSiswa/VerifikasiSiswaFormResponse
 * — sisanya disimpan di kolom `data` (json) kedua model itu, key-nya = `key` di sini.
 */
class VerifikasiSiswaFieldDefinition extends Model
{
    protected $fillable = [
        'proyek_id',
        'key',
        'label',
        'tipe',
        'is_core',
        'sumber_utama',
        'urutan',
        'kata_kunci_header',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'kata_kunci_header' => 'array',
        ];
    }

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(VerifikasiSiswaProyek::class, 'proyek_id');
    }
}
