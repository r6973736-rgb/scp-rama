<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

if (!function_exists('formatKelas')) {
    /**
     * Menghilangkan 1 atau 2 nomor terakhir beserta tanda strip pada id_kelas.
     * Contoh: "AKL 1 - 27" -> "AKL 1", "AKL 1 - 5" -> "AKL 1".
     * Jika tingkat diberikan, menghasilkan: "XI AKL 1" atau "X AKL 1".
     */
    function formatKelas($idKelas, $tingkat = null): string
    {
        if (!$idKelas) {
            return '-';
        }

        // Hapus tanda strip diikuti 1 atau 2 digit nomor di akhir string
        $bersih = trim(preg_replace('/\s*-\s*\d+$/', '', $idKelas));

        // Jika tingkat disediakan dan belum ada di depan, tambahkan tingkat
        if ($tingkat && !str_starts_with($bersih, $tingkat)) {
            return trim($tingkat . ' ' . $bersih);
        }

        return $bersih;
    }
}

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
