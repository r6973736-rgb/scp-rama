<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Journal;
use App\Models\PresensiHeader;
use App\Models\PresensiDetail;
use App\Models\Siswa;
use App\Support\IzinDisetujui;
use App\Support\LaporanIzinHarian;

/**
 * Presensi siswa TIDAK berdiri sendiri: dibuka setelah guru mengisi jurnal mengajar.
 * Tanggal dan jadwal presensi selalu mengikuti jurnal yang bersangkutan.
 */
class PresensiController extends Controller
{
    private function getGuruLogin(): ?\stdClass
    {
        return DB::table('guru')->where('nip', session('username'))->first();
    }

    // GET /presensi : tidak ada halaman terpisah, kembali ke Jurnal mengajar
    public function index()
    {
        return redirect()->route('jurnal.index');
    }

    // Ambil jurnal milik guru yang login (null bila bukan miliknya)
    private function jurnalMilikGuru($idJurnal, \stdClass $guru): ?Journal
    {
        $jurnal = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.ruangan'])->find($idJurnal);

        if (!$jurnal || !$jurnal->jadwal || $jurnal->jadwal->id_guru != $guru->id_guru) {
            return null;
        }
        return $jurnal;
    }

    // Siswa dengan izin/sakit yang sudah disetujui pada tanggal tertentu
    private function izinPadaTanggal(string $tanggal)
    {
        return IzinDisetujui::padaTanggal($tanggal);
    }

    // GET /presensi/isi?id_jurnal=X : form presensi untuk jurnal yang baru diisi
    public function isi(Request $request)
    {
        $guru = $this->getGuruLogin();
        if (!$guru) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Data guru tidak ditemukan.']);
        }

