<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifikasi_siswas', function (Blueprint $table) {
            // Field dinamis (non-core) per proyek disimpan di sini sebagai {key: value} -
            // kolom agama/jenis_kelamin/tempat_lahir/tanggal_lahir/alamat TETAP ADA dulu
            // (belum dihapus) sampai data sudah dipindahkan & diverifikasi lewat command
            // verifikasi-siswa:migrasi-field-dinamis.
            $table->json('data')->nullable()->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('verifikasi_siswas', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
