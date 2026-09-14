<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KartuPelajarBatch extends Model
{
    protected $fillable = [
        'label',
        'sumber_file_excel',
        'sumber_file_gform',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(KartuPelajarRow::class, 'batch_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(KartuPelajarActivityLog::class, 'batch_id')->latest('id');
    }

    public function visualFiles(): HasMany
    {
        return $this->hasMany(KartuPelajarVisualFile::class, 'batch_id');
    }
}
