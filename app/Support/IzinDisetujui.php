<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Izin/sakit siswa yang sudah disetujui pada suatu tanggal.
 * Dipakai untuk mengisi status awal presensi secara otomatis (Sakit / Izin).
 */
class IzinDisetujui
{
    /** @return Collection  id_siswa => ['status' => 'Sakit'|'Izin', 'keterangan' => string] */
    public static function padaTanggal(string $tanggal): Collection
    {
        return DB::table('leave_requests')
            ->where('status_approval', 'Approved')
            ->whereDate('start_date', '<=', $tanggal)
            ->whereDate('end_date', '>=', $tanggal)
            ->get(['student_id', 'reason'])
            ->mapWithKeys(fn ($l) => [
                $l->student_id => [
                    'status'     => stripos($l->reason, 'sakit') !== false ? 'Sakit' : 'Izin',
                    'keterangan' => 'Izin disetujui: ' . $l->reason,
                ],
            ]);
    }
}
