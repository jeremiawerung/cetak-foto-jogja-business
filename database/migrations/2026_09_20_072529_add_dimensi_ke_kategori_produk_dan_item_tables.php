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
        Schema::table('kategori_produks', function (Blueprint $table) {
            $table->decimal('panjang', 8, 2)->nullable()->after('satuan_label');
            $table->decimal('lebar', 8, 2)->nullable()->after('panjang');
            $table->unsignedInteger('berat')->nullable()->after('lebar');
        });

        Schema::table('kategori_produk_items', function (Blueprint $table) {
            $table->decimal('panjang', 8, 2)->nullable()->after('rincian');
            $table->decimal('lebar', 8, 2)->nullable()->after('panjang');
            $table->unsignedInteger('berat')->nullable()->after('lebar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kategori_produks', function (Blueprint $table) {
            $table->dropColumn(['panjang', 'lebar', 'berat']);
        });

        Schema::table('kategori_produk_items', function (Blueprint $table) {
            $table->dropColumn(['panjang', 'lebar', 'berat']);
        });
    }
};
