<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengaturan_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bank');
            $table->string('no_rekening');
            $table->string('atas_nama');
            $table->string('qris_gambar')->nullable();
            $table->timestamps();
        });

        // Baris tunggal (id=1), diseed dari nilai .env lama supaya perilaku situs
        // tidak berubah begitu deploy - admin baru edit lewat panel kalau perlu.
        DB::table('pengaturan_pembayarans')->insert([
            'nama_bank' => config('services.bank.nama_bank'),
            'no_rekening' => config('services.bank.no_rekening'),
            'atas_nama' => config('services.bank.atas_nama'),
            'qris_gambar' => config('services.qris.gambar'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_pembayarans');
    }
};
