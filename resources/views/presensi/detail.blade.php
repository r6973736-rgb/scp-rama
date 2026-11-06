@extends('layouts.app')

@section('judul', 'Detail presensi')
@section('sub', 'Kelas ' . (optional(optional($header->jadwal)->kelas)->nama_kelas ?? optional($header->jadwal)->id_kelas ?? '-') . ' — ' . \Carbon\Carbon::parse($header->tanggal)->translatedFormat('d M Y'))

@section('isi')

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Informasi sesi KBM</span>
        <a href="{{ route('jurnal.index') }}" class="btn btn-sm btn-outline-secondary">← Kembali</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">Tanggal</div>
                <div class="fw-semibold">{{ \Carbon\Carbon::parse($header->tanggal)->translatedFormat('l, d M Y') }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">Kelas</div>
                <div class="fw-semibold">{{ optional(optional($header->jadwal)->kelas)->nama_kelas ?? optional($header->jadwal)->id_kelas ?? '-' }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">Mata Pelajaran</div>
                <div class="fw-semibold">{{ $header->jadwal->mataPelajaran->nama_mapel ?? ($header->jadwal->id_mapel ?? '-') }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">Ruangan digunakan</div>
                <div class="fw-semibold">{{ $header->id_ruangan_digunakan }}</div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="text-muted small">Guru pengajar</div>
                <div class="fw-semibold">{{ $header->guruPengajar->nama_guru ?? '-' }}</div>
            </div>
            @if($header->catatan_kbm)
            <div class="col-12">
                <div class="text-muted small">Catatan KBM</div>
                <div>{{ $header->catatan_kbm }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Ringkasan --}}
@php
    $counts = $header->detail->groupBy('status_kehadiran');
    $total  = $header->detail->count();
@endphp
<div class="ringkas mb-4">
    <div class="butir"><div class="angka">{{ $total }}</div><div class="label">Total siswa</div></div>
    <div class="butir"><div class="angka">{{ $counts->get('Hadir', collect())->count() }}</div><div class="label">Hadir</div></div>
    <div class="butir"><div class="angka">{{ $counts->get('Sakit', collect())->count() }}</div><div class="label">Sakit</div></div>
    <div class="butir"><div class="angka">{{ $counts->get('Izin', collect())->count() }}</div><div class="label">Izin</div></div>
    <div class="butir"><div class="angka">{{ $counts->get('Alfa', collect())->count() }}</div><div class="label">Alfa</div></div>
</div>

<div class="card">
    <div class="card-header">Daftar kehadiran siswa</div>
    <div class="card-body tabel-luas">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Siswa</th>
                    <th>NISN</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($header->detail->sortBy('siswa.nama_siswa') as $i => $d)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $d->siswa->nama_siswa ?? '(Data siswa tidak ditemukan)' }}</td>
                    <td><small class="text-muted">{{ $d->siswa->nisn ?? '-' }}</small></td>
                    <td>
                        @php
                            $warna = match($d->status_kehadiran) {
                                'Hadir' => 'success',
                                'Sakit' => 'info',
                                'Izin'  => 'warning',
                                default => 'danger',
                            };
                        @endphp
                        <span class="badge bg-{{ $warna }} {{ in_array($d->status_kehadiran, ['Sakit','Izin']) ? 'text-dark' : '' }}">
                            {{ $d->status_kehadiran }}
                        </span>
                    </td>
                    <td>{{ $d->keterangan ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

