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
        Schema::table('kartu_pelajar_visual_files', function (Blueprint $table) {
            // Thumbnail JPEG hasil ekstraksi dari dalam file .psd (kalau ada) - null berarti
            // file bukan PSD atau tidak punya thumbnail tertanam, tampilan fallback ke ikon generik.
            $table->string('thumbnail_path')->nullable()->after('stored_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kartu_pelajar_visual_files', function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });
    }
};
