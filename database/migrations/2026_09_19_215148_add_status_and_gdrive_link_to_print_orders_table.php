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
            $table->string('status')->default('baru')->after('file_paths');
            $table->string('gdrive_link')->nullable()->after('file_paths');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_orders', function (Blueprint $table) {
            $table->dropColumn(['status', 'gdrive_link']);
        });
    }
};
