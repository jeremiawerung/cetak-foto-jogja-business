<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KategoriProdukItem extends Model
{
    protected $fillable = [
        'kategori_produk_id',
        'slug',
        'nama',
        'gambar',
        'harga',
        'harga_normal',
        'rincian',
        'panjang',
        'lebar',
        'berat',
        'is_custom',
        'is_active',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function kategoriProduk(): BelongsTo
    {
        return $this->belongsTo(KategoriProduk::class);
    }
}
