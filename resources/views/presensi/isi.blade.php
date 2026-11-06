@extends('layouts.app')

@section('judul', 'Presensi siswa')
@section('sub', 'Kelas ' . (optional($jadwal->kelas)->nama_kelas ?? $jadwal->id_kelas) . ' — ' . ($jadwal->mataPelajaran->nama_mapel ?? $jadwal->id_mapel))

@section('isi')

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

@if($sudahIsi)
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        Presensi untuk jadwal ini pada <strong>{{ \Carbon\Carbon::parse($sudahIsi->tanggal)->translatedFormat('d M Y') }}</strong>
        sudah diisi.
        <a href="{{ route('presensi.detail', $sudahIsi->id_presensi) }}" class="ms-auto btn btn-sm btn-outline-warning">Lihat detail</a>
    </div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Presensi siswa: lanjutan dari jurnal "{{ \Illuminate\Support\Str::limit($jurnal->topic_description, 60) }}"</span>
        <a href="{{ route('jurnal.index') }}" class="btn btn-sm btn-outline-secondary">← Kembali</a>
    </div>
    <div class="card-body">

        {{-- Info Jadwal --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Kelas</div>
                    <div class="fw-semibold">{{ optional($jadwal->kelas)->nama_kelas ?? $jadwal->id_kelas }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Mata Pelajaran</div>
                    <div class="fw-semibold">{{ $jadwal->mataPelajaran->nama_mapel ?? $jadwal->id_mapel }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Jam</div>
                    <div class="fw-semibold">{{ $jadwal->jam_mulai }} – {{ $jadwal->jam_selesai }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Hari</div>
                    <div class="fw-semibold">{{ $jadwal->hari }}</div>
                </div>
            </div>
        </div>

        @if($siswaList->isEmpty())
            <div class="alert alert-info">
                Tidak ada siswa aktif yang terdaftar di kelas <strong>{{ optional($jadwal->kelas)->nama_kelas ?? $jadwal->id_kelas }}</strong>.<br>
                <small class="text-muted">Pastikan data siswa sudah ditetapkan ke kelas ini melalui halaman manajemen akun.</small>
            </div>
        @else
        <form action="{{ route('presensi.simpan') }}" method="POST">
            @csrf

            <input type="hidden" name="id_jurnal" value="{{ $jurnal->id }}">

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Tanggal KBM (sesuai jurnal)</label>
                    <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($jurnal->date)->translatedFormat('d M Y') }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ruangan yang digunakan <span class="text-danger">*</span></label>
                    <select name="id_ruangan_digunakan" class="form-select" required>
                        <option value="{{ $jadwal->id_ruangan_default }}" selected>
                            {{ $jadwal->id_ruangan_default }} (default)
                        </option>
                        {{-- Jika ingin memilih ruangan lain, bisa ditambahkan loop ruangan di sini --}}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Catatan KBM</label>
                    <input type="text" name="catatan_kbm" class="form-control" placeholder="Opsional…" maxlength="500">
                </div>
            </div>

            {{-- Tombol centang cepat --}}
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <span class="text-muted small align-self-center">Centang cepat:</span>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="setAll('Hadir')">Semua Hadir</button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="setAll('Alfa')">Semua Alfa</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:3rem">#</th>
                            <th>Nama Siswa</th>
                            <th>NISN</th>
                            <th style="min-width:260px">Status Kehadiran <span class="text-danger">*</span></th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($siswaList as $i => $siswa)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $siswa->nama_siswa }}</td>
                            <td><small class="text-muted">{{ $siswa->nisn }}</small></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    @foreach(['Hadir' => 'success', 'Sakit' => 'info', 'Izin' => 'warning', 'Alfa' => 'danger'] as $status => $warna)
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input status-radio" type="radio"
                                               name="kehadiran[{{ $siswa->id_siswa }}]"
                                               id="s{{ $siswa->id_siswa }}_{{ $status }}"
                                               value="{{ $status }}"
                                               {{ $status === ($izinHariIni[$siswa->id_siswa]['status'] ?? 'Hadir') ? 'checked' : '' }}
                                               required>
                                        <label class="form-check-label badge bg-{{ $warna }} {{ $warna === 'info' || $warna === 'warning' ? 'text-dark' : '' }}"
                                               for="s{{ $siswa->id_siswa }}_{{ $status }}">
                                            {{ $status }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <input type="text" name="keterangan[{{ $siswa->id_siswa }}]"
                                       class="form-control form-control-sm"
                                       value="{{ $izinHariIni[$siswa->id_siswa]['keterangan'] ?? '' }}"
                                       placeholder="Keterangan (opsional)">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-3">
                <a href="{{ route('jurnal.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" {{ $sudahIsi ? 'disabled' : '' }}>
                    Simpan Presensi ({{ $siswaList->count() }} siswa)
                </button>
            </div>
        </form>
        @endif

    </div>
</div>

@endsection

@push('scripts')
<script>
function setAll(status) {
    document.querySelectorAll('input[type="radio"][value="' + status + '"]').forEach(function(el) {
        el.checked = true;
    });
}
</script>
@endpush

