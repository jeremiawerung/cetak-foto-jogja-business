<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemisahan area admin: "super" (akses /internal & /admin, default untuk admin yang
 * sudah ada), "internal" (khusus /internal - Verifikasi Siswa), "katalog" (khusus
 * /admin - Kelola Katalog Cetak Foto). Default 'super' supaya admin yang sudah ada
 * otomatis tetap punya akses penuh tanpa perlu diubah manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('super');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
