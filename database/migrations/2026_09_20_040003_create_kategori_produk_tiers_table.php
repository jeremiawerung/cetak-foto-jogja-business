<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_produk_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_produk_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min');
            $table->unsignedInteger('max')->nullable();
            $table->unsignedInteger('harga');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_produk_tiers');
    }
};
