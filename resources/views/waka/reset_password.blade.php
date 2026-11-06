@extends('layouts.app')

@section('judul', 'Reset password')
@section('sub', 'Atur atau buat password baru untuk pengguna yang meminta reset maupun reset akun langsung.')

@section('isi')
@php
    $namaRole = [1 => 'Admin', 2 => 'Guru', 3 => 'Wali kelas', 4 => 'Waka kurikulum', 5 => 'Siswa', 7 => 'Guru BK'];
@endphp

@if(session('password_baru'))
    @php $pb = session('password_baru'); @endphp
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>✅ Password Berhasil Diperbarui!</strong>
        <div class="mt-2">Nama: <strong>{{ $pb['nama'] }}</strong> | Username / NIP / NISN: <code>{{ $pb['username'] }}</code></div>
        <div class="mt-2">Password Baru: <code class="fs-5 user-select-all bg-white px-2 py-1 rounded border border-success">{{ $pb['password'] }}</code></div>
        <div class="mt-2 small text-muted">Silakan sampaikan password baru ini kepada guru atau siswa yang bersangkutan.</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4 mb-4">
    {{-- Form Reset Password Langsung --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Reset Password Langsung</div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Ubah password akun pengguna mana pun secara langsung tanpa perlu menunggu permintaan.
                </p>
                <form action="{{ route('reset.manual') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Pilih Akun Pengguna</label>
                        <select name="username" class="form-select" required>
                            <option value="">-- Pilih Pengguna --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->username }}">
                                    {{ $u->nama_lengkap }} ({{ $u->username }} - {{ $namaRole[$u->id_role] ?? 'Role '.$u->id_role }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password Baru (Buat Sendiri)</label>
                        <div class="input-group">
                            <input type="password" name="password_baru" id="pwdManual" class="form-control"
                                   placeholder="Minimal 6 karakter..." required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleLihat('pwdManual', this)">Lihat</button>
                        </div>
                        <div class="form-text">Tentukan password sesuai keinginan Anda.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Simpan Password Baru</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Daftar Permintaan Reset yang Masuk --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Permintaan Lupa Password Masuk</span>
                <span class="badge bg-primary">{{ $menunggu->count() }} Menunggu</span>
            </div>
            <div class="card-body tabel-luas">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pemohon</th>
                            <th>Kontak</th>
                            <th style="min-width: 250px;">Buat Password Sendiri</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menunggu as $m)
                        <tr>
                            <td class="text-nowrap small text-muted">
                                {{ \Illuminate\Support\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <strong>{{ $m->nama_lengkap ?? '-' }}</strong><br>
                                <small class="text-muted">{{ $m->username }} ({{ $namaRole[$m->id_role] ?? '-' }})</small>
                            </td>
                            <td><small>{{ $m->kontak ?: '-' }}</small></td>
                            <td>
                                <form action="{{ route('reset.proses', $m->id) }}" method="POST" class="d-flex gap-1 align-items-center">
                                    @csrf
                                    <input type="text" name="password_baru" class="form-control form-control-sm"
                                           placeholder="Ketik password baru..." required minlength="6">
                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap"
                                            onclick="return confirm('Ubah password akun {{ $m->username }}?')">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('reset.tolak', $m->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Tolak permintaan reset password dari {{ $m->username }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Tolak</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Tidak ada permintaan reset password yang menunggu.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Riwayat Terakhir --}}
<div class="card">
    <div class="card-header">Riwayat Permintaan Terakhir</div>
    <div class="card-body tabel-luas">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Waktu Diproses</th>
                    <th>Username</th>
                    <th>Nama Pengguna</th>
                    <th>Peran</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $r)
                <tr>
                    <td class="text-nowrap">{{ $r->diproses_at ? \Illuminate\Support\Carbon::parse($r->diproses_at)->format('d/m/Y H:i') : '-' }}</td>
                    <td><code>{{ $r->username }}</code></td>
                    <td>{{ $r->nama_lengkap ?? '-' }}</td>
                    <td>{{ $namaRole[$r->id_role] ?? '-' }}</td>
                    <td>
                        @if($r->status == 'Selesai')
                            <span class="badge bg-success">Selesai (Diperbarui)</span>
                        @else
                            <span class="badge bg-danger">Ditolak</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat.</td></tr>
                @endforelse
            </tbody>
        </table>
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
