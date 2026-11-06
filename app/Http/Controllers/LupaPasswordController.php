<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Lupa password tanpa email:
 * 1. Pengguna mengirim permintaan (NISN / NIP) dari halaman login.
 * 2. Waka Kurikulum melihat permintaan, lalu menentukan password baru sesuai keinginan.
 * 3. Waka juga bisa langsung mereset password akun mana pun secara mandiri.
 */
class LupaPasswordController extends Controller
{
    /**
     * Memastikan tabel permintaan_reset_password sudah tersedia agar tidak crash saat diakses.
     */
    private function ensureTableExists(): void
    {
        try {
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
        } catch (\Throwable $e) {
            // Abaikan jika sudah ada atau permissions terbatas
        }
    }

    // ==========================================
    // SISI PENGGUNA (terbuka, tanpa login)
    // ==========================================

    public function form()
    {
        return view('auth.lupa-password');
    }

    public function kirim(Request $request)
    {
        $this->ensureTableExists();

        $request->validate([
            'username' => 'required|string|max:50',
            'kontak'   => 'nullable|string|max:30',
        ], [
            'username.required' => 'NISN / NIP wajib diisi.',
        ]);

        // Maksimal 3 permintaan per 10 menit dari satu alamat IP
        $kunci = 'lupa-password|' . $request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 3)) {
            $menit = (int) ceil(RateLimiter::availableIn($kunci) / 60);
            return back()
                ->withErrors(['username' => "Terlalu banyak permintaan. Coba lagi dalam {$menit} menit."])
                ->onlyInput('username', 'kontak');
        }
        RateLimiter::hit($kunci, 600);

        $user = DB::table('users')->where('username', $request->username)->first();

        if ($user) {
            $sudahAda = DB::table('permintaan_reset_password')
                ->where('username', $user->username)
                ->where('status', 'Menunggu')
                ->exists();

            if (!$sudahAda) {
                DB::table('permintaan_reset_password')->insert([
                    'username'   => $user->username,
                    'kontak'     => $request->kontak,
                    'status'     => 'Menunggu',
                    'created_at' => now(),
                ]);
            }
        }

        // Pesan sama, baik username terdaftar maupun tidak, supaya daftar akun tidak bisa ditebak
        return redirect()->route('lupa-password')->with(
            'success',
            'Permintaan diterima. Jika NISN / NIP terdaftar, permintaan sudah diteruskan ke Waka Kurikulum. Hubungi beliau untuk mengambil password baru.'
        );
    }

    // ==========================================
    // SISI WAKA KURIKULUM (peran 4, dijaga di routes)
    // ==========================================

    public function index()
    {
        $this->ensureTableExists();

        try {
            $menunggu = DB::table('permintaan_reset_password as p')
                ->leftJoin('users as u', 'u.username', '=', 'p.username')
                ->where('p.status', 'Menunggu')
                ->orderBy('p.created_at')
                ->select('p.id', 'p.username', 'p.kontak', 'p.created_at', 'u.nama_lengkap', 'u.id_role')
                ->get();

            $riwayat = DB::table('permintaan_reset_password as p')
                ->leftJoin('users as u', 'u.username', '=', 'p.username')
                ->where('p.status', '!=', 'Menunggu')
                ->orderByDesc('p.diproses_at')
                ->limit(10)
                ->select('p.username', 'p.status', 'p.diproses_at', 'u.nama_lengkap', 'u.id_role')
                ->get();

            $users = DB::table('users')->orderBy('nama_lengkap')->get();
        } catch (\Throwable $e) {
            $menunggu = collect();
            $riwayat  = collect();
            $users    = collect();
        }

        return view('waka.reset_password', compact('menunggu', 'riwayat', 'users'));
    }

    /**
     * Memproses permintaan reset dengan password yang ditentukan sendiri oleh Waka.
     */
    public function proses(Request $request, $id)
    {
        $this->ensureTableExists();

        $request->validate([
            'password_baru' => 'required|string|min:6',
        ], [
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min'      => 'Password baru minimal 6 karakter.',
        ]);

        $permintaan = DB::table('permintaan_reset_password')
            ->where('id', $id)->where('status', 'Menunggu')->first();

        if (!$permintaan) {
            return redirect()->route('reset.index')
                ->withErrors(['error' => 'Permintaan tidak ditemukan atau sudah diproses.']);
        }

        $user = DB::table('users')->where('username', $permintaan->username)->first();

        if (!$user) {
            DB::table('permintaan_reset_password')->where('id', $permintaan->id)
                ->update(['status' => 'Ditolak', 'diproses_at' => now()]);

            return redirect()->route('reset.index')
                ->withErrors(['error' => 'Akun dengan username itu tidak ditemukan, permintaan ditolak.']);
        }

        $passwordBaru = $request->password_baru;

        DB::transaction(function () use ($user, $permintaan, $passwordBaru) {
            DB::table('users')->where('id_user', $user->id_user)
                ->update(['password_hash' => Hash::make($passwordBaru)]);

            DB::table('permintaan_reset_password')->where('id', $permintaan->id)
                ->update(['status' => 'Selesai', 'diproses_at' => now()]);
        });

        return redirect()->route('reset.index')->with('password_baru', [
            'nama'     => $user->nama_lengkap,
            'username' => $user->username,
            'password' => $passwordBaru,
        ]);
    }

    /**
     * Reset password akun langsung tanpa menunggu permintaan pengguna.
     */
    public function resetManual(Request $request)
    {
        $request->validate([
            'username'      => 'required|exists:users,username',
            'password_baru' => 'required|string|min:6',
        ], [
            'username.required'      => 'Pilih akun yang ingin direset.',
            'username.exists'        => 'Akun pengguna tidak ditemukan.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min'      => 'Password baru minimal 6 karakter.',
        ]);

        $user = DB::table('users')->where('username', $request->username)->first();

        DB::table('users')->where('id_user', $user->id_user)
            ->update(['password_hash' => Hash::make($request->password_baru)]);

        return redirect()->route('reset.index')->with('password_baru', [
            'nama'     => $user->nama_lengkap,
            'username' => $user->username,
            'password' => $request->password_baru,
        ]);
    }

    public function tolak($id)
    {
        $this->ensureTableExists();

        $jumlah = DB::table('permintaan_reset_password')
            ->where('id', $id)->where('status', 'Menunggu')
            ->update(['status' => 'Ditolak', 'diproses_at' => now()]);

        if ($jumlah === 0) {
            return redirect()->route('reset.index')
                ->withErrors(['error' => 'Permintaan tidak ditemukan atau sudah diproses.']);
        }

        return redirect()->route('reset.index')->with('success', 'Permintaan reset password ditolak.');
    }
}
