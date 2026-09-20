<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotographerBooking extends Model
{
    public const STATUSES = [
        'baru' => 'Baru',
        'dikonfirmasi' => 'Dikonfirmasi',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    protected $fillable = [
        'nama',
        'no_hp',
        'lokasi',
        'tanggal',
        'jam',
        'estimasi_orang',
        'catatan',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
