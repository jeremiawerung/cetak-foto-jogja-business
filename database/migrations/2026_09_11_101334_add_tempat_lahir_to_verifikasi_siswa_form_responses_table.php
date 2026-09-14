<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifikasi_siswa_form_responses', function (Blueprint $table) {
            // Google Form (sample nyata) menggabungkan Tempat + Tanggal Lahir jadi 1
            // pertanyaan ("Yogyakarta,1 September 2013") - tempat_lahir diekstrak
            // terpisah dari string itu supaya bisa menutup roster yang kosong.
            $table->string('tempat_lahir')->nullable()->after('kelas');
        });
    }

    public function down(): void
    {
        Schema::table('verifikasi_siswa_form_responses', function (Blueprint $table) {
            $table->dropColumn('tempat_lahir');
        });
    }
};
