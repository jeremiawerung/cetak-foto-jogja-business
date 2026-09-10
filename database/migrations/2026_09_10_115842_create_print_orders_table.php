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
        Schema::create('print_orders', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
            $table->string('varian')->nullable();
            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('estimasi_harga')->default(0);
            $table->string('nama')->nullable();
            $table->string('no_hp')->nullable();
            $table->text('catatan')->nullable();
            $table->json('file_paths')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_orders');
    }
};
