<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // Batasi percobaan login (anti tebak-password): 5 kali per menit per username & alamat IP
        $kunci = 'login:' . strtolower($request->username) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $detik = RateLimiter::availableIn($kunci);
            return back()->withErrors(['username' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik."])
                ->onlyInput('username');
        }

        $user = DB::table('users')->where('username', $request->username)->first();

        if ($user && Hash::check($request->password, $user->password_hash)) {
            // Akun yang dinonaktifkan tidak boleh masuk
            if (isset($user->is_aktif) && !$user->is_aktif) {
                return back()->withErrors(['username' => 'Akun Anda tidak aktif. Hubungi Waka Kurikulum.'])
                    ->onlyInput('username');
            }

            RateLimiter::clear($kunci);
            $request->session()->regenerate(); // cegah session fixation

            session([
                'id_user' => $user->id_user,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'id_role' => $user->id_role
            ]);

            // REDIRECT BERDASARKAN ID_ROLE SECARA SPESIFIK
            // 4 = Waka Kurikulum
            // 3 = Wali Kelas (ke verifikasi izin kesiswaan)
            // 2 = Guru, 7 = BK (hanya verifikasi izin)
            // 5 = Siswa
            switch ($user->id_role) {
                case 4:
                    return redirect()->route('waka.dashboard')->with('success', 'Berhasil login sebagai Waka Kurikulum!');
                case 3:
                    return redirect()->route('kesiswaan.izin.index')->with('success', 'Berhasil login sebagai Wali Kelas!');
                case 2:
                    return redirect()->route('guru.dashboard')->with('success', 'Berhasil login!');
                case 7:
                    return redirect()->route('kesiswaan.izin.index')->with('success', 'Berhasil login sebagai Guru BK!');
                case 5:
                    return redirect()->route('siswa.izin.index')->with('success', 'Berhasil login sebagai Siswa!');
                default:
                    return redirect()->route('login')->withErrors(['username' => 'Role pengguna tidak dikenali sistem.']);
            }
        }

        RateLimiter::hit($kunci, 60);

        return back()->withErrors(['username' => 'Username atau password salah.'])->onlyInput('username');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            // Username siswa = NISN, dan NISN harus sudah ada di data siswa
            'username' => 'required|string|max:50|unique:users,username|exists:siswa,nisn',
            'nama_lengkap' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'no_hp' => 'nullable|string|max:15',
        ], [
            'username.unique' => 'NISN ini sudah terdaftar. Silakan login.',
            'username.exists' => 'NISN tidak ditemukan di data siswa. Hubungi Waka Kurikulum agar data Anda didaftarkan.',
        ]);

        DB::table('users')->insert([
            'username' => $request->username,
            'nama_lengkap' => $request->nama_lengkap,
            'password_hash' => Hash::make($request->password),
            'no_hp' => $request->no_hp,
            'id_role' => 5, // Otomatis diatur sebagai Siswa
            'is_aktif' => 1,
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Berhasil logout!');
    }
}