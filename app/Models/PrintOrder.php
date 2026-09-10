<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintOrder extends Model
{
    protected $fillable = [
        'kategori',
        'varian',
        'jumlah',
        'estimasi_harga',
        'nama',
        'no_hp',
        'catatan',
        'file_paths',
    ];

    protected $casts = [
        'file_paths' => 'array',
    ];
}
