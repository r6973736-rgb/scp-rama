<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\PresensiHeader;
use App\Models\PresensiDetail;
use App\Support\IzinDisetujui;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JurnalController extends Controller
{
    private function guruLogin(): ?Guru
    {
        return Guru::where('nip', session('username'))->first();
    }

    private function tolakTanpaGuru()
    {
        $tujuan = match ((int) session('id_role')) {
            4 => 'waka.dashboard',
            3 => 'kesiswaan.izin.index',
            default => 'guru.dashboard',
        };
        return redirect()->route($tujuan)->withErrors(['error' => 'Data guru tidak ditemukan untuk akun ini.']);
    }

    /** Jurnal hanya boleh diedit pada hari jurnal itu dibuat (tanggal jurnal = hari ini). */
    private function jurnalHariIni(Journal $jurnal): bool
    {
        return substr((string) $jurnal->date, 0, 10) === date('Y-m-d');
    }

    private function tolakBukanHariIni()
    {
        return redirect()->route('jurnal.index')
            ->withErrors(['error' => 'Jurnal hanya bisa diedit pada hari yang sama dengan tanggal jurnal.']);
    }

    public function index()
    {
        $guru = $this->guruLogin();
        if (!$guru) {
            return $this->tolakTanpaGuru();
        }

        $jadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'tahunAjaran', 'ruangan'])
            ->where('id_guru', $guru->id_guru)
            ->get();

        $jurnalList = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran'])
            ->whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Presensi yang sudah diisi per jurnal (kunci: id_jadwal|tanggal)
        $presensiMap = PresensiHeader::with('detail')
            ->whereIn('id_jadwal', $jadwals->pluck('id_jadwal'))
            ->get()
            ->keyBy(fn ($h) => $h->id_jadwal . '|' . substr($h->tanggal, 0, 10));

        // Jadwal yang jurnalnya sudah diisi hari ini (tidak boleh diisi dua kali)
        $sudahHariIni = Journal::whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
            ->whereDate('date', date('Y-m-d'))
            ->pluck('schedule_id')
            ->all();

        // Daftar siswa aktif tiap jadwal, supaya form presensi langsung muncul
        // begitu guru memilih jadwal (status awal Sakit/Izin dari izin yang sudah disetujui)
        $kolomKelasAda = Schema::hasColumn('siswa', 'id_kelas');
        $siswaPerJadwal = [];
        if ($kolomKelasAda) {
            $izin = IzinDisetujui::padaTanggal(date('Y-m-d'));
            $siswaPerKelas = [];
            foreach ($jadwals->pluck('id_kelas')->unique() as $idKelas) {
                $siswaPerKelas[$idKelas] = Siswa::where('id_kelas', $idKelas)
                    ->where('status_utama', 'Aktif')
                    ->orderBy('nama_siswa')
                    ->get(['id_siswa', 'nisn', 'nama_siswa']);
            }
            foreach ($jadwals as $j) {
                $siswaPerJadwal[$j->id_jadwal] = ($siswaPerKelas[$j->id_kelas] ?? collect())
                    ->map(fn ($s) => [
                        'id'     => $s->id_siswa,
                        'nisn'   => $s->nisn,
                        'nama'   => trim($s->nama_siswa),
                        'status' => $izin[$s->id_siswa]['status'] ?? 'Hadir',
                        'ket'    => $izin[$s->id_siswa]['keterangan'] ?? '',
                    ])
                    ->values()
                    ->all();
            }
        }

        return view('jurnal.index', compact(
            'jadwals', 'jurnalList', 'guru', 'presensiMap',
            'sudahHariIni', 'siswaPerJadwal', 'kolomKelasAda'
        ));
    }

    /**
     * Simpan jurnal DAN presensi sekaligus dalam satu transaksi.
     * Jurnal tidak akan tersimpan tanpa presensi siswa (bila kelas punya siswa aktif),
     * dan sebaliknya, sehingga tidak ada data setengah jadi.
     */
    public function store(Request $request)
    {
        $guru = $this->guruLogin();
        if (!$guru) {
            return $this->tolakTanpaGuru();
        }

        if (!Schema::hasColumn('siswa', 'id_kelas')) {
            return redirect()->back()->withErrors([
                'error' => 'Kolom siswa.id_kelas belum ada di database. Jalankan "php artisan migrate" terlebih dahulu.',
            ])->withInput();
        }

        $request->validate([
            'schedule_id'       => 'required',
            'topic_description' => 'required',
            'photo'             => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'catatan_kbm'       => 'nullable|string|max:500',
            'kehadiran'         => 'nullable|array',
            'kehadiran.*'       => 'in:Hadir,Sakit,Izin,Alfa',
            'keterangan'        => 'nullable|array',
            'keterangan.*'      => 'nullable|string|max:255',
        ], [
            'kehadiran.*.in' => 'Status kehadiran tidak valid.',
        ]);

        // Jadwal harus milik guru yang login
        $jadwal = JadwalPelajaran::where('id_jadwal', $request->schedule_id)
            ->where('id_guru', $guru->id_guru)->first();
        if (!$jadwal) {
            return redirect()->back()->withErrors(['error' => 'Jadwal tidak valid atau bukan jadwal mengajar Anda.'])->withInput();
        }

        $tanggal = date('Y-m-d');

        // Satu jadwal hanya boleh punya satu jurnal per hari
        if (Journal::where('schedule_id', $jadwal->id_jadwal)->whereDate('date', $tanggal)->exists()) {
            return redirect()->back()
                ->withErrors(['error' => 'Jurnal untuk jadwal ini hari ini sudah diisi. Gunakan Edit atau Isi presensi di riwayat jurnal.'])
                ->withInput();
        }

        // Semua siswa aktif di kelas ini wajib punya status kehadiran
        $idSiswaAktif = Siswa::where('id_kelas', $jadwal->id_kelas)
            ->where('status_utama', 'Aktif')
            ->pluck('id_siswa')
            ->all();

        $kehadiran  = $request->input('kehadiran', []);
        $keterangan = $request->input('keterangan', []);

        $belumDiisi = array_filter($idSiswaAktif, fn ($id) => !isset($kehadiran[$id]));
        if (count($belumDiisi) > 0) {
            return redirect()->back()
                ->withErrors(['error' => 'Presensi belum lengkap: ' . count($belumDiisi) . ' siswa belum diberi status kehadiran.'])
                ->withInput();
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->hashName(); // nama & ekstensi dibuat sistem, bukan dari klien
            $file->move(public_path('uploads/jurnal'), $filename);
            $photoPath = 'uploads/jurnal/' . $filename;
        }

        DB::beginTransaction();
        try {
            DB::table('journals')->insert([
                'schedule_id'       => $jadwal->id_jadwal,
                'date'              => $tanggal,
                'topic_description' => $request->topic_description,
                'photo_path'        => $photoPath,
            ]);

            $jumlahSiswa = 0;
            $sudahAdaPresensi = PresensiHeader::where('id_jadwal', $jadwal->id_jadwal)
                ->where('tanggal', $tanggal)->exists();

            if (count($idSiswaAktif) > 0 && !$sudahAdaPresensi) {
                $header = PresensiHeader::create([
                    'id_jadwal'            => $jadwal->id_jadwal,
                    'tanggal'              => $tanggal,
                    'id_ruangan_digunakan' => $jadwal->id_ruangan_default,
                    'id_guru_pengajar'     => $guru->id_guru,
                    'catatan_kbm'          => $request->catatan_kbm,
                ]);

                foreach ($idSiswaAktif as $idSiswa) {
                    PresensiDetail::create([
                        'id_presensi'      => $header->id_presensi,
                        'id_siswa'         => $idSiswa,
                        'status_kehadiran' => $kehadiran[$idSiswa],
                        'keterangan'       => $keterangan[$idSiswa] ?? null,
                    ]);
                    $jumlahSiswa++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($photoPath && file_exists(public_path($photoPath))) {
                @unlink(public_path($photoPath)); // jangan menyisakan foto tanpa jurnal
            }
            return redirect()->back()
                ->withErrors(['error' => 'Gagal menyimpan jurnal dan presensi: ' . $e->getMessage()])
                ->withInput();
        }

        if ($jumlahSiswa === 0) {
            return redirect()->route('jurnal.index')
                ->with('success', 'Jurnal tersimpan. Kelas ini belum punya siswa aktif, atur kelas siswa di menu Kelola akun (Waka).');
        }

        return redirect()->route('jurnal.index')
            ->with('success', 'Jurnal dan presensi ' . $jumlahSiswa . ' siswa berhasil disimpan!');
    }

    public function edit($id)
    {
        $guru = $this->guruLogin();
        if (!$guru) {
            return $this->tolakTanpaGuru();
        }

        $jurnal = Journal::with('jadwal')->findOrFail($id);
        if (optional($jurnal->jadwal)->id_guru != $guru->id_guru) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Anda hanya boleh mengubah jurnal milik Anda sendiri.']);
        }

        if (!$this->jurnalHariIni($jurnal)) {
            return $this->tolakBukanHariIni();
        }

        $jadwals = JadwalPelajaran::where('id_guru', $guru->id_guru)->get();

        return view('jurnal.edit', compact('jurnal', 'jadwals'));
    }

    public function update(Request $request, $id)
    {
        $guru = $this->guruLogin();
        if (!$guru) {
            return $this->tolakTanpaGuru();
        }

        $jurnal = Journal::with('jadwal')->findOrFail($id);
        if (optional($jurnal->jadwal)->id_guru != $guru->id_guru) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Anda hanya boleh mengubah jurnal milik Anda sendiri.']);
        }

        if (!$this->jurnalHariIni($jurnal)) {
            return $this->tolakBukanHariIni();
        }

        $request->validate([
            'schedule_id'       => 'required',
            'topic_description' => 'required',
            'photo'             => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $jadwalBaru = JadwalPelajaran::where('id_jadwal', $request->schedule_id)
            ->where('id_guru', $guru->id_guru)->first();
        if (!$jadwalBaru) {
            return redirect()->back()->withErrors(['error' => 'Jadwal tidak valid atau bukan jadwal mengajar Anda.'])->withInput();
        }

        $data = [
            'schedule_id'       => $jadwalBaru->id_jadwal,
            'topic_description' => $request->topic_description,
        ];

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->hashName(); // nama & ekstensi dibuat sistem, bukan dari klien
            $file->move(public_path('uploads/jurnal'), $filename);
            $data['photo_path'] = 'uploads/jurnal/' . $filename;
        }

        $jurnal->update($data);

        return redirect()->route('jurnal.index')->with('success', 'Jurnal mengajar berhasil diperbarui!');
    }

    /** Rekap seluruh jurnal untuk Waka Kurikulum */
    public function rekapWaka()
    {
        $jurnalList = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.guru'])
            ->orderBy('date', 'desc')
            ->get();

        $presensiMap = PresensiHeader::with('detail')->get()
            ->keyBy(fn ($h) => $h->id_jadwal . '|' . substr($h->tanggal, 0, 10));

        return view('jurnal.rekap_waka', compact('jurnalList', 'presensiMap'));
    }
}
