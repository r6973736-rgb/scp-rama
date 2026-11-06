<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjaga halaman berdasarkan peran (id_role) di sesi.
 * Dipakai di routes/web.php, contoh: CekRole::class . ':4' atau CekRole::class . ':2,3,4,7'
 *
 * Peran: 1 Admin, 2 Guru, 3 Wali Kelas, 4 Waka Kurikulum, 5 Siswa, 7 BK
 */
class CekRole
{
    /** Halaman awal tiap peran (tujuan pengalihan kalau mengakses halaman yang bukan haknya). */
    private const HALAMAN_AWAL = [
        4 => 'waka.dashboard',
        3 => 'kesiswaan.izin.index',
        2 => 'guru.dashboard',
        7 => 'kesiswaan.izin.index',
        5 => 'siswa.izin.index',
    ];

    public function handle(Request $request, Closure $next, string ...$peranBoleh): Response
    {
        // 1. Belum login
        if (!$request->session()->has('id_user')) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        // 2. Peran termasuk yang diizinkan
        $peran = (int) $request->session()->get('id_role');
        if (in_array($peran, array_map('intval', $peranBoleh), true)) {
            return $next($request);
        }

        // 3. Peran tidak berhak: kembalikan ke halaman awal perannya sendiri
        $tujuan = self::HALAMAN_AWAL[$peran] ?? null;

        if ($tujuan === null) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Peran akun tidak dikenali sistem.']);
        }

        return redirect()->route($tujuan)
            ->withErrors(['error' => 'Anda tidak memiliki akses ke halaman itu.']);
    }
}
