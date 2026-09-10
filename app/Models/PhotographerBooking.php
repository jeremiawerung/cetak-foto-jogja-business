<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotographerBooking extends Model
{
    protected $fillable = [
        'nama',
        'no_hp',
        'jenis_acara',
        'lokasi',
        'tanggal',
        'jam',
        'estimasi_orang',
        'paket',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
