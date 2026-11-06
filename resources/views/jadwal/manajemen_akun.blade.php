@extends('layouts.app')

@section('judul', 'Kelola akun')
@section('sub', 'Buat akun, atur peran, dan tetapkan kelas siswa agar bisa diabsen guru.')

@section('isi')
<div class="row g-4">
    <!-- Form Buat Akun Baru -->
    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-header">Buat akun & atur peran</div>
            <div class="card-body">
                <form action="{{ route('akun.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Username (NIP / NISN)</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username') }}" required placeholder="NIP guru atau NISN siswa">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" required placeholder="Contoh: Budi Santoso, S.Pd.">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Peran / Hak Akses</label>
                        <select name="id_role" id="id_role" class="form-select" required>
                            <option value="">-- Pilih Peran --</option>
                            <option value="4" {{ old('id_role') == '4' ? 'selected' : '' }}>Waka Kurikulum</option>
                            <option value="2" {{ old('id_role') == '2' ? 'selected' : '' }}>Guru Pengampu</option>
                            <option value="3" {{ old('id_role') == '3' ? 'selected' : '' }}>Wali Kelas</option>
                            <option value="7" {{ old('id_role') == '7' ? 'selected' : '' }}>Guru BK</option>
                            <option value="5" {{ old('id_role') == '5' ? 'selected' : '' }}>Siswa</option>
                        </select>
                    </div>

                    <!-- Muncul hanya jika peran = Siswa -->
                    <div class="mb-3" id="wrap-kelas" style="display:none;">
                        <label class="form-label">Kelas Siswa</label>
                        <select name="id_kelas" id="id_kelas" class="form-select">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($kelas as $k)
                                <option value="{{ $k->id_kelas }}" {{ old('id_kelas') == $k->id_kelas ? 'selected' : '' }}>
                                    {{ $k->tingkat }} {{ $k->id_jurusan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Simpan akun baru</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Akun -->
    <div class="col-xl-8 col-lg-7">
        <div class="card">
            <div class="card-header">Daftar akun terdaftar</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Username</th>
                                <th>Nama</th>
                                <th>Peran</th>
                                <th style="min-width:240px">Kelas (siswa)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $namaPeran = [1 => 'Admin', 2 => 'Guru', 3 => 'Wali Kelas', 4 => 'Waka', 5 => 'Siswa', 6 => 'Orang Tua', 7 => 'BK']; @endphp
                            @forelse($users as $index => $u)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $u->username }}</td>
                                    <td>{{ trim($u->nama_lengkap) }}</td>
                                    <td>
                                        <span class="badge {{ $u->id_role == 4 ? 'bg-danger' : ($u->id_role == 5 ? 'bg-secondary' : 'bg-primary') }}">
                                            {{ $namaPeran[$u->id_role] ?? 'Peran '.$u->id_role }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($u->id_role == 5)
                                            <form action="{{ route('akun.kelas.update') }}" method="POST" class="d-flex gap-1">
                                                @csrf
                                                <input type="hidden" name="username" value="{{ $u->username }}">
                                                <select name="id_kelas" class="form-select form-select-sm" required>
                                                    <option value="">-- Belum ada kelas --</option>
                                                    @foreach($kelas as $k)
                                                        <option value="{{ $k->id_kelas }}" {{ $u->id_kelas == $k->id_kelas ? 'selected' : '' }}>
                                                            {{ $k->tingkat }} {{ $k->id_jurusan }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-sm btn-primary">Simpan</button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data akun.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const roleSel   = document.getElementById('id_role');
    const wrapKelas = document.getElementById('wrap-kelas');
    const kelasSel  = document.getElementById('id_kelas');
    function aturKelas() {
        const siswa = roleSel.value === '5';
        wrapKelas.style.display = siswa ? 'block' : 'none';
        kelasSel.required = siswa;
    }
    roleSel.addEventListener('change', aturKelas);
    aturKelas();
</script>
@endpush
