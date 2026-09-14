<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KartuPelajarActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'batch_id',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(KartuPelajarBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
