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
        Schema::create('kartu_pelajar_visual_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('kartu_pelajar_batches')->cascadeOnDelete();
            // ID_FILE yang cocok dengan nama file yang diupload (mis. "KP-000123"), null kalau
            // nama filenya tidak sesuai pola manapun yang dikenal di batch ini (tidak dikenali).
            $table->string('id_file')->nullable();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('status'); // 'matched' | 'orphan'
            $table->timestamps();

            $table->index(['batch_id', 'id_file']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_pelajar_visual_files');
    }
};
