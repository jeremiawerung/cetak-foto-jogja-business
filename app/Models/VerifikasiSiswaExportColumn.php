<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 baris = 1 kolom di CSV ekspor proyek ini (urutan, label, sumbernya). Terpisah dari
 * VerifikasiSiswaFieldDefinition karena ekspor punya kebutuhan khusus yang bukan
 * "field data siswa biasa" — gabungan TTL (tempat+tanggal lahir) dan kolom ID_FILE.
 */
class VerifikasiSiswaExportColumn extends Model
{
    protected $fillable = [
        'proyek_id',
        'urutan',
        'label',
        'sumber_tipe',
        'field_key',
    ];

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(VerifikasiSiswaProyek::class, 'proyek_id');
    }
}
