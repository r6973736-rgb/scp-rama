<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Siswa;
use App\Models\Guru;

class ProfileController extends Controller
{
    public function index()
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $idUser = session('id_user');
        $user = DB::table('users')->where('id_user', $idUser)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Data akun tidak ditemukan.']);
        }

        $namaPeran = [
            1 => 'Admin',
            2 => 'Guru Pengampu',
            3 => 'Wali Kelas',
            4 => 'Waka Kurikulum',
            5 => 'Siswa',
            7 => 'Guru BK',
        ];
        $peran = $namaPeran[$user->id_role] ?? 'Pengguna';

        $siswa = null;
        $guru = null;
        $kelasWali = collect();
        $totalIzin = 0;
        $totalJadwal = 0;
        $totalJurnal = 0;

        if ($user->id_role == 5) {
            // Role Siswa
            $siswa = Siswa::with('kelas.waliKelas')->where('nisn', $user->username)->first();
            $totalIzin = DB::table('leave_requests')->where('applicant_id', $user->id_user)->count();
        } else {
            // Role Guru, Wali Kelas, Waka, atau BK
            $guru = Guru::where('nip', $user->username)->first();
            if ($guru) {
                $kelasWali = DB::table('kelas')->where('id_wali_kelas', $guru->id_guru)->get();
                $jadwalIds = DB::table('jadwal_pelajaran')->where('id_guru', $guru->id_guru)->pluck('id_jadwal');
                $totalJadwal = $jadwalIds->count();
                $totalJurnal = DB::table('journals')->whereIn('schedule_id', $jadwalIds)->count();
            }
        }

        return view('profil.index', compact(
            'user',
            'peran',
            'siswa',
            'guru',
            'kelasWali',
            'totalIzin',
            'totalJadwal',
            'totalJurnal'
        ));
    }

    public function update(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $idUser = session('id_user');
        $user = DB::table('users')->where('id_user', $idUser)->first();

        $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'no_hp'        => 'nullable|string|max:20',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
        ]);

        DB::table('users')->where('id_user', $idUser)->update([
            'nama_lengkap' => $request->nama_lengkap,
            'no_hp'        => $request->no_hp,
        ]);

        // Sinkronisasi ke tabel siswa bila akun siswa
        if ($user->id_role == 5) {
            DB::table('siswa')->where('nisn', $user->username)->update([
                'nama_siswa' => $request->nama_lengkap,
            ]);
        }

        // Sinkronisasi ke tabel guru bila akun guru / wali / waka / bk
        if (in_array($user->id_role, [2, 3, 4, 7])) {
            DB::table('guru')->where('nip', $user->username)->update([
                'nama_guru' => $request->nama_lengkap,
            ]);
        }

        session(['nama_lengkap' => $request->nama_lengkap]);

        return redirect()->route('profil.index')->with('success', 'Data profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $idUser = session('id_user');
        $user = DB::table('users')->where('id_user', $idUser)->first();

        $request->validate([
            'password_lama'       => 'required',
            'password_baru'       => 'required|string|min:6',
            'password_konfirmasi' => 'required|same:password_baru',
        ], [
            'password_lama.required'       => 'Password saat ini wajib diisi.',
            'password_baru.required'       => 'Password baru wajib diisi.',
            'password_baru.min'            => 'Password baru minimal 6 karakter.',
            'password_konfirmasi.required' => 'Ulangi password baru Anda.',
            'password_konfirmasi.same'     => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (!Hash::check($request->password_lama, $user->password_hash)) {
            return redirect()->back()->withErrors(['password_lama' => 'Password saat ini salah!']);
        }

        DB::table('users')->where('id_user', $idUser)->update([
            'password_hash' => Hash::make($request->password_baru),
        ]);

        return redirect()->route('profil.index')->with('success', 'Password Anda berhasil diubah!');
    }
}

