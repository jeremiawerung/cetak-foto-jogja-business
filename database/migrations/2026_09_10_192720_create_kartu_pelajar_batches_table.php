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
        Schema::create('kartu_pelajar_batches', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->string('sumber_file_excel')->nullable();
            $table->string('sumber_file_pdf')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_pelajar_batches');
    }
};
