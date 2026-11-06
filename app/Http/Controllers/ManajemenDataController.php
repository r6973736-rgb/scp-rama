<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Manajemen data (master data) untuk Waka Kurikulum:
 * jurusan, kelas, mata pelajaran, ruangan, guru, tahun ajaran.
 */
class ManajemenDataController extends Controller
{
    /**
     * Skema tiap data.
     * kolom: nama => [label, tipe (text|select|datalist|check), aturan validasi, opsi statis (opsional)]
     * pakai: tabel lain yang memakai data ini [tabel, kolom, label] -> data tidak boleh dihapus kalau masih dipakai
     */
    private function skema(): array
    {
        return [
            'jurusan' => [
                'label' => 'Jurusan', 'tabel' => 'jurusan', 'pk' => 'id_jurusan', 'auto' => false, 'pk_label' => 'Kode jurusan',
                'pk_aturan' => 'required|string|max:10',
                'kolom' => [
                    'nama_jurusan' => ['Nama jurusan', 'text', 'required|string|max:100'],
                ],
                'pakai' => [['kelas', 'id_jurusan', 'kelas']],
            ],
            'kelas' => [
                'label' => 'Kelas', 'tabel' => 'kelas', 'pk' => 'id_kelas', 'auto' => false, 'pk_label' => 'Kode kelas',
                'pk_aturan' => 'required|string|max:20',
                'kolom' => [
                    'tingkat'       => ['Tingkat', 'select', 'required|in:X,XI,XII', ['X' => 'X', 'XI' => 'XI', 'XII' => 'XII']],
                    'id_jurusan'    => ['Jurusan', 'select', 'nullable|exists:jurusan,id_jurusan', 'jurusan'],
                    'id_wali_kelas' => ['Wali kelas', 'select', 'nullable|exists:guru,id_guru', 'guru'],
                ],
                'pakai' => [['siswa', 'id_kelas', 'siswa'], ['jadwal_pelajaran', 'id_kelas', 'jadwal pelajaran']],
            ],
            'mapel' => [
                'label' => 'Mata pelajaran', 'tabel' => 'mata_pelajaran', 'pk' => 'id_mapel', 'auto' => false, 'pk_label' => 'Kode mapel',
                'pk_aturan' => 'required|string|max:20',
                'kolom' => [
                    'nama_mapel' => ['Nama mata pelajaran', 'text', 'required|string|max:100'],
                ],
                'pakai' => [['jadwal_pelajaran', 'id_mapel', 'jadwal pelajaran']],
            ],
            'ruangan' => [
                'label' => 'Ruangan', 'tabel' => 'ruangan', 'pk' => 'id_ruangan', 'auto' => false, 'pk_label' => 'Kode ruangan',
                'pk_aturan' => 'required|string|max:20',
                'kolom' => [
                    'nama_ruangan' => ['Nama ruangan', 'text', 'required|string|max:50'],
                    'jenis'        => ['Jenis', 'datalist', 'nullable|string|max:20', ['Ruangan' => 'Ruangan', 'Lab' => 'Lab', 'Bengkel' => 'Bengkel']],
                ],
                'pakai' => [['jadwal_pelajaran', 'id_ruangan_default', 'jadwal pelajaran'], ['presensi_header', 'id_ruangan_digunakan', 'presensi']],
            ],
            'guru' => [
                'label' => 'Guru', 'tabel' => 'guru', 'pk' => 'id_guru', 'auto' => true, 'pk_label' => 'ID',
                'kolom' => [
                    'nip'       => ['NIP', 'text', 'nullable|string|max:30'],
                    'nama_guru' => ['Nama guru', 'text', 'required|string|max:100'],
                ],
                'pakai' => [['jadwal_pelajaran', 'id_guru', 'jadwal pelajaran'], ['presensi_header', 'id_guru_pengajar', 'presensi'], ['kelas', 'id_wali_kelas', 'kelas (wali kelas)']],
            ],
            'tahun' => [
                'label' => 'Tahun ajaran', 'tabel' => 'tahun_ajaran', 'pk' => 'id_tahun_ajaran', 'auto' => false, 'pk_label' => 'Kode tahun ajaran',
                'pk_aturan' => 'required|string|max:20',
                'kolom' => [
                    'nama_semester' => ['Nama semester', 'text', 'required|string|max:50'],
                    'is_aktif'      => ['Jadikan tahun ajaran aktif', 'check', 'nullable|boolean'],
                ],
                'pakai' => [['jadwal_pelajaran', 'id_tahun_ajaran', 'jadwal pelajaran']],
            ],
        ];
    }

