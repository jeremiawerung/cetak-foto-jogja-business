<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanPembayaran extends Model
{
    protected $fillable = [
        'nama_bank',
        'no_rekening',
        'atas_nama',
        'qris_gambar',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'nama_bank' => config('services.bank.nama_bank'),
            'no_rekening' => config('services.bank.no_rekening'),
            'atas_nama' => config('services.bank.atas_nama'),
            'qris_gambar' => config('services.qris.gambar'),
        ]);
    }
}
