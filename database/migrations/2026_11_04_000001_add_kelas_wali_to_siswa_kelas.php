<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom id_kelas ke tabel siswa (relasi siswa → kelas)
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('id_kelas', 20)->nullable()->after('nisn');
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->onDelete('set null');
        });

        // Tambah kolom id_wali_kelas ke tabel kelas (relasi kelas → guru wali kelas)
        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedInteger('id_wali_kelas')->nullable()->after('id_jurusan');
            $table->foreign('id_wali_kelas')->references('id_guru')->on('guru')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropForeign(['id_kelas']);
            $table->dropColumn('id_kelas');
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['id_wali_kelas']);
            $table->dropColumn('id_wali_kelas');
        });
    }
};