    private function skemaAtau404(string $entitas): array
    {
        $semua = $this->skema();
        abort_unless(isset($semua[$entitas]), 404);

        return $semua[$entitas];
    }

    /** Opsi untuk kolom bertipe select yang bersumber dari tabel lain. */
    private function opsi(): array
    {
        return [
            'jurusan' => DB::table('jurusan')->orderBy('id_jurusan')->pluck('nama_jurusan', 'id_jurusan')->all(),
            'guru'    => DB::table('guru')->orderBy('nama_guru')->pluck('nama_guru', 'id_guru')->all(),
        ];
    }

    public function index(Request $request)
    {
        $skema  = $this->skema();
        $tab    = $request->query('tab', 'jurusan');
        $tab    = isset($skema[$tab]) ? $tab : 'jurusan';
        $aktif  = $skema[$tab];
        $opsi   = $this->opsi();

        $query = DB::table($aktif['tabel']);
        $aktif['auto']
            ? $query->orderBy($aktif['pk'])
            : $query->orderByRaw('LENGTH(`' . $aktif['pk'] . '`), `' . $aktif['pk'] . '`');
        $baris = $query->get();

        $jumlah = [];
        foreach ($skema as $kode => $s) {
            $jumlah[$kode] = DB::table($s['tabel'])->count();
        }

        $bisaExcel = $this->bisaExcel();

        return view('manajemen_data.index', compact('skema', 'tab', 'aktif', 'baris', 'opsi', 'jumlah', 'bisaExcel'));
    }

    public function store(Request $request, string $entitas)
    {
        $s = $this->skemaAtau404($entitas);

        $aturan = [];
        if (!$s['auto']) {
            $aturan[$s['pk']] = $s['pk_aturan'] . '|unique:' . $s['tabel'] . ',' . $s['pk'];
        }
        foreach ($s['kolom'] as $nama => $k) {
            $aturan[$nama] = $k[2];
        }
        $data = $request->validate($aturan);

        DB::transaction(function () use ($s, $data, $request) {
            $simpan = $this->rapikan($s, $data, $request);
            if (!empty($simpan['is_aktif'])) {
                DB::table($s['tabel'])->update(['is_aktif' => 0]);
            }
            DB::table($s['tabel'])->insert($simpan);
        });

        return redirect()->route('data.index', ['tab' => $entitas])
            ->with('success', $s['label'] . ' berhasil ditambahkan.');
    }

    public function update(Request $request, string $entitas, string $id)
    {
        $s = $this->skemaAtau404($entitas);
        abort_unless(DB::table($s['tabel'])->where($s['pk'], $id)->exists(), 404);

        $aturan = [];
        foreach ($s['kolom'] as $nama => $k) {
            $aturan[$nama] = $k[2];
        }
        $data = $request->validate($aturan);

        DB::transaction(function () use ($s, $data, $request, $id) {
            $simpan = $this->rapikan($s, $data, $request);
            if (!empty($simpan['is_aktif'])) {
                DB::table($s['tabel'])->where($s['pk'], '!=', $id)->update(['is_aktif' => 0]);
            }
            DB::table($s['tabel'])->where($s['pk'], $id)->update($simpan);
        });

        return redirect()->route('data.index', ['tab' => $entitas])
            ->with('success', $s['label'] . ' berhasil diperbarui.');
    }

