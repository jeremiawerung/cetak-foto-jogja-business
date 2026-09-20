<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PrintOrder extends Model
{
    public const STATUSES = [
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    public const METODE_AMBIL = [
        'ambil_toko' => 'Ambil di Toko',
        'dikirim' => 'Dikirim',
    ];

    public const METODE_BAYAR = [
        'bayar_toko' => 'Bayar di Toko',
        'transfer' => 'Transfer Bank',
        'qris' => 'QRIS',
    ];

    public const STATUS_PEMBAYARAN = [
        'belum_bayar' => 'Belum Bayar',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'lunas' => 'Lunas',
        'ditolak' => 'Bukti Ditolak',
    ];

    protected $fillable = [
        'nomor_pesanan',
        'estimasi_harga',
        'berat_total',
        'biaya_ongkir',
        'ongkir_label',
        'resi',
        'nama',
        'no_hp',
        'catatan',
        'status',
        'is_custom',
        'metode_ambil',
        'alamat_pengiriman',
        'metode_bayar',
        'bukti_transfer',
        'status_pembayaran',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PrintOrderItem::class);
    }

    public function totalPembayaran(): int
    {
        return $this->estimasi_harga + $this->biaya_ongkir;
    }

    public static function generateNomorPesanan(): string
    {
        do {
            $nomor = 'CFJ-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (self::where('nomor_pesanan', $nomor)->exists());

        return $nomor;
    }
}
