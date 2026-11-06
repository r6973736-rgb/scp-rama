<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Role
        Schema::create('role', function (Blueprint $table) {
            $table->increments('id_role');
            $table->string('nama_role', 30);
        });

        // 2. Users
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255);
            $table->string('nama_lengkap', 100);
            $table->string('no_hp', 20)->nullable();
            $table->unsignedInteger('id_role');
            $table->tinyInteger('is_aktif')->default(1);

            $table->foreign('id_role')->references('id_role')->on('role')->onDelete('cascade');
        });

        // 3. Jurusan
        Schema::create('jurusan', function (Blueprint $table) {
            $table->string('id_jurusan', 10)->primary();
            $table->string('nama_jurusan', 100);
        });

        // 4. Kelas
        Schema::create('kelas', function (Blueprint $table) {
            $table->string('id_kelas', 20)->primary();
            $table->enum('tingkat', ['X', 'XI', 'XII']);
            $table->string('id_jurusan', 10)->nullable();

            $table->foreign('id_jurusan')->references('id_jurusan')->on('jurusan')->onDelete('set null');
        });

        // 5. Tahun Ajaran
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->string('id_tahun_ajaran', 20)->primary();
            $table->string('nama_semester', 50);
            $table->tinyInteger('is_aktif')->default(0);
        });

        // 6. Mata Pelajaran
        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->string('id_mapel', 20)->primary();
            $table->string('nama_mapel', 100);
        });

        // 7. Guru
        Schema::create('guru', function (Blueprint $table) {
            $table->increments('id_guru');
            $table->string('nip', 30)->nullable()->unique();
            $table->string('nama_guru', 100);
        });

        // 8. Ruangan
        Schema::create('ruangan', function (Blueprint $table) {
            $table->string('id_ruangan', 20)->primary();
            $table->string('nama_ruangan', 50);
            $table->enum('jenis', ['Teori', 'Laboratorium', 'Bengkel'])->default('Teori');
        });

        // 9. Siswa
        Schema::create('siswa', function (Blueprint $table) {
            $table->increments('id_siswa');
            $table->string('nisn', 20)->unique();
            $table->string('nama_siswa', 100);
            $table->enum('status_utama', ['Aktif', 'Lulus', 'Keluar', 'DO'])->default('Aktif');
        });

        // 10. Jadwal Pelajaran
        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
            $table->increments('id_jadwal');
            $table->string('id_tahun_ajaran', 20);
            $table->string('id_kelas', 20);
            $table->string('id_mapel', 20);
            $table->unsignedInteger('id_guru');
            $table->string('id_ruangan_default', 20);
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->enum('cycle_type', ['Putaran 1', 'Putaran 2'])->default('Putaran 1');

            $table->foreign('id_tahun_ajaran')->references('id_tahun_ajaran')->on('tahun_ajaran')->onDelete('cascade');
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->onDelete('cascade');
            $table->foreign('id_mapel')->references('id_mapel')->on('mata_pelajaran')->onDelete('cascade');
            $table->foreign('id_guru')->references('id_guru')->on('guru')->onDelete('cascade');
            $table->foreign('id_ruangan_default')->references('id_ruangan')->on('ruangan')->onDelete('cascade');
        });

        // 11. Presensi Header
        Schema::create('presensi_header', function (Blueprint $table) {
            $table->increments('id_presensi');
            $table->unsignedInteger('id_jadwal');
            $table->date('tanggal');
            $table->string('id_ruangan_digunakan', 20);
            $table->unsignedInteger('id_guru_pengajar');
            $table->text('catatan_kbm')->nullable();

            $table->foreign('id_jadwal')->references('id_jadwal')->on('jadwal_pelajaran')->onDelete('cascade');
            $table->foreign('id_ruangan_digunakan')->references('id_ruangan')->on('ruangan')->onDelete('cascade');
            $table->foreign('id_guru_pengajar')->references('id_guru')->on('guru')->onDelete('cascade');
        });

        // 12. Presensi Detail
        Schema::create('presensi_detail', function (Blueprint $table) {
            $table->increments('id_detail');
            $table->unsignedInteger('id_presensi');
            $table->unsignedInteger('id_siswa');
            $table->enum('status_kehadiran', ['Hadir', 'Sakit', 'Izin', 'Alfa']);
            $table->string('keterangan', 255)->nullable();

            $table->foreign('id_presensi')->references('id_presensi')->on('presensi_header')->onDelete('cascade');
            $table->foreign('id_siswa')->references('id_siswa')->on('siswa')->onDelete('cascade');
        });

        // 13. Tabel Sessions (Tambahan agar error hilang)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('presensi_detail');
        Schema::dropIfExists('presensi_header');
        Schema::dropIfExists('jadwal_pelajaran');
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('ruangan');
        Schema::dropIfExists('guru');
        Schema::dropIfExists('mata_pelajaran');
        Schema::dropIfExists('tahun_ajaran');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('jurusan');
        Schema::dropIfExists('users');
        Schema::dropIfExists('role');
    }
};