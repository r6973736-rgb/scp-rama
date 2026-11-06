<?php

namespace App\Http\Controllers;

use App\Support\XlsxSederhana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Manajemen akun (khusus Waka Kurikulum).
 * Akun dikelompokkan per peran: guru pengampu, wali kelas, guru BK, waka kurikulum, siswa.
 */
class ManajemenAkunController extends Controller
{
    /** kode tab => [id_role, label] */
    private const TAB = [
        'guru'  => [2, 'Guru pengampu'],
        'wali'  => [3, 'Wali kelas'],
        'bk'    => [7, 'Guru BK'],
        'waka'  => [4, 'Waka kurikulum'],
        'siswa' => [5, 'Siswa'],
    ];

    private const ROLE_STAF = [2, 3, 4, 7];

    public function index(Request $request)
    {
        $peran = (string) $request->query('peran', '');
        $peran = isset(self::TAB[$peran]) ? $peran : '';
        $q = trim((string) $request->query('q', ''));

        // Filter kelas (hanya berlaku di tab Siswa): id_kelas, 'none' = siswa tanpa kelas, '' = semua kelas
        $kelas = (string) $request->query('kelas', '');
        if ($peran !== 'siswa' || ($kelas !== 'none' && !DB::table('kelas')->where('id_kelas', $kelas)->exists())) {
            $kelas = '';
        }

        $per = (int) $request->query('per', 25);
        $per = in_array($per, [10, 25, 50, 100], true) ? $per : 25;

        // Satu tabel untuk semua peran; kelas siswa diambil dari tabel siswa (NISN = username)
        $query = DB::table('users')
            ->leftJoin('siswa', function ($j) {
                $j->on('siswa.nisn', '=', 'users.username')->where('users.id_role', '=', 5);
            })
            ->whereIn('users.id_role', array_column(self::TAB, 0))
            ->select('users.*', 'siswa.id_kelas');

        if ($peran !== '') {
            $query->where('users.id_role', self::TAB[$peran][0]);
        }
        if ($kelas === 'none') {
            $query->whereNull('siswa.id_kelas');
        } elseif ($kelas !== '') {
            $query->where('siswa.id_kelas', $kelas);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('users.username', 'like', "%$q%")->orWhere('users.nama_lengkap', 'like', "%$q%");
            });
        }

        // Urutan: Waka, Guru, Wali kelas, BK, Siswa, lalu nama
        $users = $query->orderByRaw('FIELD(users.id_role, 4, 2, 3, 7, 5)')
            ->orderBy('users.nama_lengkap')
            ->paginate($per)
            ->withQueryString();

        // Jumlah akun per peran (untuk pilihan filter)
        $jumlah = [];
        $perRole = DB::table('users')->selectRaw('id_role, COUNT(*) as n')->groupBy('id_role')->pluck('n', 'id_role');
        foreach (self::TAB as $kode => [$id]) {
            $jumlah[$kode] = (int) ($perRole[$id] ?? 0);
        }

        // Kelas yang diampu tiap wali kelas di halaman ini (kelas.id_wali_kelas -> guru.id_guru; guru.nip = username)
        $waliKelas = [];
        $usernameWali = collect($users->items())->where('id_role', 3)->pluck('username')->all();
        if ($usernameWali) {
            $idGuru = DB::table('guru')->whereIn('nip', $usernameWali)->pluck('id_guru', 'nip');
            $kelasPer = DB::table('kelas')->whereIn('id_wali_kelas', $idGuru->values()->all())->get()->groupBy('id_wali_kelas');
            foreach ($usernameWali as $uname) {
                $gid = $idGuru[$uname] ?? null;
                $waliKelas[$uname] = $gid ? $kelasPer->get($gid, collect())->pluck('id_kelas')->all() : [];
            }
        }

        $daftarKelas = DB::table('kelas')
            ->leftJoin('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
            ->select('kelas.*', 'guru.nama_guru as nama_wali')
            ->orderByRaw('CAST(kelas.id_kelas AS UNSIGNED)')
            ->get();

        // Jumlah siswa per kelas (untuk pilihan filter kelas) dan yang belum punya kelas
        $jumlahSiswaKelas = DB::table('users')
            ->join('siswa', 'siswa.nisn', '=', 'users.username')
            ->where('users.id_role', 5)
            ->whereNotNull('siswa.id_kelas')
            ->selectRaw('siswa.id_kelas, COUNT(*) as n')
            ->groupBy('siswa.id_kelas')
            ->pluck('n', 'id_kelas');
        $siswaTanpaKelas = DB::table('users')
            ->leftJoin('siswa', 'siswa.nisn', '=', 'users.username')
            ->where('users.id_role', 5)
            ->whereNull('siswa.id_kelas')
            ->count();

        $daftarTab = self::TAB;
        $bisaExcel = $this->bisaExcel();

        return view('manajemen_akun.index', compact(
            'peran', 'q', 'kelas', 'per', 'users', 'jumlah', 'waliKelas', 'daftarKelas', 'daftarTab', 'bisaExcel',
            'jumlahSiswaKelas', 'siswaTanpaKelas'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_role'      => 'required|in:2,3,4,5,7',
            'username'     => 'required|string|max:50|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'password'     => 'required|string|min:6',
            'no_hp'        => 'nullable|string|max:20',
            'id_kelas'     => 'required_if:id_role,5|nullable|exists:kelas,id_kelas',
            'kelas_wali'   => 'nullable|array',
            'kelas_wali.*' => 'exists:kelas,id_kelas',
        ], [
            'username.unique'     => 'Username (NIP/NISN) ini sudah dipakai akun lain.',
            'id_kelas.required_if' => 'Kelas siswa wajib dipilih.',
        ]);

        $role     = (int) $request->id_role;
        $username = trim($request->username);
        $nama     = trim($request->nama_lengkap);

        DB::transaction(function () use ($request, $role, $username, $nama) {
            DB::table('users')->insert([
                'username'      => $username,
                'password_hash' => bcrypt($request->password),
                'nama_lengkap'  => $nama,
                'no_hp'         => $request->filled('no_hp') ? trim($request->no_hp) : null,
                'id_role'       => $role,
                'is_aktif'      => 1,
            ]);

            if (in_array($role, self::ROLE_STAF, true)) {
                $idGuru = $this->pastikanGuru($username, $nama);
                if ($role === 3) {
                    $this->setWali($idGuru, (array) $request->input('kelas_wali', []));
                }
            }

            if ($role === 5) {
                DB::table('siswa')->updateOrInsert(
                    ['nisn' => $username],
                    ['nama_siswa' => $nama, 'id_kelas' => $request->id_kelas, 'status_utama' => 'Aktif']
                );
            }
        });

        return redirect()->route('akun.index')
            ->with('success', 'Akun ' . $nama . ' berhasil dibuat.');
    }

    public function update(Request $request, int $id)
    {
        $user = DB::table('users')->where('id_user', $id)->first();
        abort_unless($user, 404);

        $siswa    = (int) $user->id_role === 5;
        $roleBaru = $siswa ? 5 : (int) $request->input('id_role', $user->id_role);

        $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'no_hp'        => 'nullable|string|max:20',
            'id_role'      => $siswa ? 'nullable' : 'required|in:2,3,4,7',
            'id_kelas'     => $siswa ? 'required|exists:kelas,id_kelas' : 'nullable',
            'kelas_wali'   => 'nullable|array',
            'kelas_wali.*' => 'exists:kelas,id_kelas',
        ]);

        // Waka tidak boleh menurunkan perannya sendiri (nanti terkunci dari halaman ini)
        if ((int) $user->id_user === (int) session('id_user') && $roleBaru !== 4) {
            return redirect()->back()
                ->withErrors(['error' => 'Peran akun Anda sendiri tidak bisa diubah.']);
        }

        $nama = trim($request->nama_lengkap);

        DB::transaction(function () use ($request, $user, $siswa, $roleBaru, $nama) {
            DB::table('users')->where('id_user', $user->id_user)->update([
                'nama_lengkap' => $nama,
                'no_hp'        => $request->filled('no_hp') ? trim($request->no_hp) : null,
                'id_role'      => $roleBaru,
            ]);

            if ($siswa) {
                DB::table('siswa')->updateOrInsert(
                    ['nisn' => $user->username],
                    ['nama_siswa' => $nama, 'id_kelas' => $request->id_kelas, 'status_utama' => 'Aktif']
                );
                return;
            }

            $idGuru = $this->pastikanGuru($user->username, $nama);
            // Wali kelas: kelas yang dipilih diset; bila peran bukan wali kelas, kelasnya dilepas
            $this->setWali($idGuru, $roleBaru === 3 ? (array) $request->input('kelas_wali', []) : []);
        });

        return redirect()->back()
            ->with('success', 'Data akun ' . $nama . ' berhasil diperbarui.');
    }

    public function resetPassword(Request $request, int $id)
    {
        $user = DB::table('users')->where('id_user', $id)->first();
        abort_unless($user, 404);

        $request->validate(['password' => 'required|string|min:6'], [
            'password.min' => 'Password baru minimal 6 karakter.',
        ]);

        DB::table('users')->where('id_user', $id)->update(['password_hash' => bcrypt($request->password)]);

        return redirect()->back()
            ->with('success', 'Password akun ' . trim($user->nama_lengkap) . ' berhasil diganti.');
    }

    public function ubahStatus(int $id)
    {
        $user = DB::table('users')->where('id_user', $id)->first();
        abort_unless($user, 404);

        if ((int) $user->id_user === (int) session('id_user')) {
            return redirect()->back()
                ->withErrors(['error' => 'Akun Anda sendiri tidak bisa dinonaktifkan.']);
        }

        $baru = $user->is_aktif ? 0 : 1;
        DB::table('users')->where('id_user', $id)->update(['is_aktif' => $baru]);

        return redirect()->back()
            ->with('success', 'Akun ' . trim($user->nama_lengkap) . ($baru ? ' diaktifkan.' : ' dinonaktifkan (tidak bisa login).'));
    }

    /**
     * Hapus semua akun satu peran (guru pengampu, wali kelas, guru BK, waka),
     * atau semua siswa pada satu kelas. Wajib mengetik HAPUS sebagai konfirmasi.
     */
    public function hapusSemua(Request $request)
    {
        $tab   = (string) $request->input('peran_hapus', '');
        $kelas = trim((string) $request->input('kelas_hapus', ''));

        if (!isset(self::TAB[$tab])) {
            return redirect()->route('akun.index')->withErrors(['error' => 'Peran yang akan dihapus tidak dikenali.']);
        }
        [$role, $labelTab] = self::TAB[$tab];
        $kembali = fn () => redirect()->route('akun.index', array_filter(['peran' => $tab, 'kelas' => $role === 5 ? $kelas : null]));

        if ($request->input('konfirmasi') !== 'HAPUS') {
            return $kembali()->withErrors(['error' => 'Penghapusan dibatalkan: ketik HAPUS (huruf besar) untuk konfirmasi.']);
        }

        // ---------- Siswa: per kelas ----------
        if ($role === 5) {
            if ($kelas === '') {
                return $kembali()->withErrors(['error' => 'Pilih kelas terlebih dahulu sebelum menghapus semua siswa.']);
            }
            $namaKelas = 'tanpa kelas';
            $q = DB::table('siswa');
            if ($kelas === 'none') {
                $q->whereNull('id_kelas');
            } else {
                $k = DB::table('kelas')->where('id_kelas', $kelas)->first();
                if (!$k) {
                    return $kembali()->withErrors(['error' => 'Kelas tidak ditemukan.']);
                }
                $namaKelas = trim($k->tingkat . ' ' . $k->id_jurusan);
                $q->where('id_kelas', $kelas);
            }
            $daftar = $q->get(['id_siswa', 'nisn']);
            if ($daftar->isEmpty()) {
                return $kembali()->withErrors(['error' => "Tidak ada siswa di kelas {$namaKelas}."]);
            }
            $idSiswa = $daftar->pluck('id_siswa')->all();
            $nisn    = $daftar->pluck('nisn')->map(fn ($v) => (string) $v)->all();

            DB::transaction(function () use ($idSiswa, $nisn) {
                $adaAttendances = DB::getSchemaBuilder()->hasTable('attendances');
                foreach (array_chunk($idSiswa, 500) as $bagian) {
                    if ($adaAttendances) {
                        DB::table('attendances')->whereIn('student_id', $bagian)->delete();
                    }
                    DB::table('siswa')->whereIn('id_siswa', $bagian)->delete(); // presensi_detail ikut terhapus (cascade)
                }
                foreach (array_chunk($nisn, 500) as $bagian) {
                    DB::table('users')->where('id_role', 5)->whereIn('username', $bagian)->delete();
                }
            });

            return $kembali()->with('success', count($idSiswa) . " siswa kelas {$namaKelas} dihapus (akun, data siswa, dan riwayat absensinya).");
        }

        // ---------- Guru pengampu / wali kelas / guru BK / waka ----------
        $saya = (int) session('id_user');
        $akun = DB::table('users')->where('id_role', $role)->where('id_user', '<>', $saya)->get(['id_user', 'username']);
        $adaAkunSaya = DB::table('users')->where('id_role', $role)->where('id_user', $saya)->exists();
        if ($akun->isEmpty()) {
            return $kembali()->withErrors(['error' => "Tidak ada akun {$labelTab} yang bisa dihapus" . ($adaAkunSaya ? ' (akun Anda sendiri tidak boleh dihapus).' : '.')]);
        }
        $idUser   = $akun->pluck('id_user')->all();
        $username = $akun->pluck('username')->map(fn ($v) => (string) $v)->all();

        DB::transaction(function () use ($role, $idUser, $username) {
            // Wali kelas: lepaskan penetapan wali di kelasnya. Data guru, jadwal, dan jurnal tidak disentuh.
            if ($role === 3) {
                foreach (array_chunk($username, 500) as $bagian) {
                    $idGuru = DB::table('guru')->whereIn('nip', $bagian)->pluck('id_guru')->all();
                    if ($idGuru) {
                        DB::table('kelas')->whereIn('id_wali_kelas', $idGuru)->update(['id_wali_kelas' => null]);
                    }
                }
            }
            foreach (array_chunk($idUser, 500) as $bagian) {
                DB::table('users')->where('id_role', $role)->whereIn('id_user', $bagian)->delete();
            }
        });

        $pesan = count($idUser) . " akun {$labelTab} dihapus.";
        if ($adaAkunSaya) {
            $pesan .= ' Akun Anda sendiri tidak ikut dihapus.';
        }

        return $kembali()->with('success', $pesan);
    }

    /** Pastikan NIP tercatat di tabel guru; kembalikan id_guru. */
    private function pastikanGuru(string $nip, string $nama): int
    {
        $ada = DB::table('guru')->where('nip', $nip)->first();
        if ($ada) {
            return (int) $ada->id_guru;
        }

        return (int) DB::table('guru')->insertGetId(['nip' => $nip, 'nama_guru' => $nama]);
    }

    /** Tetapkan kelas yang diampu sebagai wali kelas (kelas lain milik guru ini dilepas). */
    private function setWali(int $idGuru, array $idKelas): void
    {
        DB::table('kelas')->where('id_wali_kelas', $idGuru)
            ->whereNotIn('id_kelas', $idKelas)
            ->update(['id_wali_kelas' => null]);

        if ($idKelas) {
            DB::table('kelas')->whereIn('id_kelas', $idKelas)->update(['id_wali_kelas' => $idGuru]);
        }
    }

    // ======================================================================
    // IMPORT AKUN (CSV; Excel .xlsx bila paket phpoffice/phpspreadsheet terpasang)
    // ======================================================================

    private function bisaExcel(): bool
    {
        return XlsxSederhana::tersedia();
    }

    /** Judul kolom berkas import untuk tiap tab. */
    private function kolomImport(int $role): array
    {
        $dasar = ['username', 'nama_lengkap', 'password', 'no_hp'];
        if ($role === 3) {
            $dasar[] = 'kelas_wali';
        }
        if ($role === 5) {
            $dasar[] = 'kelas';
        }

        return $dasar;
    }

    private function contohImport(int $role): array
    {
        return match ($role) {
            5       => ['0012345678', 'Contoh Siswa', 'rahasia123', '', 'X RPL 1'],
            3       => ['199001012020011001', 'Contoh Wali Kelas, S.Pd.', 'rahasia123', '', 'X RPL 1|X RPL 2'],
            default => ['199001012020011002', 'Contoh Guru, S.Pd.', 'rahasia123', ''],
        };
    }

    /** Keterangan tiap kolom untuk sheet "Petunjuk" di template Excel. */
    private function keteranganKolom(): array
    {
        return [
            'username'     => 'Wajib. NIP (guru/wali kelas/BK/Waka) atau NISN (siswa). Tidak boleh sama dengan akun lain.',
            'nama_lengkap' => 'Wajib.',
            'password'     => 'Wajib, minimal 6 karakter.',
            'no_hp'        => 'Boleh kosong.',
            'kelas_wali'   => 'Boleh kosong. Kode atau nama kelas (contoh: X RPL 1). Beberapa kelas dipisah tanda |.',
            'kelas'        => 'Wajib. Kode atau nama kelas siswa (contoh: X RPL 1).',
        ];
    }

    public function template(Request $request)
    {
        $tab = (string) $request->query('peran', 'guru');
        abort_unless(isset(self::TAB[$tab]), 404);
        [$role, $label] = self::TAB[$tab];

        $kolom  = $this->kolomImport($role);
        $contoh = $this->contohImport($role);

        // CSV hanya bila diminta, atau ekstensi zip PHP belum aktif
        if ($request->query('format') === 'csv' || !XlsxSederhana::tersedia()) {
            return response()->streamDownload(function () use ($kolom, $contoh) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $kolom, ';', '"', '\\');
                fputcsv($out, $contoh, ';', '"', '\\');
                fclose($out);
            }, 'template-akun-' . $tab . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        // Excel: sheet "Data" hanya berisi judul kolom (kolom berformat Teks), sheet "Petunjuk" berisi penjelasan & contoh
        $ket = $this->keteranganKolom();
        $petunjuk = [
            ['Petunjuk pengisian akun ' . $label],
            [],
            ['1. Isi data di sheet "Data" mulai baris ke-2. Jangan mengubah judul kolom di baris pertama.'],
            ['2. Semua kolom sudah berformat Teks, jadi NIP 18 digit dan angka 0 di depan NISN aman.'],
            ['3. Hanya sheet "Data" yang diimpor; sheet "Petunjuk" ini hanya penjelasan.'],
            [],
            ['Kolom', 'Keterangan', 'Contoh'],
        ];
        foreach ($kolom as $i => $nama) {
            $petunjuk[] = [$nama, $ket[$nama] ?? '', $contoh[$i] ?? ''];
        }

        $isi = XlsxSederhana::tulis([
            ['nama' => 'Data', 'baris' => [$kolom], 'header' => true, 'teks' => true, 'bekukan' => true, 'lebar' => 26],
            ['nama' => 'Petunjuk', 'baris' => $petunjuk, 'header' => true, 'lebar' => 40],
        ]);

        return response()->streamDownload(function () use ($isi) {
            echo $isi;
        }, 'template-akun-' . $tab . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'peran'  => ['required', 'in:' . implode(',', array_keys(self::TAB))],
            'berkas' => ['required', 'file', 'max:2048', function ($atribut, $nilai, $gagal) {
                $ext = strtolower($nilai->getClientOriginalExtension());
                $boleh = $this->bisaExcel() ? ['xlsx'] : ['csv', 'txt']; // CSV hanya cadangan bila ekstensi zip mati
                if (!in_array($ext, $boleh, true)) {
                    $gagal('Format berkas harus ' . ($this->bisaExcel() ? 'Excel (.xlsx). Unduh template Excel lalu isi.' : 'CSV.'));
                }
            }],
            'mode'   => ['required', 'in:lewati,perbarui'],
            'password_awal' => ['nullable', 'string', 'min:6', 'max:100'],
            'filter_kolom'  => ['nullable', 'string', 'max:60'],
            'filter_nilai'  => ['nullable', 'string', 'max:200'],
        ], [
            'peran.required'  => 'Pilih peran akun yang akan diimpor.',
            'peran.in'        => 'Peran akun tidak dikenali.',
            'berkas.required' => 'Pilih berkas yang akan diimpor.',
            'berkas.max'      => 'Ukuran berkas maksimal 2 MB.',
            'password_awal.min' => 'Password awal minimal 6 karakter.',
        ]);

        $tab = $request->input('peran');
        [$role, $labelTab] = self::TAB[$tab];

        $kembali = fn () => redirect()->route('akun.index', ['peran' => $tab]);

        try {
            $baris = $this->bacaBerkas($request->file('berkas'));
        } catch (\Throwable $e) {
            return $kembali()->withErrors(['error' => 'Berkas tidak bisa dibaca: ' . $e->getMessage()]);
        }
        if (count($baris) < 2) {
            return $kembali()->withErrors(['error' => 'Berkas kosong: isi minimal satu baris data di bawah baris judul kolom.']);
        }

        // Format "Daftar wali kelas" (kolom KELAS + NAMA WALI KELAS, tanpa NIP): dicocokkan lewat nama
        if ($tab === 'wali') {
            $hasilDaftar = $this->importDaftarWali($baris, $kembali);
            if ($hasilDaftar !== null) {
                return $hasilDaftar;
            }
        }

        // 1. Judul kolom. Baris judul dicari di 10 baris pertama, jadi berkas yang diawali
        //    judul tabel (mis. "DATA NOMINATIF GURU") tetap bisa dibaca.
        $alias = ['nip' => 'username', 'nisn' => 'username', 'nama' => 'nama_lengkap', 'hp' => 'no_hp', 'no_hp' => 'no_hp',
                  'id_kelas' => 'kelas', 'wali_kelas' => 'kelas_wali', 'kelas_diampu' => 'kelas_wali'];
        $normal = function ($h) use ($alias) {
            $h = $this->namaHeader($h);

            return $alias[$h] ?? $h;
        };

        $barisJudul = 0;
        foreach (array_slice($baris, 0, 10, true) as $i => $sel) {
            $h = array_map($normal, $sel);
            if (in_array('username', $h, true) && in_array('nama_lengkap', $h, true)) {
                $barisJudul = $i;
                break;
            }
        }
        $header = array_map($normal, $baris[$barisJudul]);
        $judulAsli = array_values(array_filter(array_map(fn ($c) => trim((string) $c), $baris[$barisJudul]), fn ($c) => $c !== ''));
        $baris  = array_slice($baris, $barisJudul + 1); // sisanya = data; nomor baris Excel = indeks + $barisJudul + 2

        $perlu  = $this->kolomImport($role);
        // password, no_hp, dan kelas_wali boleh tidak ada. Password kosong diisi password awal atau username.
        $wajib  = array_values(array_diff($perlu, ['password', 'no_hp', 'kelas_wali']));
        $hilang = array_diff($wajib, $header);
        if ($hilang) {
            return $kembali()->withErrors(['error' =>
                'Judul kolom tidak ditemukan. Kolom yang wajib ada: ' . implode(', ', $wajib)
                . ' (username boleh bernama NIP/NISN, nama_lengkap boleh bernama NAMA). Yang belum ada: ' . implode(', ', $hilang)
                . '. Baris judul harus berada di 10 baris pertama. Unduh template untuk contoh yang benar.']);
        }
        $indeks = array_flip($header);

        // Filter baris (opsional): hanya baris yang kolomnya berisi salah satu kata kunci yang diimpor
        $filterKolom = trim((string) $request->input('filter_kolom', ''));
        $filterNilai = trim((string) $request->input('filter_nilai', ''));
        $filterAktif = $filterKolom !== '' || $filterNilai !== '';
        $kolomFilter = null;
        $kataKunci = [];
        $rapikan = fn ($t) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $t)));
        if ($filterAktif) {
            if ($filterKolom === '' || $filterNilai === '') {
                return $kembali()->withErrors(['error' => 'Filter baris: isi nama kolom dan kata kuncinya, atau kosongkan keduanya untuk mengimpor semua baris.']);
            }
            $kolomFilter = $normal($filterKolom);
            if (!isset($indeks[$kolomFilter])) {
                return $kembali()->withErrors(['error' => "Filter baris: kolom '{$filterKolom}' tidak ada di berkas. Kolom yang tersedia: " . implode(', ', $judulAsli) . '.']);
            }
            $kataKunci = array_values(array_filter(array_map($rapikan, preg_split('/[,;|]+/', $filterNilai)), fn ($k) => $k !== ''));
        }
        $cocokBaris = 0;
        $tidakCocok = 0;

        // Peran Guru: baris Guru BK dan Waka dilewati otomatis (mereka punya peran sendiri).
        // Dicek di kolom MAPEL, JABATAN, dan TUGAS POKOK bila kolom itu ada di berkas.
        $dikecualikan = 0;
        $kolomPenanda = array_values(array_filter(
            array_map(fn ($k) => $indeks[$k] ?? null, ['mapel', 'jabatan', 'tugas_pokok']),
            fn ($i) => $i !== null
        ));

        // 2. Peta kelas (kode kelas atau tulisan seperti "X RPL 1")
        $petaKelas = [];
        foreach (DB::table('kelas')->get() as $k) {
            $petaKelas[strtolower((string) $k->id_kelas)] = (string) $k->id_kelas;
            $label = strtolower(preg_replace('/\s+/', ' ', trim($k->tingkat . ' ' . $k->id_jurusan)));
            $petaKelas[$label] = (string) $k->id_kelas;
        }
        $cariKelas = function (?string $teks) use ($petaKelas) {
            $kunci = strtolower(preg_replace('/\s+/', ' ', trim((string) $teks)));

            return $petaKelas[$kunci] ?? null;
        };

        // 3. Validasi semua baris dulu; kalau ada yang salah, tidak ada akun yang masuk
        $mode = $request->input('mode');
        $rencana = [];
        $galat = [];
        $terlihat = [];
        $kelasWaliDipakai = [];
        $passwordAwal = $request->filled('password_awal') ? (string) $request->input('password_awal') : null;
        $tanpaUsername = [];
        $ambil = function (array $sel, string $kol) use ($indeks) {
            if (!isset($indeks[$kol])) {
                return null;
            }
            $v = trim((string) ($sel[$indeks[$kol]] ?? ''));

            return $v === '' ? null : $v;
        };

        foreach ($baris as $i => $sel) {
            $no = $i + $barisJudul + 2; // nomor baris asli di Excel/CSV
            if (!array_filter($sel, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }

            if ($tab === 'guru' && $kolomPenanda) {
                $teksPenanda = $rapikan(implode(' ', array_map(fn ($i) => (string) ($sel[$i] ?? ''), $kolomPenanda)));
                if (preg_match('/bimbingan\s+(dan\s+)?konseling|konselor|\bbk\b|\bwaka\b|wakil\s+kepala/u', $teksPenanda)) {
                    $dikecualikan++;
                    continue;
                }
            }
            if ($filterAktif) {
                $isiSel = $rapikan($sel[$indeks[$kolomFilter]] ?? '');
                $cocok = false;
                foreach ($kataKunci as $kata) {
                    if ($isiSel !== '' && str_contains($isiSel, $kata)) {
                        $cocok = true;
                        break;
                    }
                }
                if (!$cocok) {
                    $tidakCocok++;
                    continue;
                }
            }
            $cocokBaris++;

            $username = $ambil($sel, 'username');
            // Baris tanpa NIP/NISN (mis. baris rekap atau pegawai belum punya NIP) dilewati dan dilaporkan
            if ($username === null) {
                $tanpaUsername[] = $no . ' (' . ($ambil($sel, 'nama_lengkap') ?? 'tanpa nama') . ')';
                continue;
            }
            // NISN selalu 10 digit; Excel sering membuang 1-2 angka 0 di depan (8-9 digit). NIS 5 digit tidak diubah.
            if ($role === 5 && $username !== null && ctype_digit($username) && strlen($username) >= 8 && strlen($username) < 10) {
                $username = str_pad($username, 10, '0', STR_PAD_LEFT);
            }

            // Pengaman angka panjang: Excel hanya menyimpan 15 digit penting
            if ($username !== null && preg_match('/^\d+([.,]\d+)?E\+?\d+$/i', $username)) {
                $galat[] = "Baris $no: username '$username' rusak karena Excel mengubahnya ke format ilmiah. Format kolom username sebagai Teks, lalu ketik ulang.";
                continue;
            }
            if ($role !== 5 && $username !== null && ctype_digit($username) && strlen($username) >= 16 && substr($username, -3) === '000') {
                $galat[] = "Baris $no: NIP '$username' diduga rusak (Excel membulatkan 3 digit terakhir jadi 000). Format kolom sebagai Teks, lalu ketik ulang.";
                continue;
            }

            $nilai = [
                'username'     => $username,
                'nama_lengkap' => $ambil($sel, 'nama_lengkap'),
                'password'     => $ambil($sel, 'password') ?? $passwordAwal ?? $username,
                'no_hp'        => $ambil($sel, 'no_hp'),
            ];
            $v = Validator::make($nilai, [
                'username'     => 'required|string|max:50',
                'nama_lengkap' => 'required|string|max:100',
                'password'     => 'required|string|min:6|max:100',
                'no_hp'        => 'nullable|string|max:20',
            ], [], ['username' => 'username', 'nama_lengkap' => 'nama_lengkap', 'password' => 'password', 'no_hp' => 'no_hp']);
            $adaGalat = false;
            if ($v->fails()) {
                foreach ($v->errors()->all() as $pesan) {
                    $galat[] = "Baris $no: $pesan";
                }
                continue;
            }

            if (isset($terlihat[$username])) {
                $galat[] = "Baris $no: username '$username' sudah muncul di baris {$terlihat[$username]} pada berkas yang sama.";
                continue;
            }
            $terlihat[$username] = $no;

            // Kelas siswa
            $idKelas = null;
            if ($role === 5) {
                $teks = $ambil($sel, 'kelas');
                $idKelas = $teks === null ? null : $cariKelas($teks);
                if ($idKelas === null) {
                    $galat[] = $teks === null ? "Baris $no: kelas wajib diisi." : "Baris $no: kelas '$teks' tidak ditemukan.";
                    $adaGalat = true;
                }
            }

            // Kelas yang diampu wali kelas (boleh lebih dari satu: pisahkan dengan | , ; atau /)
            $wali = null;
            if ($role === 3) {
                $teks = $ambil($sel, 'kelas_wali');
                if ($teks !== null) {
                    $wali = [];
                    foreach (preg_split('/[|,;\/]+/', $teks, -1, PREG_SPLIT_NO_EMPTY) as $bagian) {
                        $id = $cariKelas($bagian);
                        if ($id === null) {
                            $galat[] = "Baris $no: kelas '" . trim($bagian) . "' tidak ditemukan.";
                            $adaGalat = true;
                            continue;
                        }
                        if (isset($kelasWaliDipakai[$id])) {
                            $galat[] = "Baris $no: kelas '" . trim($bagian) . "' sudah dijadikan wali di baris {$kelasWaliDipakai[$id]}.";
                            $adaGalat = true;
                            continue;
                        }
                        $kelasWaliDipakai[$id] = $no;
                        $wali[] = $id;
                    }
                }
            }
            if ($adaGalat) {
                continue;
            }

            // Cek username di database
            $ada = DB::table('users')->where('username', $username)->first();
            if ($ada && (int) $ada->id_role !== $role) {
                $galat[] = "Baris $no: username '$username' sudah dipakai akun dengan peran lain.";
                continue;
            }

            $aksi = !$ada ? 'tambah' : ($mode === 'perbarui' ? 'perbarui' : 'lewati');
            $pwDefault = $ambil($sel, 'password') === null;
            $rencana[] = $nilai + ['pw_default' => $pwDefault, 'aksi' => $aksi, 'id_kelas' => $idKelas, 'wali' => $wali, 'id_user' => $ada->id_user ?? null];
        }

        if ($filterAktif && $cocokBaris === 0) {
            return $kembali()->withErrors(['error' => "Tidak ada baris yang kolom '{$filterKolom}'-nya berisi '{$filterNilai}'. Periksa penulisan kata kunci."]);
        }
        if ($galat) {
            $tampil = array_slice($galat, 0, 20);
            if (count($galat) > 20) {
                $tampil[] = '... dan ' . (count($galat) - 20) . ' kesalahan lain.';
            }
            array_unshift($tampil, 'Import dibatalkan, tidak ada akun yang masuk. Perbaiki berkas lalu coba lagi:');

            return $kembali()->withErrors($tampil);
        }
        if (!$rencana) {
            return $kembali()->withErrors(['error' => 'Tidak ada baris data yang bisa diimpor.']);
        }

        // 4. Simpan sekaligus.
        // bcrypt sengaja lambat; ratusan akun bisa melewati batas 30 detik bawaan PHP, jadi batas waktu dicabut
        // dan hash dihitung dulu di luar transaksi (password yang sama cukup di-hash sekali).
        @set_time_limit(0);
        $cacheHash = [];
        foreach ($rencana as $k => $r) {
            if ($r['aksi'] === 'tambah') {
                $rencana[$k]['hash'] = $cacheHash[$r['password']] ??= bcrypt($r['password']);
            }
        }
        $hitung = ['tambah' => 0, 'perbarui' => 0, 'lewati' => 0];
        $jumlahPwDefault = 0;
        DB::transaction(function () use ($rencana, $role, &$hitung, &$jumlahPwDefault) {
            foreach ($rencana as $r) {
                $hitung[$r['aksi']]++;
                if ($r['aksi'] === 'tambah' && $r['pw_default']) {
                    $jumlahPwDefault++;
                }
                if ($r['aksi'] === 'lewati') {
                    continue;
                }

                $nama = $r['nama_lengkap'];
                if ($r['aksi'] === 'tambah') {
                    DB::table('users')->insert([
                        'username'      => $r['username'],
                        'password_hash' => $r['hash'],
                        'nama_lengkap'  => $nama,
                        'no_hp'         => $r['no_hp'],
                        'id_role'       => $role,
                        'is_aktif'      => 1,
                    ]);
                } else {
                    // Perbarui: password tidak diubah lewat import (gunakan tombol Password)
                    $ubah = ['nama_lengkap' => $nama];
                    if ($r['no_hp'] !== null) {
                        $ubah['no_hp'] = $r['no_hp'];
                    }
                    DB::table('users')->where('id_user', $r['id_user'])->update($ubah);
                }

                if ($role === 5) {
                    DB::table('siswa')->updateOrInsert(
                        ['nisn' => $r['username']],
                        ['nama_siswa' => $nama, 'id_kelas' => $r['id_kelas'], 'status_utama' => 'Aktif']
                    );
                } else {
                    $idGuru = $this->pastikanGuru($r['username'], $nama);
                    if ($role === 3 && $r['wali'] !== null) {
                        $this->setWali($idGuru, $r['wali']);
                    }
                }
            }
        });

        $pesan = "Import akun {$labelTab} selesai: {$hitung['tambah']} ditambahkan, {$hitung['perbarui']} diperbarui, {$hitung['lewati']} dilewati (username sudah ada).";
        if ($jumlahPwDefault > 0) {
            $pesan .= " {$jumlahPwDefault} akun baru memakai password awal "
                . ($passwordAwal !== null ? 'yang Anda isi.' : '= username (NIP/NISN) masing-masing.');
        }
        if ($dikecualikan > 0) {
            $pesan .= " {$dikecualikan} baris Guru BK/Waka dilewati otomatis karena bukan peran Guru.";
        }
        if ($filterAktif) {
            $pesan .= " Filter {$filterKolom} berisi '{$filterNilai}': {$cocokBaris} baris cocok, {$tidakCocok} baris lain diabaikan.";
        }
        if ($tanpaUsername) {
            $pesan .= ' Dilewati karena tidak ada NIP/NISN: baris ' . implode(', ', array_slice($tanpaUsername, 0, 10))
                . (count($tanpaUsername) > 10 ? ', dan ' . (count($tanpaUsername) - 10) . ' baris lain' : '') . '.';
        }

        return $kembali()->with('success', $pesan);
    }

    /** Kunci pencocokan nama: bagian sebelum koma (tanpa gelar), huruf kecil, hanya huruf a-z. */
    private function kunciNama(?string $nama): string
    {
        $bagian = explode(',', mb_strtolower(trim((string) $nama)))[0];

        return preg_replace('/[^a-z]/', '', $bagian);
    }

    /**
     * Impor daftar wali kelas yang tidak punya NIP (kolom KELAS dan NAMA WALI KELAS).
     * Nama wali dicocokkan dengan akun guru yang sudah ada; akun itu dijadikan Wali kelas
     * untuk kelas yang tertulis. Kembalikan null bila berkas bukan format ini.
     */
    private function importDaftarWali(array $baris, \Closure $kembali)
    {
        $barisJudul = null;
        foreach (array_slice($baris, 0, 20, true) as $i => $sel) {
            $h = array_map(fn ($c) => $this->namaHeader($c), $sel);
            if (in_array('kelas', $h, true) && in_array('nama_wali_kelas', $h, true)
                && !in_array('nip', $h, true) && !in_array('username', $h, true)) {
                $barisJudul = $i;
                break;
            }
        }
        if ($barisJudul === null) {
            return null;
        }
        $h = array_flip(array_map(fn ($c) => $this->namaHeader($c), $baris[$barisJudul]));
        $iKelas = $h['kelas'];
        $iWali  = $h['nama_wali_kelas'];

        // Peta kelas: "X AK1", "X  AK 1", "XAK1" -> semuanya menjadi kunci XAK1
        $petaKelas = [];
        foreach (DB::table('kelas')->get() as $k) {
            $petaKelas[strtoupper(preg_replace('/\s+/', '', $k->tingkat . $k->id_jurusan))] = (string) $k->id_kelas;
        }

        // Peta nama -> akun guru/wali yang sudah ada
        $petaNama = [];
        foreach (DB::table('users')->whereIn('id_role', [2, 3])->get(['id_user', 'username', 'nama_lengkap', 'id_role']) as $u) {
            $petaNama[$this->kunciNama($u->nama_lengkap)][] = $u;
        }

        $galat = [];
        $perAkun = [];      // id_user => ['user' => ..., 'kelas' => [id_kelas, ...]]
        $kelasTerpakai = [];
        foreach ($baris as $i => $sel) {
            if ($i <= $barisJudul) {
                continue;
            }
            $no = $i + 1;
            $teksKelas = trim((string) ($sel[$iKelas] ?? ''));
            $namaWali  = trim((string) ($sel[$iWali] ?? ''));
            if ($namaWali === '') {
                continue; // baris kosong atau keterangan di bawah tabel
            }

            $kunciKelas = strtoupper(preg_replace('/\s+/', '', $teksKelas));
            if (!isset($petaKelas[$kunciKelas])) {
                $galat[] = "Baris $no: kelas '$teksKelas' tidak ditemukan.";
                continue;
            }
            $idKelas = $petaKelas[$kunciKelas];
            if (isset($kelasTerpakai[$idKelas])) {
                $galat[] = "Baris $no: kelas '$teksKelas' sudah muncul di baris {$kelasTerpakai[$idKelas]}.";
                continue;
            }

            $cocok = $petaNama[$this->kunciNama($namaWali)] ?? [];
            if (count($cocok) === 0) {
                $galat[] = "Baris $no: wali '$namaWali' tidak ada di akun guru. Impor akun gurunya dulu atau periksa penulisan namanya.";
                continue;
            }
            if (count($cocok) > 1) {
                $galat[] = "Baris $no: nama '$namaWali' cocok dengan lebih dari satu akun.";
                continue;
            }

            $kelasTerpakai[$idKelas] = $no;
            $u = $cocok[0];
            $perAkun[$u->id_user]['user']    = $u;
            $perAkun[$u->id_user]['kelas'][] = $idKelas;
        }

        if ($galat) {
            $tampil = array_slice($galat, 0, 20);
            if (count($galat) > 20) {
                $tampil[] = '... dan ' . (count($galat) - 20) . ' kesalahan lain.';
            }
            array_unshift($tampil, 'Import dibatalkan, tidak ada yang diubah. Perbaiki berkas lalu coba lagi:');

            return $kembali()->withErrors($tampil);
        }
        if (!$perAkun) {
            return $kembali()->withErrors(['error' => 'Tidak ada baris wali kelas yang bisa diimpor.']);
        }

        $dijadikanWali = 0;
        DB::transaction(function () use ($perAkun, &$dijadikanWali) {
            foreach ($perAkun as $item) {
                $u = $item['user'];
                if ((int) $u->id_role !== 3) {
                    DB::table('users')->where('id_user', $u->id_user)->update(['id_role' => 3]);
                    $dijadikanWali++;
                }
                $idGuru = $this->pastikanGuru((string) $u->username, (string) $u->nama_lengkap);
                $this->setWali($idGuru, $item['kelas']);
            }
        });

        $jumlahKelas = count($kelasTerpakai);

        return $kembali()->with('success',
            count($perAkun) . " wali kelas ditetapkan untuk {$jumlahKelas} kelas."
            . ($dijadikanWali > 0 ? " {$dijadikanWali} akun guru diubah perannya menjadi Wali kelas." : '')
            . ' Kolom guru BK pada berkas tidak diproses.');
    }

    private function namaHeader($h): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $h);

        return str_replace([' ', '-', '.'], '_', strtolower(trim($h)));
    }

    /** Baca berkas CSV/XLSX menjadi array baris; baris pertama = judul kolom. */
    private function bacaBerkas(\Illuminate\Http\UploadedFile $berkas): array
    {
        $ext = strtolower($berkas->getClientOriginalExtension());

        if ($ext === 'xlsx') {
            if (!$this->bisaExcel()) {
                throw new \RuntimeException('Ekstensi PHP zip belum aktif. Gunakan CSV atau aktifkan extension=zip di php.ini.');
            }

            return XlsxSederhana::baca($berkas->getRealPath());
        }

        // CSV: pastikan UTF-8, lalu deteksi pemisah (; , atau tab)
        $isi = file_get_contents($berkas->getRealPath());
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
        if (!mb_check_encoding($isi, 'UTF-8')) {
            $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
        }
        $barisPertama = strtok($isi, "\r\n") ?: '';
        $pemisah = ';';
        $maks = -1;
        foreach ([';', ',', "\t"] as $kandidat) {
            $n = substr_count($barisPertama, $kandidat);
            if ($n > $maks) {
                $maks = $n;
                $pemisah = $kandidat;
            }
        }

        $h = fopen('php://temp', 'r+');
        fwrite($h, $isi);
        rewind($h);
        $hasil = [];
        while (($baris = fgetcsv($h, 0, $pemisah, '"', '\\')) !== false) {
            $hasil[] = $baris;
        }
        fclose($h);

        return $hasil;
    }
}
