<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerifikasiSiswaProyek extends Model
{
    protected $fillable = [
        'nama',
        'google_sheet_id',
        'google_sheet_range',
        'last_synced_row',
    ];

    public function siswa(): HasMany
    {
        return $this->hasMany(VerifikasiSiswa::class, 'proyek_id');
    }

    public function formResponses(): HasMany
    {
        return $this->hasMany(VerifikasiSiswaFormResponse::class, 'proyek_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(VerifikasiSiswaActivityLog::class, 'proyek_id')->latest('id');
    }

    public function fieldDefinitions(): HasMany
    {
        return $this->hasMany(VerifikasiSiswaFieldDefinition::class, 'proyek_id')->orderBy('urutan');
    }

    public function exportColumns(): HasMany
    {
        return $this->hasMany(VerifikasiSiswaExportColumn::class, 'proyek_id')->orderBy('urutan');
    }
}
