<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifikasi_siswa_form_responses', function (Blueprint $table) {
            $table->json('data')->nullable()->after('discrepancies');
        });
    }

    public function down(): void
    {
        Schema::table('verifikasi_siswa_form_responses', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
