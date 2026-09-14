<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifikasiSiswaActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'proyek_id',
        'user_id',
        'action',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(VerifikasiSiswaProyek::class, 'proyek_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
