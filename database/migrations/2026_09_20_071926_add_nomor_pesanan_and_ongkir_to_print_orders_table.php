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
            $table->string('nomor_pesanan')->nullable()->unique()->after('id');
            $table->unsignedInteger('biaya_ongkir')->default(0)->after('estimasi_harga');
            $table->string('ongkir_label')->nullable()->after('biaya_ongkir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->dropColumn(['nomor_pesanan', 'biaya_ongkir', 'ongkir_label']);
        });
    }
};
