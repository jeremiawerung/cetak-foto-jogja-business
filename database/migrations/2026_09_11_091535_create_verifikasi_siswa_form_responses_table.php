<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_siswa_form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('verifikasi_siswa_proyeks')->cascadeOnDelete();
            $table->unsignedInteger('sheet_row_number');
            $table->timestamp('submitted_at')->nullable();
            $table->string('kelas')->nullable();
            $table->string('nama')->nullable();
            $table->string('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('agama')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('nis')->nullable();
            $table->string('nisn')->nullable();
            $table->string('status')->default('unmatched');
            // Bukan foreign key DB - tabel verifikasi_siswas dibuat setelah tabel ini (saling menunjuk).
            $table->unsignedBigInteger('matched_siswa_id')->nullable();
            $table->json('discrepancies')->nullable();
            $table->timestamps();

            $table->unique(['proyek_id', 'sheet_row_number'], 'vs_form_responses_proyek_row_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_siswa_form_responses');
    }
};
