<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CekRole;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LupaPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WakaController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\SiswaIzinController;
use App\Http\Controllers\KesiswaanController;
use App\Http\Controllers\LaporanIzinController;
use App\Http\Controllers\ManajemenAkunController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Peran: 1 Admin, 2 Guru, 3 Wali Kelas, 4 Waka Kurikulum, 5 Siswa, 7 BK
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// ROUTE AUTENTIKASI & LOGOUT
// ==========================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/register-process', [AuthController::class, 'register'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==========================================
// LUPA PASSWORD (terbuka, tanpa login)
// ==========================================
Route::get('/lupa-password', [LupaPasswordController::class, 'form'])->name('lupa-password');
Route::post('/lupa-password', [LupaPasswordController::class, 'kirim'])->name('lupa-password.kirim');


// ==========================================
// PROFIL (semua peran yang sudah login)
// ==========================================
Route::middleware(CekRole::class . ':1,2,3,4,5,7')->group(function () {
    Route::get('/profil', [ProfileController::class, 'index'])->name('profil.index');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::post('/profil/password', [ProfileController::class, 'updatePassword'])->name('profil.updatePassword');
});


// ==========================================
// WAKA KURIKULUM (peran 4)
// ==========================================
Route::middleware(CekRole::class . ':4')->group(function () {
    Route::get('/waka/dashboard', [WakaController::class, 'dashboard'])->name('waka.dashboard');
    Route::get('/waka/alert-data', [WakaController::class, 'alertData'])->name('waka.alert.data');
    Route::post('/waka/config/update', [WakaController::class, 'updateConfig'])->name('waka.config.update');
    Route::get('/waka/jurnal/rekap', [JurnalController::class, 'rekapWaka'])->name('waka.jurnal.rekap');

    // Manajemen akun (guru pengampu, wali kelas, guru BK, waka kurikulum, siswa)
    Route::get('/manajemen-akun', [ManajemenAkunController::class, 'index'])->name('akun.index');
    Route::post('/manajemen-akun', [ManajemenAkunController::class, 'store'])->name('akun.store');
    Route::get('/manajemen-akun/template', [ManajemenAkunController::class, 'template'])->name('akun.template');
    Route::post('/manajemen-akun/import', [ManajemenAkunController::class, 'import'])->name('akun.import');
    Route::put('/manajemen-akun/{id}', [ManajemenAkunController::class, 'update'])->whereNumber('id')->name('akun.update');
    Route::post('/manajemen-akun/{id}/password', [ManajemenAkunController::class, 'resetPassword'])->whereNumber('id')->name('akun.password');
    Route::post('/manajemen-akun/{id}/status', [ManajemenAkunController::class, 'ubahStatus'])->whereNumber('id')->name('akun.status');
    Route::post('/manajemen-akun/hapus-semua', [ManajemenAkunController::class, 'hapusSemua'])->name('akun.hapusSemua');

    // Reset password
    Route::get('/waka/reset-password', [LupaPasswordController::class, 'index'])->name('reset.index');
    Route::post('/waka/reset-password/manual', [LupaPasswordController::class, 'resetManual'])->name('reset.manual');
    Route::post('/waka/reset-password/{id}/proses', [LupaPasswordController::class, 'proses'])->name('reset.proses');
    Route::post('/waka/reset-password/{id}/tolak', [LupaPasswordController::class, 'tolak'])->name('reset.tolak');

});


// ==========================================
// GURU (peran 2)
// ==========================================
Route::get('/guru/dashboard', [GuruController::class, 'dashboard'])
    ->middleware(CekRole::class . ':2')->name('guru.dashboard');


// ==========================================
// JADWAL, JURNAL, PRESENSI (guru, wali kelas, waka; BK tidak mengajar)
// ==========================================
Route::middleware(CekRole::class . ':2,3,4')->group(function () {
    // Jadwal
    Route::get('/jadwal', [JadwalPelajaranController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal/store', [JadwalPelajaranController::class, 'store'])->name('jadwal.store');
    Route::get('/jadwal/{id}/edit', [JadwalPelajaranController::class, 'edit'])->whereNumber('id')->name('jadwal.edit');
    Route::put('/jadwal/{id}', [JadwalPelajaranController::class, 'update'])->whereNumber('id')->name('jadwal.update');
    Route::delete('/jadwal/{id}', [JadwalPelajaranController::class, 'destroy'])->whereNumber('id')->name('jadwal.destroy');

    // Jurnal
    Route::get('/jurnal', [JurnalController::class, 'index'])->name('jurnal.index');
    Route::post('/jurnal/store', [JurnalController::class, 'store'])->name('jurnal.store');
    Route::get('/jurnal/{id}/edit', [JurnalController::class, 'edit'])->whereNumber('id')->name('jurnal.edit');
    Route::put('/jurnal/{id}', [JurnalController::class, 'update'])->whereNumber('id')->name('jurnal.update');

    // Presensi siswa (absen per kelas)
    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
    Route::get('/presensi/isi', [PresensiController::class, 'isi'])->name('presensi.isi');
    Route::post('/presensi/simpan', [PresensiController::class, 'simpan'])->name('presensi.simpan');
    Route::get('/presensi/{id}', [PresensiController::class, 'detail'])->whereNumber('id')->name('presensi.detail');
});

// Rekap kehadiran siswa per kelas (wali kelas: kelasnya, Waka: semua kelas)
Route::get('/rekap-presensi', [PresensiController::class, 'rekap'])
    ->middleware(CekRole::class . ':3,4')->name('rekap.presensi');


// ==========================================
// SISWA (PENGAJUAN IZIN / SAKIT)
// ==========================================
Route::middleware(CekRole::class . ':5')->group(function () {
    Route::get('/siswa/izin', [SiswaIzinController::class, 'index'])->name('siswa.izin.index');
    Route::post('/siswa/izin', [SiswaIzinController::class, 'store'])->name('siswa.izin.store');
});


// ==========================================
// KESISWAAN (WALI KELAS & BK) - VERIFIKASI IZIN
// ==========================================
Route::middleware(CekRole::class . ':3,7')->group(function () {
    Route::get('/kesiswaan/izin', [KesiswaanController::class, 'index'])->name('kesiswaan.izin.index');
    Route::post('/kesiswaan/izin/{id}/update', [KesiswaanController::class, 'updateStatus'])->name('kesiswaan.izin.update');
});


// ==========================================
// LAPORAN IZIN SISWA HARIAN (wali kelas, Waka, BK)
// ==========================================
Route::get('/laporan/izin-harian', [LaporanIzinController::class, 'harian'])
    ->middleware(CekRole::class . ':3,4,7')->name('laporan.izin.harian');
