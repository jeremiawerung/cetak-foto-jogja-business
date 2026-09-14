<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kartu_pelajar_rows', function (Blueprint $table) {
            // Field pelengkap untuk ekspor (Prompt 5) - tidak melalui audit/normalisasi Prompt 1,
            // sekadar tangkap-apa-adanya (trim) karena bukan bagian dari logika matching.
            $table->string('tempat_lahir')->nullable()->after('tanggal_lahir');
            $table->string('agama')->nullable()->after('alamat');
            $table->string('jenis_kelamin')->nullable()->after('agama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kartu_pelajar_rows', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'agama', 'jenis_kelamin']);
        });
    }
};
