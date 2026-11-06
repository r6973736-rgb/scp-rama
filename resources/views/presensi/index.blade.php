@extends('layouts.app')

@section('judul', 'Presensi siswa')
@section('sub', 'Catat kehadiran siswa pada sesi KBM Anda hari ini.')

@section('isi')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

{{-- ===== KARTU PILIH JADWAL ===== --}}
<div class="card mb-4">
    <div class="card-header">Pilih jadwal untuk mengisi presensi hari ini</div>
    <div class="card-body">
        @if($jadwals->isEmpty())
            <p class="text-muted mb-0">Anda belum memiliki jadwal mengajar yang terdaftar.</p>
        @else
            <div class="row g-3">
                @foreach($jadwals as $j)
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('presensi.isi', ['id_jadwal' => $j->id_jadwal]) }}"
                       class="card h-100 text-decoration-none border-primary-subtle {{ $j->hari == now()->locale('id')->isoFormat('dddd') ? 'border-primary' : '' }}"
                       style="transition:.15s">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary">{{ $j->hari }}</span>
                                <small class="text-muted">{{ $j->jam_mulai }} – {{ $j->jam_selesai }}</small>
                            </div>
                            <div class="fw-semibold">{{ optional($j->kelas)->nama_kelas ?? $j->id_kelas }}</div>
                            <div class="text-muted small">{{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</div>
                            <div class="text-muted small">Ruang: {{ $j->id_ruangan_default }}</div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- ===== RIWAYAT PRESENSI ===== --}}
<div class="card">
    <div class="card-header">Riwayat presensi yang sudah diisi</div>
    <div class="card-body tabel-luas">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kelas</th>
                    <th>Mata Pelajaran</th>
                    <th class="text-center">Hadir</th>
                    <th class="text-center">Sakit</th>
                    <th class="text-center">Izin</th>
                    <th class="text-center">Alfa</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $r)
                @php
                    $counts = $r->detail->groupBy('status_kehadiran');
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d M Y') }}</td>
                    <td>{{ optional(optional($r->jadwal)->kelas)->nama_kelas ?? optional($r->jadwal)->id_kelas ?? '-' }}</td>
                    <td>{{ $r->jadwal->mataPelajaran->nama_mapel ?? ($r->jadwal->id_mapel ?? '-') }}</td>
                    <td class="text-center"><span class="badge bg-success">{{ $counts->get('Hadir', collect())->count() }}</span></td>
                    <td class="text-center"><span class="badge bg-info text-dark">{{ $counts->get('Sakit', collect())->count() }}</span></td>
                    <td class="text-center"><span class="badge bg-warning text-dark">{{ $counts->get('Izin', collect())->count() }}</span></td>
                    <td class="text-center"><span class="badge bg-danger">{{ $counts->get('Alfa', collect())->count() }}</span></td>
                    <td class="text-center">
                        <a href="{{ route('presensi.detail', $r->id_presensi) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada presensi yang diisi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

