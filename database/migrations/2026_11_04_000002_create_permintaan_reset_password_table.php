<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permintaan_reset_password')) {
            Schema::create('permintaan_reset_password', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 50);
                $table->string('kontak', 50)->nullable();
                $table->enum('status', ['Menunggu', 'Selesai', 'Ditolak'])->default('Menunggu');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('diproses_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_reset_password');
    }
};

