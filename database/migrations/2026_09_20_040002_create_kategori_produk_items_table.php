<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_produk_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_produk_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('nama');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('harga_normal')->nullable();
            $table->string('rincian')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['kategori_produk_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_produk_items');
    }
};
