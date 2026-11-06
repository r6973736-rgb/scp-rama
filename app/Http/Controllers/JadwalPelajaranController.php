<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JadwalPelajaran;
use App\Models\SystemConfiguration;
use App\Models\Guru;

class JadwalPelajaranController extends Controller
{
    /** Ubah nilai siklus aktif ("1", "Putaran 1", dst) menjadi "Putaran 1" / "Putaran 2". */
    private function siklusAktif(): string
    {
        $config = SystemConfiguration::first();
        $nilai = trim((string) ($config->active_cycle ?? '1'));

        if (in_array($nilai, ['1', '2'], true)) {
            return 'Putaran ' . $nilai;
        }
        return in_array($nilai, ['Putaran 1', 'Putaran 2'], true) ? $nilai : 'Putaran 1';
    }

    private function jadwalTerkunci(): bool
    {
        $config = SystemConfiguration::first();
        return $config && $config->is_schedule_locked;
    }

    /** Guru yang sedang login (null bila akun tidak punya data guru). */
    private function guruLogin(): ?Guru
    {
        return Guru::where('nip', session('username'))->first();
    }

    /** Daftar sesi per hari: ['Senin' => [['sesi_ke'=>1,'mulai'=>'08:00','selesai'=>'08:35'], ...], ...] */
    private function sesiPerHari(): array
    {
        try {
            return DB::table('sesi_jam')->orderBy('sesi_ke')->get()
                ->groupBy('hari')
                ->map(fn ($rows) => $rows->map(fn ($r) => [
                    'sesi_ke' => (int) $r->sesi_ke,
                    'mulai'   => substr($r->jam_mulai, 0, 5),
                    'selesai' => substr($r->jam_selesai, 0, 5),
                ])->values()->all())
                ->all();
        } catch (\Throwable $e) {
            return []; // tabel sesi_jam belum dibuat
        }
    }

    /** Cari nomor sesi mulai & selesai yang cocok dengan jam sebuah jadwal: [mulai, selesai] atau [null, null]. */
    private function cariSesi(array $sesiPerHari, ?string $hari, $jamMulai, $jamSelesai): array
    {
        $daftar = collect($sesiPerHari[$hari] ?? []);
        $a = $daftar->firstWhere('mulai', substr((string) $jamMulai, 0, 5));
        $b = $daftar->firstWhere('selesai', substr((string) $jamSelesai, 0, 5));

        if (!$a || !$b || $b['sesi_ke'] < $a['sesi_ke']) {
            return [null, null];
        }
        return [$a['sesi_ke'], $b['sesi_ke']];
    }

    private function labelSesi(array $sesiPerHari, $jadwal): ?string
    {
        [$a, $b] = $this->cariSesi($sesiPerHari, $jadwal->hari, $jadwal->jam_mulai, $jadwal->jam_selesai);
        if (!$a) {
            return null;
        }
        return $a === $b ? "Jam ke-$a" : "Jam ke-$a s/d $b";
    }

    /** Ubah pilihan sesi (jam ke-) menjadi jam mulai & jam selesai. Kembalian: [jam_mulai, jam_selesai] atau string pesan error. */
    private function waktuDariSesi(Request $request)
    {
        $mulai   = DB::table('sesi_jam')->where('hari', $request->hari)->where('sesi_ke', $request->sesi_mulai)->first();
        $selesai = DB::table('sesi_jam')->where('hari', $request->hari)->where('sesi_ke', $request->sesi_selesai)->first();

        if (!$mulai || !$selesai) {
            return 'Sesi yang dipilih tidak tersedia pada hari ' . $request->hari . '.';
        }
        if ((int) $request->sesi_selesai < (int) $request->sesi_mulai) {
            return 'Jam ke selesai tidak boleh lebih kecil dari jam ke mulai.';
        }
        return [$mulai->jam_mulai, $selesai->jam_selesai];
    }

    private function aturanSesi(): array
    {
        return [
            'hari'         => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat',
            'sesi_mulai'   => 'required|integer|min:1',
            'sesi_selesai' => 'required|integer|min:1',
        ];
    }

    /** Cek bentrok waktu / ruangan / kelas / guru pada hari & putaran yang sama. */
    private function adaBentrok(Request $request, string $putaran, ?int $kecualiId = null): bool
    {
        return JadwalPelajaran::where('hari', $request->hari)
            ->where('cycle_type', $putaran)
            ->when($kecualiId, fn ($q) => $q->where('id_jadwal', '!=', $kecualiId))
            ->where(function ($query) use ($request) {
                $query->where('id_ruangan_default', $request->id_ruangan_default)
                      ->orWhere('id_kelas', $request->id_kelas)
                      ->orWhere('id_guru', $request->id_guru);
            })
            ->where('jam_mulai', '<', $request->jam_selesai)
            ->where('jam_selesai', '>', $request->jam_mulai)
            ->exists();
    }

