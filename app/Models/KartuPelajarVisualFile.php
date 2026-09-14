<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KartuPelajarVisualFile extends Model
{
    protected $fillable = [
        'batch_id',
        'id_file',
        'original_filename',
        'stored_path',
        'thumbnail_path',
        'status',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(KartuPelajarBatch::class, 'batch_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->stored_path);
    }

    /**
     * URL preview yang bisa ditampilkan <img> — thumbnail hasil ekstraksi kalau ada
     * (untuk file .psd), atau file aslinya kalau memang sudah format gambar biasa.
     */
    public function previewUrl(): ?string
    {
        if ($this->thumbnail_path !== null) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }

        if (str_ends_with(mb_strtolower($this->stored_path), '.psd')) {
            return null;
        }

        return $this->url();
    }
}
