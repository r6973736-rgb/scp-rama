<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\LaporanIzinHarian;

/**
 * Laporan izin siswa harian.
 * Peran: Wali kelas (hanya kelas yang diampu), Guru BK dan Waka Kurikulum (semua kelas).
 */
class LaporanIzinController extends Controller
{
    public function harian(Request $request)
    {
        $request->validate([
            'tanggal'  => 'nullable|date',
            'status'   => 'nullable|in:Approved,Pending,Rejected,Semua',
            'id_kelas' => 'nullable|string|max:20',
        ]);

        $tanggal = $request->input('tanggal') ?: date('Y-m-d');
        $status  = $request->input('status', 'Approved');
        $idKelas = $request->input('id_kelas');

        // Wali kelas hanya melihat kelas yang diampunya (diatur di LaporanIzinHarian)
        $kelasBatas = LaporanIzinHarian::kelasBatas();

        $daftarKelas = DB::table('kelas')
            ->when($kelasBatas !== null, fn ($q) => $q->whereIn('id_kelas', $kelasBatas))
            ->orderByRaw('CAST(id_kelas AS UNSIGNED)')
            ->get();

        $data = LaporanIzinHarian::ambil($tanggal, $status, $idKelas);

        if ($request->query('unduh') === 'csv') {
            return $this->unduhCsv($data, $tanggal);
        }

        return view('laporan.izin_harian', [
            'data'          => $data,
            'tanggal'       => $tanggal,
            'tanggalTeks'   => LaporanIzinHarian::tanggalIndonesia($tanggal),
            'status'        => $status,
            'idKelas'       => $idKelas,
            'daftarKelas'   => $daftarKelas,
            'daftarStatus'  => LaporanIzinHarian::STATUS,
            'jumlahSakit'   => $data->where('jenis', 'Sakit')->count(),
            'jumlahIzin'    => $data->where('jenis', 'Izin')->count(),
            'perKelas'      => $data->groupBy('kelas_label')->map->count(),
            'waliTanpaKelas' => $kelasBatas !== null && count($kelasBatas) === 0,
        ]);
    }

    private function unduhCsv($data, string $tanggal)
    {
        $nama = 'laporan-izin-harian-' . $tanggal . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // agar terbaca benar di Excel
            fputcsv($out, ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Jenis', 'Alasan', 'Mulai', 'Selesai', 'Status', 'Alasan Penolakan']);
            foreach ($data->values() as $i => $r) {
                fputcsv($out, [
                    $i + 1, $r->nisn, trim($r->nama_siswa), $r->kelas_label, $r->jenis,
                    $r->reason, $r->start_date, $r->end_date, $r->status_teks, $r->rejection_reason,
                ]);
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
