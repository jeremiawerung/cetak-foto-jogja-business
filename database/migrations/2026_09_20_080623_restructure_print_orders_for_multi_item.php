<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Satu order sekarang bisa berisi banyak item (beda kategori/varian sekaligus),
     * jadi kategori/varian/jumlah/file_paths/gdrive_link pindah ke print_order_items.
     * Fitur order cetak foto baru dibangun & belum dipakai produksi, jadi kolom lama
     * langsung dihapus tanpa migrasi data.
     */
    public function up(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'varian', 'jumlah', 'file_paths', 'gdrive_link']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->string('kategori')->nullable();
            $table->string('varian')->nullable();
            $table->unsignedInteger('jumlah')->nullable();
            $table->json('file_paths')->nullable();
            $table->string('gdrive_link')->nullable();
        });
    }
};
