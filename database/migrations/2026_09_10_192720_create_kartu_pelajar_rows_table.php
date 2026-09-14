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
        Schema::create('kartu_pelajar_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('kartu_pelajar_batches')->cascadeOnDelete();

            // Asal baris di file sumber (untuk ditelusuri kembali kalau perlu).
            $table->string('source'); // 'excel' | 'gform'
            $table->unsignedInteger('source_row_number');

            // Hasil audit & normalisasi dari Prompt 1.
            $table->string('status'); // clean | normalized_with_warning | needs_manual_review | duplicate_collapsed
            $table->json('reasons')->nullable();
            $table->string('nama')->nullable();
            $table->string('kelas')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nis')->nullable();
            $table->string('nisn')->nullable();
            $table->text('alamat')->nullable();

            // Kandidat kunci pencocokan (dipakai mesin matching di Prompt 3 - belum diimplementasikan di sini).
            $table->string('matching_key_nisn')->nullable();
            $table->string('matching_key_composite')->nullable();

            // Status pencocokan: unprocessed | auto_matched | conflict | manual_resolved
            $table->string('matching_status')->default('unprocessed');
            $table->string('matched_via')->nullable(); // 'nisn' | 'fallback', hanya terisi kalau auto_matched
            $table->foreignId('matched_row_id')->nullable()->constrained('kartu_pelajar_rows')->nullOnDelete();

            $table->timestamps();

            $table->index(['batch_id', 'source']);
            $table->index('matching_key_nisn');
            $table->index('matching_key_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_pelajar_rows');
    }
};