    public function destroy(string $entitas, string $id)
    {
        $s = $this->skemaAtau404($entitas);
        abort_unless(DB::table($s['tabel'])->where($s['pk'], $id)->exists(), 404);

        // Tolak penghapusan kalau masih dipakai (relasi di database bersifat cascade,
        // jadi tanpa pengecekan ini jadwal & presensi bisa ikut terhapus).
        $dipakai = [];
        foreach ($s['pakai'] as [$tabel, $kolom, $nama]) {
            $n = DB::table($tabel)->where($kolom, $id)->count();
            if ($n > 0) {
                $dipakai[] = $n . ' ' . $nama;
            }
        }

        if ($dipakai) {
            return redirect()->route('data.index', ['tab' => $entitas])
                ->withErrors(['error' => $s['label'] . ' tidak bisa dihapus karena masih dipakai oleh ' . implode(', ', $dipakai) . '.']);
        }

        DB::table($s['tabel'])->where($s['pk'], $id)->delete();

        return redirect()->route('data.index', ['tab' => $entitas])
            ->with('success', $s['label'] . ' berhasil dihapus.');
    }

    /** Siapkan nilai yang akan disimpan: kosong jadi NULL, checkbox jadi 0/1. */
    private function rapikan(array $s, array $data, Request $request): array
    {
        $simpan = [];
        if (!$s['auto'] && isset($data[$s['pk']])) {
            $simpan[$s['pk']] = trim($data[$s['pk']]);
        }
        foreach ($s['kolom'] as $nama => $k) {
            if ($k[1] === 'check') {
                $simpan[$nama] = $request->boolean($nama) ? 1 : 0;
                continue;
            }
            $nilai = isset($data[$nama]) ? trim((string) $data[$nama]) : null;
            $simpan[$nama] = ($nilai === '' || $nilai === null) ? null : $nilai;
        }

        return $simpan;
    }

    // ======================================================================
    // IMPORT (CSV; Excel .xlsx bila paket phpoffice/phpspreadsheet terpasang)
    // ======================================================================

