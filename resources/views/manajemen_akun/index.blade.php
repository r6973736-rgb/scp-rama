@extends('layouts.app')

@section('judul', 'Manajemen akun')
@section('sub', 'Buat, impor, dan kelola semua akun pengguna dalam satu tabel.')

@section('isi')
@php
    $labelKelas = fn ($k) => trim($k->tingkat . ' ' . $k->id_jurusan);
    $namaPeran  = [2 => 'Guru pengampu', 3 => 'Wali kelas', 4 => 'Waka kurikulum', 5 => 'Siswa', 7 => 'Guru BK'];
    $warnaPeran = [2 => 'bg-primary', 3 => 'bg-info text-dark', 4 => 'bg-danger', 5 => 'bg-secondary', 7 => 'bg-warning text-dark'];
    $urutanBuat = [2, 3, 7, 4, 5]; // urutan pilihan peran di form
    $totalAkun  = array_sum($jumlah);
@endphp

<style>
    .tabel-akun th { font-size: .78rem; letter-spacing: .03em; text-transform: uppercase; color: #5b6573; background: #f4f7fb; white-space: nowrap; border-bottom: 2px solid #dbe3ee; }
    .tabel-akun td { padding-top: .7rem; padding-bottom: .7rem; }
    .tabel-akun .nama { font-weight: 600; color: #1d2733; }
    .tabel-akun .kode-user { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .9rem; white-space: nowrap; }
    .tabel-akun .baris-nonaktif td { background: #fafafa; color: #8a93a0; }
    .tabel-akun .baris-nonaktif .nama { color: #6c7683; }
    .status { display: inline-flex; align-items: center; gap: 6px; font-size: .86rem; font-weight: 600; white-space: nowrap; }
    .status::before { content: ""; width: 9px; height: 9px; border-radius: 50%; background: currentColor; }
    .status.aktif { color: #17803d; }
    .status.nonaktif { color: #8a93a0; }
    .tab-peran .nav-link { padding: .35rem .8rem; font-size: .92rem; }
    .tab-peran .nav-link .badge { font-weight: 600; }
    .paginasi-id nav p.small { display: none; }          /* ringkasan bahasa Inggris bawaan Laravel */
    .paginasi-id nav > div:last-child > div:first-child { display: none; }
    .paginasi-id nav > div:last-child { justify-content: flex-end !important; }
</style>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="fw-semibold">Akun pengguna</span>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalBuat">+ Buat akun</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalImport">Import dari {{ $bisaExcel ? 'Excel' : 'CSV' }}</button>
            @php
                $bisaHapusSemua = in_array($peran, ['guru', 'wali', 'bk', 'waka'], true) || ($peran === 'siswa' && $kelas !== '');
                if ($peran === 'siswa' && $kelas !== '') {
                    $kelasTerpilih = $kelas === 'none' ? null : $daftarKelas->firstWhere('id_kelas', $kelas);
                    $namaHapus   = $kelas === 'none' ? 'siswa tanpa kelas' : 'siswa kelas ' . ($kelasTerpilih ? $labelKelas($kelasTerpilih) : $kelas);
                    $jumlahHapus = $kelas === 'none' ? $siswaTanpaKelas : ($jumlahSiswaKelas[$kelas] ?? 0);
                } elseif ($bisaHapusSemua) {
                    $namaHapus   = $daftarTab[$peran][1];
                    $jumlahHapus = $jumlah[$peran] ?? 0;
                }
            @endphp
            @if($bisaHapusSemua)
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapusSemua" {{ $jumlahHapus < 1 ? 'disabled' : '' }}>Hapus semua {{ $namaHapus }}</button>
            @endif
        </div>
    </div>

    <div class="card-body">
        {{-- Filter peran + pencarian --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <ul class="nav nav-pills tab-peran gap-1">
                <li class="nav-item">
                    <a class="nav-link {{ $peran === '' ? 'active' : '' }}" href="{{ route('akun.index', array_filter(['q' => $q, 'per' => $per != 25 ? $per : null])) }}">
                        Semua <span class="badge {{ $peran === '' ? 'bg-light text-dark' : 'bg-secondary' }}">{{ $totalAkun }}</span>
                    </a>
                </li>
                @foreach($daftarTab as $kode => [$idPeran, $label])
                    <li class="nav-item">
                        <a class="nav-link {{ $peran === $kode ? 'active' : '' }}" href="{{ route('akun.index', array_filter(['peran' => $kode, 'q' => $q, 'per' => $per != 25 ? $per : null])) }}">
                            {{ $label }} <span class="badge {{ $peran === $kode ? 'bg-light text-dark' : 'bg-secondary' }}">{{ $jumlah[$kode] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <form method="GET" action="{{ route('akun.index') }}" class="d-flex gap-1">
                @if($peran !== '')<input type="hidden" name="peran" value="{{ $peran }}">@endif
                @if($per != 25)<input type="hidden" name="per" value="{{ $per }}">@endif
                @if($peran === 'siswa')
                    <select name="kelas" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" title="Filter siswa per kelas">
                        <option value="">Semua kelas</option>
                        @foreach($daftarKelas as $k)
                            <option value="{{ $k->id_kelas }}" {{ $kelas === (string) $k->id_kelas ? 'selected' : '' }}>
                                {{ $labelKelas($k) }} ({{ $jumlahSiswaKelas[$k->id_kelas] ?? 0 }})
                            </option>
                        @endforeach
                        @if($siswaTanpaKelas > 0)
                            <option value="none" {{ $kelas === 'none' ? 'selected' : '' }}>Belum ada kelas ({{ $siswaTanpaKelas }})</option>
                        @endif
                    </select>
                @endif
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" style="width:230px" placeholder="Cari nama atau NIP/NISN…">
                <button class="btn btn-sm btn-outline-secondary">Cari</button>
                @if($q !== '' || $kelas !== '')<a href="{{ route('akun.index', array_filter(['peran' => $peran, 'per' => $per != 25 ? $per : null])) }}" class="btn btn-sm btn-light">{{ $kelas !== '' ? 'Reset filter' : 'Hapus pencarian' }}</a>@endif
            </form>
        </div>

        @php
            $tampilPeran = $peran === '';
            $tampilKelas = in_array($peran, ['', 'wali', 'siswa'], true);
            $jumlahKolom = 5 + ($tampilPeran ? 1 : 0) + ($tampilKelas ? 1 : 0);
        @endphp

        <div class="table-responsive tabel-akun">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:48px">No</th>
                        <th>Nama</th>
                        <th>NIP / NISN</th>
                        @if($tampilPeran)<th>Peran</th>@endif
                        @if($tampilKelas)<th>{{ $peran === 'wali' ? 'Kelas diampu' : 'Kelas' }}</th>@endif
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $i => $u)
                        @php
                            $siswa = (int) $u->id_role === 5;
                            $idWali = $waliKelas[$u->username] ?? [];
                            $teksKelas = null;
                            if ($siswa) {
                                $kelasUser = $daftarKelas->firstWhere('id_kelas', $u->id_kelas);
                                $teksKelas = $kelasUser ? $labelKelas($kelasUser) : null;
                            } elseif ((int) $u->id_role === 3) {
                                $teksKelas = $daftarKelas->whereIn('id_kelas', $idWali)->map($labelKelas)->implode(', ') ?: null;
                            }
                            $diriSendiri = (int) $u->id_user === (int) session('id_user');
                        @endphp
                        <tr class="{{ $u->is_aktif ? '' : 'baris-nonaktif' }}">
                            <td class="text-muted">{{ $users->firstItem() + $i }}</td>
                            <td>
                                <div class="nama">{{ trim($u->nama_lengkap) }}@if($diriSendiri) <span class="badge bg-info text-dark">Anda</span>@endif</div>
                                @if($u->no_hp)<div class="small text-muted">HP {{ $u->no_hp }}</div>@endif
                            </td>
                            <td class="kode-user">{{ $u->username }}</td>
                            @if($tampilPeran)
                                <td><span class="badge {{ $warnaPeran[$u->id_role] ?? 'bg-secondary' }}">{{ $namaPeran[$u->id_role] ?? 'Peran ' . $u->id_role }}</span></td>
                            @endif
                            @if($tampilKelas)
                                <td>
                                    @if($teksKelas)
                                        {{ $teksKelas }}
                                    @elseif($siswa)
                                        <span class="badge text-bg-warning">Belum ada kelas</span>
                                    @elseif((int) $u->id_role === 3)
                                        <span class="text-muted">Belum mengampu kelas</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td><span class="status {{ $u->is_aktif ? 'aktif' : 'nonaktif' }}">{{ $u->is_aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-ubah" title="Ubah nama, no. HP, peran atau kelas"
                                        data-id="{{ $u->id_user }}"
                                        data-username="{{ $u->username }}"
                                        data-nama="{{ trim($u->nama_lengkap) }}"
                                        data-hp="{{ $u->no_hp }}"
                                        data-role="{{ $u->id_role }}"
                                        data-kelas="{{ $u->id_kelas ?? '' }}"
                                        data-wali="{{ json_encode($idWali) }}">Ubah</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-sandi" title="Buat password baru untuk akun ini"
                                        data-id="{{ $u->id_user }}" data-nama="{{ trim($u->nama_lengkap) }}">Ganti password</button>
                                @unless($diriSendiri)
                                    <form action="{{ route('akun.status', $u->id_user) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('{{ $u->is_aktif ? 'Nonaktifkan akun ini? Pemilik akun tidak akan bisa login.' : 'Aktifkan kembali akun ini?' }}')">
                                        @csrf
                                        <button class="btn btn-sm {{ $u->is_aktif ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                title="{{ $u->is_aktif ? 'Akun tidak bisa login' : 'Akun bisa login lagi' }}">
                                            {{ $u->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $jumlahKolom }}" class="text-center text-muted py-5">
                            {{ ($q !== '' || $peran !== '' || $kelas !== '') ? 'Tidak ada akun yang cocok dengan pencarian atau filter ini.' : 'Belum ada akun. Klik “+ Buat akun” atau “Import”.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->total() > 0)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <small class="text-muted">Menampilkan <strong>{{ $users->firstItem() }}–{{ $users->lastItem() }}</strong> dari <strong>{{ $users->total() }}</strong> akun</small>
                    <form method="GET" action="{{ route('akun.index') }}" class="d-flex align-items-center gap-1">
                        @if($peran !== '')<input type="hidden" name="peran" value="{{ $peran }}">@endif
                        @if($q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
                        @if($kelas !== '')<input type="hidden" name="kelas" value="{{ $kelas }}">@endif
                        <label class="small text-muted mb-0" for="per">Per halaman</label>
                        <select name="per" id="per" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $per == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="paginasi-id">{{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif
    </div>
</div>

{{-- ============ MODAL BUAT AKUN ============ --}}
<div class="modal fade" id="modalBuat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat akun baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if($errors->any() && old('id_role') !== null)
                    <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                <form action="{{ route('akun.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Peran</label>
                        <select name="id_role" id="buat_peran" class="form-select" required>
                            <option value="">-- Pilih peran --</option>
                            @foreach($urutanBuat as $idPeran)
                                <option value="{{ $idPeran }}" {{ (string) old('id_role', $peran !== '' ? $daftarTab[$peran][0] : '') === (string) $idPeran ? 'selected' : '' }}>{{ $namaPeran[$idPeran] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username (<span id="buat_labelUser">NIP / NISN</span>)</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. HP <span class="text-muted">(opsional)</span></label>
                        <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                    </div>

                    {{-- Muncul bila peran = Siswa --}}
                    <div class="mb-3" id="buat_grupKelas" style="display:none">
                        <label class="form-label">Kelas</label>
                        <select name="id_kelas" id="buat_kelas" class="form-select">
                            <option value="">-- Pilih kelas --</option>
                            @foreach($daftarKelas as $k)
                                <option value="{{ $k->id_kelas }}" {{ old('id_kelas') == $k->id_kelas ? 'selected' : '' }}>{{ $labelKelas($k) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Muncul bila peran = Wali kelas --}}
                    <div class="mb-3" id="buat_grupWali" style="display:none">
                        <label class="form-label">Kelas yang diampu <span class="text-muted">(opsional)</span></label>
                        <select name="kelas_wali[]" class="form-select" multiple size="6">
                            @foreach($daftarKelas as $k)
                                <option value="{{ $k->id_kelas }}" {{ in_array($k->id_kelas, (array) old('kelas_wali', [])) ? 'selected' : '' }}>
                                    {{ $labelKelas($k) }}{{ $k->nama_wali ? ' — wali saat ini: ' . $k->nama_wali : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Tahan Ctrl (Cmd di Mac) untuk memilih lebih dari satu.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Simpan akun</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ============ MODAL HAPUS SEMUA ============ --}}
@if($bisaHapusSemua)
<div class="modal fade" id="modalHapusSemua" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Hapus semua {{ $namaHapus }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('akun.hapusSemua') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" name="peran_hapus" value="{{ $peran }}">
                <input type="hidden" name="kelas_hapus" value="{{ $kelas }}">
                <div class="modal-body">
                    <div class="alert alert-danger small">
                        <strong>{{ $jumlahHapus }} {{ $peran === 'siswa' ? 'siswa' : 'akun' }}</strong> akan dihapus <strong>permanen</strong> dan tidak bisa dikembalikan.
                        @if($peran === 'siswa')
                            Yang ikut terhapus: akun login, data siswa, dan seluruh riwayat presensi/absensi siswa tersebut. Jurnal dan jadwal tidak terpengaruh.
                        @elseif($peran === 'wali')
                            Mereka tidak bisa login lagi dan penetapan wali di kelasnya dilepas. Data guru, jadwal, dan jurnal tidak terhapus.
                        @else
                            Mereka tidak bisa login lagi. Data guru, jadwal, dan jurnal tidak terhapus.
                        @endif
                        @if($peran === 'waka') Akun Anda sendiri tidak ikut dihapus. @endif
                    </div>
                    <label class="form-label small" for="konfirmasi_hapus">Ketik <strong>HAPUS</strong> untuk melanjutkan</label>
                    <input type="text" name="konfirmasi" id="konfirmasi_hapus" class="form-control" autocomplete="off" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="tombol_hapus_semua" disabled>Hapus semua</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ============ MODAL IMPORT ============ --}}
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import akun dari {{ $bisaExcel ? 'Excel' : 'CSV' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if($errors->any() && old('peran') !== null)
                    <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                <form action="{{ route('akun.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Peran untuk seluruh isi berkas</label>
                        <select name="peran" id="imp_peran" class="form-select" required>
                            @foreach($daftarTab as $kode => [$idPeran, $label])
                                <option value="{{ $kode }}" {{ (old('peran') ?? $peran) === $kode ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <p class="small text-muted mb-2">
                        Judul kolom: <code id="imp_kolom"></code><br>
                        <span id="imp_catatan"></span>
                    </p>
                    <div class="mb-3">
                        <a href="{{ route('akun.template') }}" id="imp_template" class="btn btn-sm btn-success">
                            {{ $bisaExcel ? 'Unduh template Excel (.xlsx)' : 'Unduh template CSV' }}
                        </a>
                    </div>

                    <div class="mb-3">
                        <input type="file" name="berkas" class="form-control" accept="{{ $bisaExcel ? '.xlsx' : '.csv,.txt' }}" required>
                    </div>
                    <div class="mb-3 border rounded p-2 bg-light">
                        <div class="fw-semibold small mb-1">Filter baris (opsional)</div>
                        <div class="row g-2">
                            <div class="col-5">
                                <input type="text" name="filter_kolom" id="imp_filter_kolom" list="daftarKolomFilter" maxlength="60"
                                       class="form-control form-control-sm" placeholder="Kolom, mis. MAPEL" value="{{ old('filter_kolom') }}" autocomplete="off">
                            </div>
                            <div class="col-7">
                                <input type="text" name="filter_nilai" id="imp_filter_nilai" maxlength="200"
                                       class="form-control form-control-sm" placeholder="Berisi, mis. BIMBINGAN KONSELING" value="{{ old('filter_nilai') }}" autocomplete="off">
                            </div>
                        </div>
                        <datalist id="daftarKolomFilter">
                            <option value="MAPEL"><option value="JABATAN"><option value="PENDIDIKAN"><option value="GOLONGAN">
                            <option value="JENIS KELAMIN"><option value="TUGAS POKOK"><option value="STATUS">
                        </datalist>
                        <div class="form-text">
                            Hanya baris yang kolomnya berisi kata itu yang diimpor (huruf besar/kecil tidak berpengaruh). Beberapa kata dipisah koma, salah satu cocok sudah cukup. Kosongkan keduanya untuk mengimpor semua baris.
                            <a href="#" id="imp_contoh_bk">Isi contoh Guru BK</a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kalau username sudah ada</label>
                        <select name="mode" class="form-select">
                            <option value="lewati">Lewati (jangan diubah)</option>
                            <option value="perbarui">Perbarui nama, no. HP, dan kelas</option>
                        </select>
                        <div class="form-text">Password akun yang sudah ada tidak diubah lewat import.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password awal (opsional)</label>
                        <input type="text" name="password_awal" class="form-control" minlength="6" maxlength="100"
                               value="{{ old('password_awal') }}" autocomplete="off"
                               placeholder="Kosongkan = pakai NIP / NISN masing-masing">
                        <div class="form-text">Dipakai untuk akun baru yang kolom password-nya kosong atau tidak ada di berkas. Sarankan pengguna mengganti password setelah login pertama.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Import</button>
                </form>
                <p class="small text-muted mt-2 mb-1">Berkas data pegawai biasa (mis. judul tabel di atas, kolom NIP dan NAMA) juga bisa langsung diimpor: judul kolom dicari otomatis di 10 baris pertama, kolom password boleh tidak ada, dan baris tanpa NIP/NISN dilewati.</p>
<p class="small text-muted mt-2 mb-0">Pakai template Excel di atas: semua kolomnya sudah berformat Teks, jadi NIP 18 digit dan angka 0 di depan NISN tidak dirusak Excel. Kalau ada satu baris yang salah, seluruh import dibatalkan dan nomor baris yang salah ditampilkan.</p>
            </div>
        </div>
    </div>
</div>

{{-- ============ MODAL UBAH ============ --}}
<div class="modal fade" id="modalUbah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="formUbah" method="POST" action="">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" id="ubah_username" class="form-control" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama lengkap</label>
                    <input type="text" name="nama_lengkap" id="ubah_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="no_hp" id="ubah_hp" class="form-control">
                </div>

                <div class="mb-3" id="grupRole">
                    <label class="form-label">Peran</label>
                    <select name="id_role" id="ubah_role" class="form-select">
                        @foreach($urutanBuat as $idPeran)
                            @continue($idPeran === 5)
                            <option value="{{ $idPeran }}">{{ $namaPeran[$idPeran] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3" id="grupKelas">
                    <label class="form-label">Kelas</label>
                    <select name="id_kelas" id="ubah_kelas" class="form-select">
                        <option value="">-- Pilih kelas --</option>
                        @foreach($daftarKelas as $k)
                            <option value="{{ $k->id_kelas }}">{{ $labelKelas($k) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3" id="grupWali">
                    <label class="form-label">Kelas yang diampu</label>
                    <select name="kelas_wali[]" id="ubah_wali" class="form-select" multiple size="6">
                        @foreach($daftarKelas as $k)
                            <option value="{{ $k->id_kelas }}">{{ $labelKelas($k) }}{{ $k->nama_wali ? ' — wali saat ini: ' . $k->nama_wali : '' }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Tahan Ctrl (Cmd di Mac) untuk memilih lebih dari satu.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- ============ MODAL GANTI PASSWORD ============ --}}
<div class="modal fade" id="modalSandi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="formSandi" method="POST" action="">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Ganti password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Password baru untuk <strong id="sandi_nama"></strong>.</p>
                <input type="password" name="password" class="form-control" minlength="6" required placeholder="Minimal 6 karakter">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning">Ganti password</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const el = (id) => document.getElementById(id);

    // ---------- Form buat akun: bidang menyesuaikan peran ----------
    function aturBuat() {
        const r = el('buat_peran').value;
        el('buat_grupKelas').style.display = r === '5' ? 'block' : 'none';
        el('buat_kelas').required = r === '5';
        el('buat_grupWali').style.display = r === '3' ? 'block' : 'none';
        el('buat_labelUser').textContent = r === '5' ? 'NISN' : (r === '' ? 'NIP / NISN' : 'NIP');
    }
    el('buat_peran').addEventListener('change', aturBuat);
    aturBuat();

    // ---------- Import: judul kolom & template menyesuaikan peran ----------
    const kolomImport = {
        guru:  ['username, nama_lengkap, password, no_hp', 'Kolom no_hp boleh kosong. Baris yang MAPEL, JABATAN, atau TUGAS POKOK-nya Guru BK (Bimbingan Konseling) atau Waka otomatis dilewati.'],
        wali:  ['username, nama_lengkap, password, no_hp, kelas_wali', 'no_hp dan kelas_wali boleh kosong. Kelas diisi kode atau nama kelas (contoh: X RPL 1); beberapa kelas dipisah tanda |. Daftar wali kelas tanpa NIP (kolom KELAS dan NAMA WALI KELAS) juga bisa langsung diimpor: nama wali dicocokkan dengan akun guru yang sudah ada, lalu perannya menjadi Wali kelas.'],
        bk:    ['username, nama_lengkap, password, no_hp', 'Kolom no_hp boleh kosong.'],
        waka:  ['username, nama_lengkap, password, no_hp', 'Kolom no_hp boleh kosong.'],
        siswa: ['username, nama_lengkap, password, no_hp, kelas', 'Kolom kelas wajib: diisi kode atau nama kelas (contoh: X RPL 1). Username = NISN.'],
    };
    const urlTemplate = @json(route('akun.template'));
    function aturImport() {
        const p = el('imp_peran').value;
        el('imp_kolom').textContent = kolomImport[p][0];
        el('imp_catatan').textContent = kolomImport[p][1];
        el('imp_template').href = urlTemplate + '?peran=' + encodeURIComponent(p);
        if (el('imp_template_csv')) { el('imp_template_csv').href = urlTemplate + '?peran=' + encodeURIComponent(p) + '&format=csv'; }
    }
    el('imp_peran').addEventListener('change', aturImport);
    aturImport();
    if (el('konfirmasi_hapus')) {
        el('konfirmasi_hapus').addEventListener('input', function () {
            el('tombol_hapus_semua').disabled = this.value !== 'HAPUS';
        });
    }
    el('imp_contoh_bk').addEventListener('click', function (e) {
        e.preventDefault();
        el('imp_filter_kolom').value = 'MAPEL';
        el('imp_filter_nilai').value = 'BIMBINGAN KONSELING';
        el('imp_peran').value = 'bk';
        aturImport();
    });

    // ---------- Buka lagi modal bila validasi gagal ----------
    @if($errors->any() && old('id_role') !== null)
        new bootstrap.Modal(el('modalBuat')).show();
    @elseif($errors->any() && old('peran') !== null)
        new bootstrap.Modal(el('modalImport')).show();
    @endif

    // ---------- Modal ubah & ganti password ----------
    const urlUbah  = @json(route('akun.update', ['id' => '__ID__']));
    const urlSandi = @json(route('akun.password', ['id' => '__ID__']));
    const modalUbah = new bootstrap.Modal(el('modalUbah'));
    const modalSandi = new bootstrap.Modal(el('modalSandi'));

    function aturWali() {
        const siswa = el('ubah_role').disabled;
        el('grupWali').style.display = (!siswa && el('ubah_role').value === '3') ? 'block' : 'none';
    }
    el('ubah_role').addEventListener('change', aturWali);

    document.querySelectorAll('.btn-ubah').forEach(function (t) {
        t.addEventListener('click', function () {
            const siswa = t.dataset.role === '5';
            el('formUbah').action = urlUbah.replace('__ID__', t.dataset.id);
            el('ubah_username').value = t.dataset.username;
            el('ubah_nama').value = t.dataset.nama;
            el('ubah_hp').value = t.dataset.hp || '';

            // Siswa: peran tetap, hanya kelas. Staf: bisa ganti peran, tanpa kelas siswa.
            el('grupRole').style.display = siswa ? 'none' : 'block';
            el('ubah_role').disabled = siswa;
            if (!siswa) { el('ubah_role').value = t.dataset.role; }
            el('grupKelas').style.display = siswa ? 'block' : 'none';
            el('ubah_kelas').disabled = !siswa;
            el('ubah_kelas').required = siswa;
            el('ubah_kelas').value = siswa ? t.dataset.kelas : '';

            const dipilih = JSON.parse(t.dataset.wali || '[]').map(String);
            Array.from(el('ubah_wali').options).forEach(function (o) { o.selected = dipilih.includes(o.value); });
            aturWali();
            modalUbah.show();
        });
    });

    document.querySelectorAll('.btn-sandi').forEach(function (t) {
        t.addEventListener('click', function () {
            el('formSandi').action = urlSandi.replace('__ID__', t.dataset.id);
            el('sandi_nama').textContent = t.dataset.nama;
            modalSandi.show();
        });
    });
</script>
@endpush
