@extends('layouts.app')

@section('judul', 'Profil saya')
@section('sub', 'Informasi akun, perincian peran, dan pengaturan keamanan.')

@section('isi')
@php
    $badgeWarna = [
        1 => 'bg-secondary',
        2 => 'bg-primary',
        3 => 'bg-success',
        4 => 'bg-danger',
        5 => 'bg-info text-dark',
        7 => 'bg-warning text-dark',
    ];
@endphp

<div class="row g-4">
    {{-- Kolom Kiri: Kartu Identitas & Statistik --}}
    <div class="col-lg-4">
        <div class="card mb-4 text-center">
            <div class="card-body py-4">
                {{-- Avatar Inisial --}}
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white fs-2 fw-bold mb-3 shadow-sm"
                     style="width: 80px; height: 80px;">
                    {{ strtoupper(substr($user->nama_lengkap, 0, 1)) }}
                </div>
                <h4 class="fw-bold mb-1">{{ $user->nama_lengkap }}</h4>
                <div class="text-muted small mb-2">{{ $user->username }}</div>
                <span class="badge {{ $badgeWarna[$user->id_role] ?? 'bg-secondary' }} px-3 py-2 fs-6">
                    {{ $peran }}
                </span>

                <hr class="my-3">

                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Username / ID:</span>
                        <span class="fw-semibold">{{ $user->username }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Nomor HP:</span>
                        <span class="fw-semibold">{{ $user->no_hp ?: '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Status Akun:</span>
                        <span class="badge bg-success">Aktif</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Spesifik Sesuai Role --}}
        @if($user->id_role == 5 && $siswa)
            {{-- Role Siswa --}}
            <div class="card">
                <div class="card-header">Data Akademik Siswa</div>
                <div class="card-body small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">NISN:</span>
                        <span class="fw-semibold">{{ $siswa->nisn }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Kelas:</span>
                        <span class="fw-semibold">
                            @if($siswa->id_kelas)
                                <span class="badge bg-primary">{{ optional($siswa->kelas)->nama_kelas ?? $siswa->id_kelas }}</span>
                            @else
                                <span class="text-muted">Belum ditetapkan</span>
                            @endif
                        </span>
                    </div>
                    @if($siswa->kelas && $siswa->kelas->waliKelas)
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Wali Kelas:</span>
                        <span class="fw-semibold">{{ $siswa->kelas->waliKelas->nama_guru }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Status Kesiswaan:</span>
                        <span class="badge bg-success">{{ $siswa->status_utama }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Total Pengajuan Izin:</span>
                        <span class="fw-bold">{{ $totalIzin }} kali</span>
                    </div>
                </div>
            </div>
        @elseif(in_array($user->id_role, [2, 3, 4, 7]))
            {{-- Role Guru, Wali Kelas, Waka, BK --}}
            <div class="card">
                <div class="card-header">Data Pendidik &amp; Tugas</div>
                <div class="card-body small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">NIP:</span>
                        <span class="fw-semibold">{{ $guru ? $guru->nip : $user->username }}</span>
                    </div>
                    @if($kelasWali->isNotEmpty())
                    <div class="py-1 border-bottom">
                        <span class="text-muted d-block mb-1">Wali Kelas Untuk:</span>
                        @foreach($kelasWali as $kw)
                            <span class="badge bg-success mb-1">{{ trim($kw->tingkat . ' ' . $kw->id_jurusan) ?: $kw->id_kelas }}</span>
                        @endforeach
                    </div>
                    @endif
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Jadwal Mengajar:</span>
                        <span class="fw-semibold">{{ $totalJadwal }} sesi</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Jurnal KBM Terisi:</span>
                        <span class="fw-semibold">{{ $totalJurnal }} kali</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Kolom Kanan: Edit Profil & Ganti Password --}}
    <div class="col-lg-8">
        {{-- Card Ubah Profil --}}
        <div class="card mb-4">
            <div class="card-header">Perbarui Data Diri</div>
            <div class="card-body">
                <form action="{{ route('profil.update') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Username / NIP / NISN</label>
                        <input type="text" class="form-control bg-light" value="{{ $user->username }}" readonly disabled>
                        <div class="form-text">Username adalah identitas resmi Anda (NIP/NISN) dan tidak dapat diubah sendiri.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control"
                               value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor WhatsApp / HP</label>
                        <input type="text" name="no_hp" class="form-control"
                               value="{{ old('no_hp', $user->no_hp) }}" placeholder="Contoh: 08123456789" maxlength="20">
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Perubahan Profil</button>
                </form>
            </div>
        </div>

        {{-- Card Ganti Password --}}
        <div class="card">
            <div class="card-header">Ganti Password Akun</div>
            <div class="card-body">
                <form action="{{ route('profil.updatePassword') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_lama" id="pwdLama" class="form-control" required placeholder="Masukkan password saat ini">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleLihat('pwdLama', this)">Lihat</button>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_baru" id="pwdBaru" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleLihat('pwdBaru', this)">Lihat</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_konfirmasi" id="pwdKonf" class="form-control" required minlength="6" placeholder="Ulangi password baru">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleLihat('pwdKonf', this)">Lihat</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-primary">Perbarui Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLihat(inputId, btn) {
    var isian = document.getElementById(inputId);
    if (!isian) return;
    if (isian.type === 'password') {
        isian.type = 'text';
        btn.textContent = 'Sembunyikan';
    } else {
        isian.type = 'password';
        btn.textContent = 'Lihat';
    }
}
</script>
@endsection