    private function bisaExcel(): bool
    {
        return class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class);
    }

    /** Urutan kolom di berkas import = nama kolom di database. */
    private function kolomImport(array $s): array
    {
        return array_merge($s['auto'] ? [] : [$s['pk']], array_keys($s['kolom']));
    }

    private function contohImport(): array
    {
        return [
            'jurusan' => ['RPL 3', 'Rekayasa Perangkat Lunak 3'],
            'kelas'   => ['40', 'X', 'RPL 1', ''],
            'mapel'   => ['INF', 'Informatika'],
            'ruangan' => ['40', 'R 40', 'Ruangan'],
            'guru'    => ['199001012020011001', 'Contoh Guru, S.Pd.'],
            'tahun'   => ['2027 Ganjil', '2027 Ganjil', '0'],
        ];
    }

    /** Unduh template CSV (pemisah titik koma + BOM agar langsung rapi di Excel Indonesia). */
    public function template(string $entitas)
    {
        $s       = $this->skemaAtau404($entitas);
        $header  = $this->kolomImport($s);
        $contoh  = $this->contohImport()[$entitas] ?? [];

        return response()->streamDownload(function () use ($header, $contoh) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ';', '"', '\\');
            fputcsv($out, $contoh, ';', '"', '\\');
            fclose($out);
        }, 'template-' . $entitas . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request, string $entitas)
    {
        $s = $this->skemaAtau404($entitas);

        $request->validate([
            'berkas' => ['required', 'file', 'max:2048', 'mimes:' . ($this->bisaExcel() ? 'csv,txt,xlsx' : 'csv,txt')],
            'mode'   => ['required', 'in:lewati,perbarui'],
        ], [
            'berkas.required' => 'Pilih berkas yang akan diimpor.',
            'berkas.mimes'    => 'Format berkas harus ' . ($this->bisaExcel() ? 'CSV atau XLSX.' : 'CSV.'),
            'berkas.max'      => 'Ukuran berkas maksimal 2 MB.',
        ]);

        try {
            $baris = $this->bacaBerkas($request->file('berkas'));
        } catch (\Throwable $e) {
            return $this->kembali($entitas)->withErrors(['error' => 'Berkas tidak bisa dibaca: ' . $e->getMessage()]);
        }

        if (count($baris) < 2) {
            return $this->kembali($entitas)->withErrors(['error' => 'Berkas kosong: isi minimal satu baris data di bawah baris judul kolom.']);
        }

        // 1. Judul kolom harus lengkap
        $header = array_map(fn ($h) => $this->namaHeader($h), array_shift($baris));
        $perlu  = $this->kolomImport($s);
        $hilang = array_diff($perlu, $header);
        if ($hilang) {
            return $this->kembali($entitas)->withErrors(['error' =>
                'Baris pertama harus berisi judul kolom: ' . implode(', ', $perlu) . '. Yang belum ada: ' . implode(', ', $hilang) . '. Unduh template untuk contoh yang benar.']);
        }
        $indeks = array_flip($header);

        // 2. Validasi semua baris dulu; kalau ada yang salah, tidak ada data yang masuk
        $rencana = [];
        $galat   = [];
        $terlihat = [];
        $mode    = $request->input('mode');

        foreach ($baris as $i => $sel) {
            $no = $i + 2; // nomor baris di berkas (baris 1 = judul)
            if (!array_filter($sel, fn ($v) => trim((string) $v) !== '')) {
                continue; // baris kosong
            }

            $nilai = [];
            foreach ($perlu as $kol) {
                $v = isset($sel[$indeks[$kol]]) ? trim((string) $sel[$indeks[$kol]]) : '';
                $nilai[$kol] = $v === '' ? null : $v;
            }
            $nilai = $this->normalisasi($entitas, $nilai);

            $aturan = [];
            if (!$s['auto']) {
                $aturan[$s['pk']] = $s['pk_aturan'];
            }
            foreach ($s['kolom'] as $nama => $k) {
                $aturan[$nama] = $k[2];
            }
            $v = Validator::make($nilai, $aturan, [], array_combine($perlu, $perlu));
            if ($v->fails()) {
                foreach ($v->errors()->all() as $pesan) {
                    $galat[] = "Baris $no: $pesan";
                }
                continue;
            }

            // Kunci pengenal: kode (data lain) atau NIP (guru)
            $kunci = $s['auto'] ? ($nilai['nip'] ?? null) : $nilai[$s['pk']];
            if ($kunci !== null) {
                if (isset($terlihat[$kunci])) {
                    $galat[] = "Baris $no: '$kunci' sudah muncul di baris {$terlihat[$kunci]} pada berkas yang sama.";
                    continue;
                }
                $terlihat[$kunci] = $no;
            }

            // Cari data yang sudah ada
            $idAda = null;
            if ($kunci !== null) {
                $q = $s['auto']
                    ? DB::table($s['tabel'])->where('nip', $kunci)
                    : DB::table($s['tabel'])->where($s['pk'], $kunci);
                $ada = $q->first();
                $idAda = $ada ? $ada->{$s['pk']} : null;
            }

            $aksi = $idAda === null ? 'tambah' : ($mode === 'perbarui' ? 'perbarui' : 'lewati');
            $rencana[] = ['aksi' => $aksi, 'nilai' => $nilai, 'id' => $idAda, 'baris' => $no];
        }

        // Hanya satu tahun ajaran yang boleh aktif
        if ($entitas === 'tahun') {
            $aktifBaru = array_filter($rencana, fn ($r) => $r['aksi'] !== 'lewati' && !empty($r['nilai']['is_aktif']));
            if (count($aktifBaru) > 1) {
                $galat[] = 'Hanya satu tahun ajaran yang boleh berstatus aktif (is_aktif = 1) dalam satu berkas.';
            }
        }

        if ($galat) {
            $tampil = array_slice($galat, 0, 20);
            if (count($galat) > 20) {
                $tampil[] = '... dan ' . (count($galat) - 20) . ' kesalahan lain.';
            }
            array_unshift($tampil, 'Import dibatalkan, tidak ada data yang masuk. Perbaiki berkas lalu coba lagi:');

            return $this->kembali($entitas)->withErrors($tampil);
        }
        if (!$rencana) {
            return $this->kembali($entitas)->withErrors(['error' => 'Tidak ada baris data yang bisa diimpor.']);
        }

        // 3. Simpan sekaligus
        $hitung = ['tambah' => 0, 'perbarui' => 0, 'lewati' => 0];
        DB::transaction(function () use ($s, $rencana, &$hitung) {
            foreach ($rencana as $r) {
                $hitung[$r['aksi']]++;
                if ($r['aksi'] === 'lewati') {
                    continue;
                }
                $nilai = $r['nilai'];
                if ($r['aksi'] === 'tambah') {
                    DB::table($s['tabel'])->insert($nilai);
                    $idBaru = $s['auto'] ? DB::getPdo()->lastInsertId() : $nilai[$s['pk']];
                } else {
                    $ubah = $nilai;
                    unset($ubah[$s['pk']]);
                    DB::table($s['tabel'])->where($s['pk'], $r['id'])->update($ubah);
                    $idBaru = $r['id'];
                }
                if (!empty($nilai['is_aktif'])) {
                    DB::table($s['tabel'])->where($s['pk'], '!=', $idBaru)->update(['is_aktif' => 0]);
                }
            }
        });

        return $this->kembali($entitas)->with('success',
            "Import {$s['label']} selesai: {$hitung['tambah']} ditambahkan, {$hitung['perbarui']} diperbarui, {$hitung['lewati']} dilewati (sudah ada).");
    }

    private function kembali(string $entitas)
    {
        return redirect()->route('data.index', ['tab' => $entitas]);
    }

    private function namaHeader($h): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $h);

        return str_replace([' ', '-'], '_', strtolower(trim($h)));
    }

    /** Samakan penulisan nilai sebelum divalidasi. */
    private function normalisasi(string $entitas, array $n): array
    {
        if ($entitas === 'kelas') {
            if (isset($n['tingkat'])) {
                $n['tingkat'] = strtoupper($n['tingkat']);
            }
            // Wali kelas boleh diisi NIP guru atau ID guru
            if (!empty($n['id_wali_kelas'])) {
                $guru = DB::table('guru')->where('nip', $n['id_wali_kelas'])->first()
                    ?? DB::table('guru')->where('id_guru', $n['id_wali_kelas'])->first();
                if ($guru) {
                    $n['id_wali_kelas'] = $guru->id_guru;
                }
            }
        }
        if ($entitas === 'tahun') {
            $v = strtolower((string) ($n['is_aktif'] ?? ''));
            $n['is_aktif'] = in_array($v, ['1', 'ya', 'aktif', 'true', 'y'], true) ? 1 : 0;
        }

        return $n;
    }

    /** Baca berkas CSV/XLSX menjadi array baris; baris pertama = judul kolom. */
    private function bacaBerkas(\Illuminate\Http\UploadedFile $berkas): array
    {
        $ext = strtolower($berkas->getClientOriginalExtension());

        if ($ext === 'xlsx') {
            if (!$this->bisaExcel()) {
                throw new \RuntimeException('Dukungan Excel belum dipasang (composer require phpoffice/phpspreadsheet). Gunakan CSV.');
            }
            $lembar = \PhpOffice\PhpSpreadsheet\IOFactory::load($berkas->getRealPath())->getActiveSheet();

            return array_map(
                fn ($baris) => array_map(fn ($c) => is_float($c) && floor($c) == $c ? number_format($c, 0, '', '') : (string) $c, $baris),
                $lembar->toArray(null, true, true, false)
            );
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
