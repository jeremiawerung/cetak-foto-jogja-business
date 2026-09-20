<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriProduk extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'gambar',
        'pricing_mode',
        'satuan_label',
        'panjang',
        'lebar',
        'berat',
        'deskripsi',
        'catatan',
        'is_active',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(KategoriProdukItem::class)->orderBy('urutan');
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(KategoriProdukTier::class)->orderBy('min');
    }
}
