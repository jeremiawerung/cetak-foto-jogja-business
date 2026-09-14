<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('verifikasi_siswa_proyeks')->cascadeOnDelete();
            $table->string('kelas')->nullable();
            $table->string('nama')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('nis')->nullable();
            $table->string('nisn')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('agama')->nullable();
            $table->string('status')->default('belum_isi');
            // Bukan foreign key DB (verifikasi_siswa_form_responses dibuat setelah tabel ini,
            // dan tabel itu juga punya kolom balik ke sini) - cukup kolom+index biasa.
            $table->unsignedBigInteger('matched_form_response_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->index(['proyek_id', 'nis']);
            $table->index(['proyek_id', 'nisn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_siswas');
    }
};
