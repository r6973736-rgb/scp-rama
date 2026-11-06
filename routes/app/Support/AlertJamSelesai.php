<?php

namespace App\Support;

use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\PresensiHeader;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Alert untuk Waka: kelas yang akan selesai <= 30 menit lagi
 * tetapi jurnal / presensinya belum diisi guru.
 *
 * Status tiap jadwal hari ini:
 *  - alert        : sedang berlangsung, sisa <= BATAS_MENIT, jurnal/presensi belum lengkap
 *  - berlangsung  : sedang berlangsung (belum masuk jendela alert, atau sudah lengkap)
 *  - terlewat     : jam sudah selesai tetapi jurnal/presensi tidak lengkap
 */
class AlertJamSelesai
{
    public const BATAS_MENIT = 30;
    public const ZONA = 'Asia/Jakarta'; // WIB, agar tidak ikut config app.timezone = UTC

    private const NAMA_HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    private static function siklusAktif(): string
    {
        $nilai = trim((string) (SystemConfiguration::first()->active_cycle ?? '1'));
        if (in_array($nilai, ['1', '2'], true)) {
            return 'Putaran ' . $nilai;
        }
        return in_array($nilai, ['Putaran 1', 'Putaran 2'], true) ? $nilai : 'Putaran 1';
    }

    public static function ambil(?Carbon $sekarang = null): array
    {
        $now     = ($sekarang ?? Carbon::now(self::ZONA))->copy()->setTimezone(self::ZONA);
        $tanggal = $now->toDateString();
        $hari    = self::NAMA_HARI[$now->dayOfWeekIso];

        $jadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'guru', 'ruangan'])
            ->where('hari', $hari)
            ->where('cycle_type', self::siklusAktif())
            ->whereNotNull('jam_mulai')
            ->whereNotNull('jam_selesai')
            ->orderBy('jam_selesai')
            ->get();

        $ids = $jadwals->pluck('id_jadwal');

        $adaJurnal = Journal::whereIn('schedule_id', $ids)->whereDate('date', $tanggal)
            ->pluck('schedule_id')->flip();
        $adaPresensi = PresensiHeader::whereIn('id_jadwal', $ids)->whereDate('tanggal', $tanggal)
            ->pluck('id_jadwal')->flip();

        // Kelas tanpa siswa aktif tidak wajib presensi (sama seperti aturan di JurnalController)
        try {
            $siswaAktif = DB::table('siswa')->where('status_utama', 'Aktif')
                ->select('id_kelas', DB::raw('COUNT(*) as n'))->groupBy('id_kelas')
                ->pluck('n', 'id_kelas');
        } catch (\Throwable $e) {
            $siswaAktif = collect();
        }

        $alert = $berlangsung = $terlewat = [];

        foreach ($jadwals as $j) {
            $mulai   = Carbon::parse($tanggal . ' ' . $j->jam_mulai, self::ZONA);
            $selesai = Carbon::parse($tanggal . ' ' . $j->jam_selesai, self::ZONA);

            if ($now->lt($mulai)) {
                continue; // belum dimulai
            }

            $jurnalOk    = $adaJurnal->has($j->id_jadwal);
            $perluAbsen  = ($siswaAktif[$j->id_kelas] ?? 0) > 0;
            $presensiOk  = !$perluAbsen || $adaPresensi->has($j->id_jadwal);
            $lengkap     = $jurnalOk && $presensiOk;

            $sisa = $selesai->timestamp - $now->timestamp; // detik, negatif bila sudah lewat

            $item = [
                'id'          => $j->id_jadwal,
                'kelas'       => $j->kelas?->nama_kelas ?? $j->id_kelas,
                'mapel'       => $j->mataPelajaran->nama_mapel ?? $j->id_mapel,
                'guru'        => $j->guru->nama_guru ?? '-',
                'ruangan'     => $j->ruangan->nama_ruangan ?? $j->id_ruangan_default,
                'mulai'       => substr($j->jam_mulai, 0, 5),
                'selesai'     => substr($j->jam_selesai, 0, 5),
                'sisa_detik'  => $sisa,
                'jurnal_ok'   => $jurnalOk,
                'presensi_ok' => $presensiOk,
                'lengkap'     => $lengkap,
            ];

            if ($sisa <= 0) {
                if (!$lengkap) {
                    $terlewat[] = $item;
                }
            } elseif ($sisa <= self::BATAS_MENIT * 60 && !$lengkap) {
                $alert[] = $item;
            } else {
                $berlangsung[] = $item;
            }
        }

        usort($alert, fn ($a, $b) => $a['sisa_detik'] <=> $b['sisa_detik']);

        return [
            'batas_menit' => self::BATAS_MENIT,
            'hari'        => $hari,
            'waktu'       => $now->format('H:i:s'),
            'alert'       => $alert,
            'berlangsung' => $berlangsung,
            'terlewat'    => $terlewat,
        ];
    }
}
