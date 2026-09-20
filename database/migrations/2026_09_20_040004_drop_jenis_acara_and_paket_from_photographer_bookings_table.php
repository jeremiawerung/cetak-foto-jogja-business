<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur paket fotografer & jenis acara dihapus - booking sekarang murni reservasi
 * tanggal/jam studio (lihat SewaFotograferController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photographer_bookings', function (Blueprint $table) {
            $table->dropColumn(['jenis_acara', 'paket']);
        });
    }

    public function down(): void
    {
        Schema::table('photographer_bookings', function (Blueprint $table) {
            $table->string('jenis_acara')->nullable();
            $table->string('paket')->nullable();
        });
    }
};
