<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;

class SiswaIzinController extends Controller
{
    public function index()
    {
        // Pengaman anti-bobol sesi siswa
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        // Cari data siswa berdasarkan username (yang berisi NISN)
        $usernameLogin = session('username');
        $siswa = Siswa::where('nisn', $usernameLogin)->first();

        $riwayatIzin = collect();
        if ($siswa) {
            // Ambil riwayat izin berdasarkan id_siswa yang valid
            $riwayatIzin = LeaveRequest::where('student_id', $siswa->id_siswa)->get();
        }

        return view('siswa.izin.index', compact('riwayatIzin'));
    }

    public function store(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis!']);
        }

        // Cari data siswa berdasarkan username (yang berisi NISN)
        $usernameLogin = session('username');
        $siswa = Siswa::where('nisn', $usernameLogin)->first();

        if (!$siswa) {
            return redirect()->back()->withErrors(['error' => 'Data master siswa dengan NISN tersebut tidak ditemukan!']);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'document' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $docPath = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = time() . '_' . $file->hashName(); // nama & ekstensi dibuat sistem, bukan dari klien
            $file->move(public_path('uploads/izin'), $filename);
            $docPath = 'uploads/izin/' . $filename;
        }

        LeaveRequest::create([
            'student_id' => $siswa->id_siswa, // Menggunakan id_siswa yang tepat dari tabel siswa
            'applicant_id' => session('id_user'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'document_path' => $docPath,
            'status_approval' => 'Pending',
        ]);

        return redirect()->back()->with('success', 'Pengajuan izin berhasil dikirim dan menunggu verifikasi Wali Kelas/BK.');
    }
}