<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LeaveRequest;
use App\Support\LaporanIzinHarian;

class KesiswaanController extends Controller
{
    public function index()
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $idRole   = (int) session('id_role');
        $username = session('username');

        // Wali kelas (role 3): hanya tampilkan izin dari siswa di kelasnya
        // BK (role 7)        : tampilkan semua izin siswa
        if ($idRole === 3) {
            $guru = DB::table('guru')->where('nip', $username)->first();

            if ($guru) {
                // Kelas yang diampuh wali kelas ini
                $kelasWali = DB::table('kelas')
                    ->where('id_wali_kelas', $guru->id_guru)
                    ->pluck('id_kelas');

                // Siswa di kelas tersebut
                $idSiswaKelas = DB::table('siswa')
                    ->whereIn('id_kelas', $kelasWali)
                    ->pluck('id_siswa');

                $pendingLeaves = LeaveRequest::with('student')
                    ->whereIn('student_id', $idSiswaKelas)
                    ->latest()
                    ->get();
            } else {
                $pendingLeaves = collect();
            }
        } else {
            // BK — tampilkan semua
            $pendingLeaves = LeaveRequest::with('student')->latest()->get();
        }

        // Laporan izin siswa hari ini otomatis masuk ke wali kelas (kelasnya) dan BK (semua kelas)
        $laporanHariIni = LaporanIzinHarian::ambil(date('Y-m-d'));
        $tanggalHariIni = LaporanIzinHarian::tanggalIndonesia(date('Y-m-d'));

        return view('kesiswaan.izin.index', compact('pendingLeaves', 'laporanHariIni', 'tanggalHariIni'));
    }

    public function updateStatus(Request $request, $id)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $request->validate([
            'status_approval'  => 'required|in:Approved,Rejected',
            // Alasan wajib diisi bila izin ditolak
            'rejection_reason' => 'required_if:status_approval,Rejected|nullable|string|min:5|max:500',
        ], [
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min'         => 'Alasan penolakan minimal 5 karakter.',
            'rejection_reason.max'         => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $leave = LeaveRequest::findOrFail($id);

        // Wali kelas hanya boleh memutuskan izin siswa di kelas yang diampunya (BK: semua kelas)
        $kelasBatas = LaporanIzinHarian::kelasBatas();
        if ($kelasBatas !== null) {
            $kelasSiswa = (string) DB::table('siswa')->where('id_siswa', $leave->student_id)->value('id_kelas');
            if (!in_array($kelasSiswa, array_map('strval', $kelasBatas), true)) {
                return redirect()->back()->withErrors(['error' => 'Anda hanya dapat memverifikasi izin siswa di kelas yang Anda ampu.']);
            }
        }

        $leave->update([
            'status_approval'  => $request->status_approval,
            'verified_by'      => session('id_user'),
            // Alasan hanya disimpan untuk penolakan; bila disetujui dikosongkan
            'rejection_reason' => $request->status_approval === 'Rejected'
                ? trim($request->rejection_reason)
                : null,
        ]);

        $pesan = $request->status_approval === 'Rejected'
            ? 'Pengajuan izin ditolak dan alasan penolakan tersimpan.'
            : 'Pengajuan izin disetujui.';

        return redirect()->back()->with('success', $pesan);
    }
}
