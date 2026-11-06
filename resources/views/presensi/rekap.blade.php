@extends('layouts.app')

@section('judul', 'Rekap presensi siswa')
@section('sub', 'Rekap kehadiran per siswa dalam satu kelas pada rentang tanggal tertentu.')

@section('isi')
<style>
    @media print {
        .samping, .no-print, .alert { display: none !important; }
        .kepala-halaman { border: 0; padding: 0 0 8px; }
        .konten { padding: 0; max-width: none; }
        .card { border: 0 !important; }
    }
</style>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('rekap.presensi') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Kelas</label>
                <select name="id_kelas" class="form-select">
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id_kelas }}" {{ (string) $idKelas === (string) $k->id_kelas ? 'selected' : '' }}>
                            {{ $k->tingkat }} {{ $k->id_jurusan }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Dari tanggal</label>
                <input type="date" name="dari" class="form-control" value="{{ $dari }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai tanggal</label>
                <input type="date" name="sampai" class="form-control" value="{{ $sampai }}" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

@if($daftarKelas->isEmpty())
    <div class="alert alert-warning">Akun Anda belum ditetapkan sebagai wali kelas dari kelas mana pun, jadi belum ada rekap yang bisa ditampilkan.</div>
@else
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-0">Kelas {{ optional($kelasDipilih)->tingkat }} {{ optional($kelasDipilih)->id_jurusan }}</h5>
            <div class="catatan">{{ $dari }} s/d {{ $sampai }}</div>
        </div>
        <button type="button" class="btn btn-outline-primary no-print" onclick="window.print()">Cetak</button>
    </div>

    <div class="card">
        <div class="card-body tabel-luas">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Siswa</th>
                        <th class="text-center">Hadir</th>
                        <th class="text-center">Sakit</th>
                        <th class="text-center">Izin</th>
                        <th class="text-center">Alfa</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">% Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekap as $i => $r)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ trim($r->nama_siswa) }}</strong><br>
                                <small class="text-muted">NISN: {{ $r->nisn }}</small>
                            </td>
                            <td class="text-center">{{ $r->hadir }}</td>
                            <td class="text-center">{{ $r->sakit }}</td>
                            <td class="text-center">{{ $r->izin }}</td>
                            <td class="text-center {{ $r->alfa > 0 ? 'text-danger fw-bold' : '' }}">{{ $r->alfa }}</td>
                            <td class="text-center">{{ $r->total }}</td>
                            <td class="text-center">
                                @if($r->persen === null)
                                    <span class="text-muted">-</span>
                                @else
                                    <span class="badge {{ $r->persen >= 90 ? 'bg-success' : ($r->persen >= 75 ? 'bg-warning text-dark' : 'bg-danger') }}">{{ $r->persen }}%</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada siswa aktif di kelas ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="catatan mt-2">Angka dihitung per pertemuan (satu kali presensi pelajaran = satu hitungan).</div>
@endif
@endsection
