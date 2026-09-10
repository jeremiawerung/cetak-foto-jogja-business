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
        Schema::create('photographer_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_hp');
            $table->string('jenis_acara');
            $table->string('lokasi');
            $table->date('tanggal');
            $table->time('jam');
            $table->unsignedInteger('estimasi_orang')->nullable();
            $table->string('paket')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['tanggal', 'jam']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photographer_bookings');
    }
};
