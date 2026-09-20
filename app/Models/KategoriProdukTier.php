<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KategoriProdukTier extends Model
{
    protected $fillable = [
        'kategori_produk_id',
        'min',
        'max',
        'harga',
    ];

    public function kategoriProduk(): BelongsTo
    {
        return $this->belongsTo(KategoriProduk::class);
    }
}
