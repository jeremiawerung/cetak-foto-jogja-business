<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_siswa_export_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('verifikasi_siswa_proyeks')->cascadeOnDelete();
            $table->unsignedInteger('urutan')->default(0);
            $table->string('label');
            $table->string('sumber_tipe');
            $table->string('field_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_siswa_export_columns');
    }
};