    private function dataPendukung(): array
    {
        return [
            'sesiPerHari' => $this->sesiPerHari(),
            'tahunAjaran' => DB::table('tahun_ajaran')->get(),
            'kelas'       => DB::table('kelas')->orderByRaw('CAST(id_kelas AS UNSIGNED)')->get(),
            'mapels'      => DB::table('mata_pelajaran')->get(),
            // Guru BK tidak mengajar, jadi tidak ditampilkan sebagai guru pengampu
            'gurus'       => DB::table('guru')
                ->leftJoin('users', 'users.username', '=', 'guru.nip')
                ->where(function ($q) {
                    $q->whereNull('users.id_role')->orWhere('users.id_role', '!=', 7);
                })
                ->select('guru.*')
                ->get(),
            'ruangans'    => DB::table('ruangan')->get(),
        ];
    }

    public function index()
    {
        // Waka melihat semua jadwal; guru hanya jadwalnya sendiri
        $guruLogin = session('id_role') == 4 ? null : $this->guruLogin();

        $jadwals = JadwalPelajaran::with(['tahunAjaran', 'kelas', 'mataPelajaran', 'guru', 'ruangan'])
            ->when($guruLogin, fn ($q) => $q->where('id_guru', $guruLogin->id_guru))
            ->get();

        $pendukung  = $this->dataPendukung();
        $sesiLabel  = $jadwals->mapWithKeys(fn ($j) => [$j->id_jadwal => $this->labelSesi($pendukung['sesiPerHari'], $j)]);

        return view('jadwal.index', array_merge($pendukung, [
            'sesiLabel' => $sesiLabel,
            'jadwals'   => $jadwals,
            'config'    => SystemConfiguration::first(),
            'guruLogin' => $guruLogin,
        ]));
    }

