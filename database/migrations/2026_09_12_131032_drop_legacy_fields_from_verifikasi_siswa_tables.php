<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langkah TERAKHIR migrasi ke field dinamis — dijalankan terpisah dari migrasi yang
 * menambah kolom `data` supaya ada jarak aman untuk mundur (lihat command
 * verifikasi-siswa:migrasi-field-dinamis). Sebelum menjalankan ini, pastikan:
 * 1. Command backfill sudah dijalankan untuk SEMUA proyek (kolom `data` terisi).
 * 2. Semua kode sudah membaca/menulis lewat `data`, bukan kolom-kolom ini lagi.
 * 3. Sudah diverifikasi (mis. bandingkan hasil ekspor CSV sebelum/sesudah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifikasi_siswas', function (Blueprint $table) {
            $table->dropColumn(['jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'agama']);
        });

        Schema::table('verifikasi_siswa_form_responses', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'tanggal_lahir', 'alamat', 'agama', 'jenis_kelamin']);
        });
    }

    public function down(): void
    {
        Schema::table('verifikasi_siswas', function (Blueprint $table) {
            $table->string('jenis_kelamin')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('agama')->nullable();
        });

        Schema::table('verifikasi_siswa_form_responses', function (Blueprint $table) {
            $table->string('tempat_lahir')->nullable();
            $table->string('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('agama')->nullable();
            $table->string('jenis_kelamin')->nullable();
        });
    }
};