        $jurnal = $this->jurnalMilikGuru($request->id_jurnal, $guru);
        if (!$jurnal) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Isi jurnal mengajar terlebih dahulu, lalu presensi akan muncul.']);
        }

        $jadwal  = $jurnal->jadwal;
        $tanggal = substr($jurnal->date, 0, 10);

        $siswaList = Siswa::where('id_kelas', $jadwal->id_kelas)
            ->where('status_utama', 'Aktif')
            ->orderBy('nama_siswa')
            ->get();

        $sudahIsi = PresensiHeader::where('id_jadwal', $jadwal->id_jadwal)
            ->where('tanggal', $tanggal)
            ->first();

        $izinHariIni = $this->izinPadaTanggal($tanggal);

        return view('presensi.isi', compact('jurnal', 'jadwal', 'siswaList', 'sudahIsi', 'izinHariIni'));
    }

    // POST /presensi/simpan
    public function simpan(Request $request)
    {
        $guru = $this->getGuruLogin();
        if (!$guru) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Data guru tidak ditemukan.']);
        }

        $request->validate([
            'id_jurnal'            => 'required|exists:journals,id',
            'id_ruangan_digunakan' => 'required|exists:ruangan,id_ruangan',
            'catatan_kbm'          => 'nullable|string|max:500',
            'kehadiran'            => 'required|array',
            'kehadiran.*'          => 'required|in:Hadir,Sakit,Izin,Alfa',
        ], [
            'kehadiran.required' => 'Minimal harus ada satu siswa untuk diisi kehadirannya.',
            'kehadiran.*.in'     => 'Status kehadiran tidak valid.',
        ]);

        $jurnal = $this->jurnalMilikGuru($request->id_jurnal, $guru);
        if (!$jurnal) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jurnal tidak ditemukan atau bukan milik Anda.']);
        }

        $jadwal  = $jurnal->jadwal;
        $tanggal = substr($jurnal->date, 0, 10);

        // Cegah presensi ganda untuk jadwal & tanggal yang sama
        if (PresensiHeader::where('id_jadwal', $jadwal->id_jadwal)->where('tanggal', $tanggal)->exists()) {
            return redirect()->route('jurnal.index')
                ->withErrors(['error' => 'Presensi untuk jurnal ini sudah pernah diisi.']);
        }

        // Hanya siswa yang memang terdaftar di kelas jadwal ini yang dicatat
        $idSiswaKelas = Siswa::where('id_kelas', $jadwal->id_kelas)->pluck('id_siswa')->all();

        DB::beginTransaction();
        try {
            $header = PresensiHeader::create([
                'id_jadwal'            => $jadwal->id_jadwal,
                'tanggal'              => $tanggal,
                'id_ruangan_digunakan' => $request->id_ruangan_digunakan,
                'id_guru_pengajar'     => $guru->id_guru,
                'catatan_kbm'          => $request->catatan_kbm,
            ]);

            foreach ($request->kehadiran as $idSiswa => $status) {
                if (!in_array((int) $idSiswa, $idSiswaKelas, true)) {
                    continue;
                }
                PresensiDetail::create([
                    'id_presensi'      => $header->id_presensi,
                    'id_siswa'         => $idSiswa,
                    'status_kehadiran' => $status,
                    'keterangan'       => $request->keterangan[$idSiswa] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('jurnal.index')->with('success', 'Jurnal dan presensi siswa berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Gagal menyimpan presensi: ' . $e->getMessage()])
                ->withInput();
        }
    }

    // GET /presensi/{id} : detail satu sesi presensi
    public function detail($id)
    {
        $header = PresensiHeader::with([
            'jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.ruangan',
            'guruPengajar', 'ruangan',
            'detail.siswa',
        ])->findOrFail($id);

        // Waka boleh melihat semua; guru hanya presensi yang diajarnya; wali kelas juga presensi kelasnya
        $peran = (int) session('id_role');
        if ($peran !== 4) {
            $guru  = $this->getGuruLogin();
            $boleh = $guru && (
                $header->id_guru_pengajar == $guru->id_guru
                || optional($header->jadwal)->id_guru == $guru->id_guru
            );

            if (!$boleh && $peran === 3) {
                $kelasWali = array_map('strval', LaporanIzinHarian::kelasBatas() ?? []);
                $boleh = in_array((string) optional($header->jadwal)->id_kelas, $kelasWali, true);
            }

            if (!$boleh) {
                $tujuan = $peran === 3 ? 'kesiswaan.izin.index' : 'jurnal.index';
                return redirect()->route($tujuan)->withErrors(['error' => 'Anda tidak berhak melihat presensi ini.']);
            }
        }

        return view('presensi.detail', compact('header'));
    }

    // GET /rekap-presensi : rekap kehadiran per siswa dalam satu kelas
    public function rekap(Request $request)
    {
        $request->validate([
            'id_kelas' => 'nullable|string|max:20',
            'dari'     => 'nullable|date',
            'sampai'   => 'nullable|date|after_or_equal:dari',
        ]);

        $kelasBatas  = LaporanIzinHarian::kelasBatas(); // null = semua kelas
        $daftarKelas = DB::table('kelas')
            ->when($kelasBatas !== null, fn ($q) => $q->whereIn('id_kelas', $kelasBatas))
            ->orderByRaw('CAST(id_kelas AS UNSIGNED)')
            ->get();

        $idKelas = $request->input('id_kelas');
        if (!$idKelas || !$daftarKelas->contains('id_kelas', $idKelas)) {
            $idKelas = optional($daftarKelas->first())->id_kelas;
        }

        $dari   = $request->input('dari') ?: date('Y-m-01');
        $sampai = $request->input('sampai') ?: date('Y-m-d');

        $rekap = collect();
        if ($idKelas) {
            $rekap = DB::table('siswa as s')
                ->leftJoin('presensi_detail as d', 'd.id_siswa', '=', 's.id_siswa')
                ->leftJoin('presensi_header as h', function ($join) use ($dari, $sampai) {
                    $join->on('h.id_presensi', '=', 'd.id_presensi')
                         ->where('h.tanggal', '>=', $dari)
                         ->where('h.tanggal', '<=', $sampai);
                })
                ->where('s.id_kelas', $idKelas)
                ->where('s.status_utama', 'Aktif')
                ->groupBy('s.id_siswa', 's.nisn', 's.nama_siswa')
                ->orderBy('s.nama_siswa')
                ->selectRaw("s.id_siswa, s.nisn, s.nama_siswa,
                    COALESCE(SUM(CASE WHEN h.id_presensi IS NOT NULL AND d.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END), 0) AS hadir,
                    COALESCE(SUM(CASE WHEN h.id_presensi IS NOT NULL AND d.status_kehadiran = 'Sakit' THEN 1 ELSE 0 END), 0) AS sakit,
                    COALESCE(SUM(CASE WHEN h.id_presensi IS NOT NULL AND d.status_kehadiran = 'Izin'  THEN 1 ELSE 0 END), 0) AS izin,
                    COALESCE(SUM(CASE WHEN h.id_presensi IS NOT NULL AND d.status_kehadiran = 'Alfa'  THEN 1 ELSE 0 END), 0) AS alfa")
                ->get()
                ->map(function ($r) {
                    $r->total  = $r->hadir + $r->sakit + $r->izin + $r->alfa;
                    $r->persen = $r->total > 0 ? round($r->hadir / $r->total * 100) : null;
                    return $r;
                });
        }

        $kelasDipilih = $daftarKelas->firstWhere('id_kelas', $idKelas);

        return view('presensi.rekap', compact('rekap', 'daftarKelas', 'idKelas', 'kelasDipilih', 'dari', 'sampai'));
    }
}
