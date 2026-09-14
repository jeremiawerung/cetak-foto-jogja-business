<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_siswa_proyeks', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('google_sheet_id')->nullable();
            $table->string('google_sheet_range')->nullable();
            $table->unsignedInteger('last_synced_row')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_siswa_proyeks');
    }
};