    public function store(Request $request)
    {
        if ($this->jadwalTerkunci()) {
            return redirect()->back()->withErrors(['error' => 'GAGAL! Jadwal telah dikunci oleh Waka Kurikulum.'])->withInput();
        }

        // Selain Waka, guru pengampu selalu = guru yang login
        $guruLogin = session('id_role') == 4 ? null : $this->guruLogin();
        if ($guruLogin) {
            $request->merge(['id_guru' => $guruLogin->id_guru]);
        }

        $request->validate([
            'id_tahun_ajaran'    => 'required',
            'id_kelas'           => 'required',
            'id_mapel'           => 'required',
            'id_guru'            => 'required',
            'id_ruangan_default' => 'required',
        ] + $this->aturanSesi());

        // Jam mulai & selesai ditentukan dari sesi (jam ke-) yang dipilih
        $waktu = $this->waktuDariSesi($request);
        if (is_string($waktu)) {
            return redirect()->back()->withErrors(['error' => $waktu])->withInput();
        }
        $request->merge(['jam_mulai' => $waktu[0], 'jam_selesai' => $waktu[1]]);

        $putaran = $this->siklusAktif();

        if ($this->adaBentrok($request, $putaran)) {
            return redirect()->back()->withErrors(['error' => 'GAGAL! Jadwal bentrok dengan waktu, ruangan, kelas, atau guru lain pada putaran ini.'])->withInput();
        }

        JadwalPelajaran::create([
            'id_tahun_ajaran'    => $request->id_tahun_ajaran,
            'id_kelas'           => $request->id_kelas,
            'id_mapel'           => $request->id_mapel,
            'id_guru'            => $request->id_guru,
            'id_ruangan_default' => $request->id_ruangan_default,
            'hari'               => $request->hari,
            'jam_mulai'          => $request->jam_mulai,
            'jam_selesai'        => $request->jam_selesai,
            'cycle_type'         => $putaran,
        ]);

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $jadwal = JadwalPelajaran::findOrFail($id);
        $guruLogin = session('id_role') == 4 ? null : $this->guruLogin();

        if ($guruLogin && $jadwal->id_guru != $guruLogin->id_guru) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'Anda hanya boleh mengubah jadwal milik Anda sendiri.']);
        }
        if ($this->jadwalTerkunci()) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'Jadwal telah dikunci oleh Waka Kurikulum.']);
        }

        $pendukung = $this->dataPendukung();
        [$sesiMulai, $sesiSelesai] = $this->cariSesi($pendukung['sesiPerHari'], $jadwal->hari, $jadwal->jam_mulai, $jadwal->jam_selesai);

        return view('jadwal.edit', array_merge($pendukung, [
            'jadwal'      => $jadwal,
            'guruLogin'   => $guruLogin,
            'sesiMulai'   => $sesiMulai,
            'sesiSelesai' => $sesiSelesai,
        ]));
    }

    public function update(Request $request, $id)
    {
        $jadwal = JadwalPelajaran::findOrFail($id);
        $guruLogin = session('id_role') == 4 ? null : $this->guruLogin();

        if ($guruLogin && $jadwal->id_guru != $guruLogin->id_guru) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'Anda hanya boleh mengubah jadwal milik Anda sendiri.']);
        }
        if ($this->jadwalTerkunci()) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'GAGAL! Jadwal telah dikunci oleh Waka Kurikulum.']);
        }
        if ($guruLogin) {
            $request->merge(['id_guru' => $guruLogin->id_guru]);
        }

        $request->validate([
            'id_tahun_ajaran'    => 'required',
            'id_kelas'           => 'required',
            'id_mapel'           => 'required',
            'id_guru'            => 'required',
            'id_ruangan_default' => 'required',
        ] + $this->aturanSesi());

        $waktu = $this->waktuDariSesi($request);
        if (is_string($waktu)) {
            return redirect()->back()->withErrors(['error' => $waktu])->withInput();
        }
        $request->merge(['jam_mulai' => $waktu[0], 'jam_selesai' => $waktu[1]]);

        if ($this->adaBentrok($request, $jadwal->cycle_type, $jadwal->id_jadwal)) {
            return redirect()->back()->withErrors(['error' => 'GAGAL! Jadwal bentrok dengan waktu, ruangan, kelas, atau guru lain pada putaran ini.'])->withInput();
        }

        $jadwal->update($request->only([
            'id_tahun_ajaran', 'id_kelas', 'id_mapel', 'id_guru',
            'id_ruangan_default', 'hari', 'jam_mulai', 'jam_selesai',
        ]));

        return redirect()->route('jadwal.index')->with('success', 'Jadwal pelajaran berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $jadwal = JadwalPelajaran::findOrFail($id);
        $guruLogin = session('id_role') == 4 ? null : $this->guruLogin();

        if ($guruLogin && $jadwal->id_guru != $guruLogin->id_guru) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'Anda hanya boleh menghapus jadwal milik Anda sendiri.']);
        }
        if ($this->jadwalTerkunci()) {
            return redirect()->route('jadwal.index')->withErrors(['error' => 'GAGAL! Jadwal telah dikunci oleh Waka Kurikulum.']);
        }

        $jadwal->delete();

        return redirect()->route('jadwal.index')->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }

    // ==========================================
    // MANAJEMEN AKUN, ROLE & KELAS SISWA (KHUSUS WAKA)
    // ==========================================

    public function indexAkun()
    {
        // Username siswa = NISN, jadi digabung dengan tabel siswa untuk menampilkan kelasnya
        $users = DB::table('users')
            ->leftJoin('siswa', 'siswa.nisn', '=', 'users.username')
            ->select('users.*', 'siswa.id_kelas')
            ->orderBy('users.id_role')
            ->orderBy('users.nama_lengkap')
            ->get();

        $kelas = DB::table('kelas')->orderByRaw('CAST(id_kelas AS UNSIGNED)')->get();

        return view('jadwal.manajemen_akun', compact('users', 'kelas'));
    }

    public function storeAkun(Request $request)
    {
        $request->validate([
            'username'     => 'required|unique:users,username',
            'nama_lengkap' => 'required',
            'password'     => 'required|min:6',
            'id_role'      => 'required|in:2,3,4,5,7',
            'id_kelas'     => 'required_if:id_role,5|nullable|exists:kelas,id_kelas',
        ]);

        DB::transaction(function () use ($request) {
            DB::table('users')->insert([
                'username'      => $request->username,
                'password_hash' => bcrypt($request->password),
                'nama_lengkap'  => $request->nama_lengkap,
                'id_role'       => $request->id_role,
                'is_aktif'      => 1,
            ]);

            // Guru, Wali Kelas, Waka, BK: NIP (username) dicatat di tabel guru
            if (in_array((int) $request->id_role, [2, 3, 4, 7], true)) {
                if (!DB::table('guru')->where('nip', $request->username)->exists()) {
                    DB::table('guru')->insert([
                        'nip'       => $request->username,
                        'nama_guru' => $request->nama_lengkap,
                    ]);
                }
            }

            // Siswa: NISN (username) dicatat di tabel siswa lengkap dengan kelasnya
            if ((int) $request->id_role === 5) {
                DB::table('siswa')->updateOrInsert(
                    ['nisn' => $request->username],
                    [
                        'nama_siswa'   => trim($request->nama_lengkap),
                        'id_kelas'     => $request->id_kelas,
                        'status_utama' => 'Aktif',
                    ]
                );
            }
        });

        return redirect()->route('akun.index')->with('success', 'Akun baru berhasil dibuat dan data terdaftar ke sistem!');
    }

    /** Atur / pindahkan kelas siswa. */
    public function updateKelasSiswa(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'id_kelas' => 'required|exists:kelas,id_kelas',
        ]);

        $user = DB::table('users')->where('username', $request->username)->where('id_role', 5)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'Akun siswa tidak ditemukan.']);
        }

        // Bila data siswa belum ada di tabel siswa, dibuatkan otomatis
        DB::table('siswa')->updateOrInsert(
            ['nisn' => $user->username],
            [
                'nama_siswa'   => trim($user->nama_lengkap),
                'id_kelas'     => $request->id_kelas,
                'status_utama' => 'Aktif',
            ]
        );

        return redirect()->route('akun.index')->with('success', 'Kelas siswa berhasil diperbarui!');
    }
}
