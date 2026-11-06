<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sumber data tunggal untuk laporan izin siswa harian.
 * Dipakai oleh halaman laporan, halaman Verifikasi izin, dan lencana sidebar,
 * sehingga laporan otomatis "masuk" ke wali kelas dan BK tanpa perlu dikirim manual.
 *
 * Cakupan data mengikuti peran yang sedang login:
 *  - Wali kelas (3) : hanya siswa di kelas yang ia ampu
 *  - BK (7), Waka (4): semua kelas
 */
class LaporanIzinHarian
{
    public const STATUS = [
        'Approved' => 'Disetujui',
        'Pending'  => 'Menunggu',
        'Rejected' => 'Ditolak',
        'Semua'    => 'Semua status',
    ];

    /** Daftar id_kelas yang boleh dilihat, atau null bila boleh semua kelas. */
    public static function kelasBatas(): ?array
    {
        if ((int) session('id_role') !== 3) {
            return null;
        }

        $guru = DB::table('guru')->where('nip', session('username'))->first();
        if (!$guru) {
            return [];
        }

        return DB::table('kelas')->where('id_wali_kelas', $guru->id_guru)->pluck('id_kelas')->all();
    }

    /** Siswa yang izin/sakit pada $tanggal, sesuai cakupan peran. */
    public static function ambil(string $tanggal, string $status = 'Approved', ?string $idKelas = null): Collection
    {
        $kelasBatas = self::kelasBatas();

        return DB::table('leave_requests as l')
            ->join('siswa as s', 's.id_siswa', '=', 'l.student_id')
            ->leftJoin('kelas as k', 'k.id_kelas', '=', 's.id_kelas')
            ->whereDate('l.start_date', '<=', $tanggal)
            ->whereDate('l.end_date', '>=', $tanggal)
            ->when($status !== 'Semua', fn ($q) => $q->where('l.status_approval', $status))
            ->when($kelasBatas !== null, fn ($q) => $q->whereIn('s.id_kelas', $kelasBatas))
            ->when($idKelas, fn ($q) => $q->where('s.id_kelas', $idKelas))
            ->orderBy('k.tingkat')
            ->orderBy('k.id_jurusan')
            ->orderBy('s.nama_siswa')
            ->select(
                's.nisn', 's.nama_siswa', 's.id_kelas',
                'k.tingkat', 'k.id_jurusan',
                'l.reason', 'l.start_date', 'l.end_date', 'l.document_path',
                'l.status_approval', 'l.rejection_reason'
            )
            ->get()
            ->map(function ($r) {
                $r->kelas_label = $r->id_kelas ? trim($r->tingkat . ' ' . $r->id_jurusan) : 'Belum ada kelas';
                $r->jenis       = stripos($r->reason, 'sakit') !== false ? 'Sakit' : 'Izin';
                $r->status_teks = self::STATUS[$r->status_approval] ?? 'Menunggu';
                return $r;
            });
    }

    /** Jumlah siswa yang izin/sakit (disetujui) hari ini, untuk lencana sidebar. */
    public static function jumlahHariIni(): int
    {
        return self::ambil(date('Y-m-d'))->count();
    }

    public static function tanggalIndonesia(string $tanggal): string
    {
        $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $t = Carbon::parse($tanggal);

        return $hari[$t->dayOfWeek] . ', ' . $t->day . ' ' . $bulan[$t->month] . ' ' . $t->year;
    }
}
