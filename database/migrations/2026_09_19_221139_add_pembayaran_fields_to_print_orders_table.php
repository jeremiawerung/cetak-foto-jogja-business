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
        Schema::table('print_orders', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('gdrive_link');
            $table->string('metode_ambil')->nullable()->after('is_custom');
            $table->text('alamat_pengiriman')->nullable()->after('metode_ambil');
            $table->string('metode_bayar')->nullable()->after('alamat_pengiriman');
            $table->string('bukti_transfer')->nullable()->after('metode_bayar');
            $table->string('status_pembayaran')->default('belum_bayar')->after('bukti_transfer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_custom',
                'metode_ambil',
                'alamat_pengiriman',
                'metode_bayar',
                'bukti_transfer',
                'status_pembayaran',
            ]);
        });
    }
};
