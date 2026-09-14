<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_siswa_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('verifikasi_siswa_proyeks')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('tipe')->default('text');
            $table->boolean('is_core')->default(false);
            $table->string('sumber_utama')->default('roster');
            $table->unsignedInteger('urutan')->default(0);
            $table->json('kata_kunci_header')->nullable();
            $table->timestamps();

            $table->unique(['proyek_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_siswa_field_definitions');
    }
};
