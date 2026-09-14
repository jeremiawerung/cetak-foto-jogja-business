<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_siswa_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyek_id')->constrained('verifikasi_siswa_proyeks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('description');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_siswa_activity_logs');
    }
};
