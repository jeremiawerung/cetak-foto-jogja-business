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
        Schema::create('print_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_order_id')->constrained()->cascadeOnDelete();
            $table->string('kategori');
            $table->string('kategori_label');
            $table->string('varian')->nullable();
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('harga_satuan')->default(0);
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('berat_satuan')->nullable();
            $table->unsignedInteger('berat_subtotal')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->json('file_paths')->nullable();
            $table->string('gdrive_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_order_items');
    }
};
