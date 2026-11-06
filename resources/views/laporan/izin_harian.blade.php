@extends('layouts.app')

@section('judul', 'Laporan izin siswa harian')
@section('sub', 'Daftar siswa yang tidak masuk karena izin atau sakit pada tanggal tertentu.')

@section('isi')
<style>
    @media print {
        .samping, .no-print, .alert { display: none !important; }
        .kepala-halaman { border: 0; padding: 0 0 8px; }
        .konten { padding: 0; max-width: none; }
        .card { border: 0 !important; }
        a[href]:after { content: none !important; }
    }
</style>

<!-- Filter -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('laporan.izin.harian') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kelas</label>
                <select name="id_kelas" class="form-select">
                    <option value="">Semua kelas</option>
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id_kelas }}" {{ (string) $idKelas === (string) $k->id_kelas ? 'selected' : '' }}>
                            {{ $k->tingkat }} {{ $k->id_jurusan }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status pengajuan</label>
                <select name="status" class="form-select">
                    @foreach($daftarStatus as $nilai => $teks)
                        <option value="{{ $nilai }}" {{ $status === $nilai ? 'selected' : '' }}>{{ $teks }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Tampilkan</button>
                <a href="{{ route('laporan.izin.harian') }}" class="btn btn-outline-secondary">Hari ini</a>
            </div>
        </form>
    </div>
</div>

@if($waliTanpaKelas)
    <div class="alert alert-warning no-print">Akun Anda belum ditetapkan sebagai wali kelas dari kelas mana pun, jadi belum ada data yang bisa ditampilkan.</div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-0">{{ $tanggalTeks }}</h5>
        <div class="catatan">
            {{ $daftarStatus[$status] }}
            @if($idKelas)
                · Kelas {{ optional($daftarKelas->firstWhere('id_kelas', $idKelas))->tingkat }} {{ optional($daftarKelas->firstWhere('id_kelas', $idKelas))->id_jurusan }}
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 no-print">
        <button type="button" class="btn btn-outline-primary" onclick="window.print()">Cetak</button>
        <a href="{{ route('laporan.izin.harian', array_filter(['tanggal' => $tanggal, 'status' => $status, 'id_kelas' => $idKelas, 'unduh' => 'csv'])) }}"
           class="btn btn-outline-success">Unduh CSV</a>
    </div>
</div>

<div class="ringkas">
    <div class="butir"><div class="angka">{{ $data->count() }}</div><div class="label">Siswa tidak masuk</div></div>
    <div class="butir"><div class="angka">{{ $jumlahSakit }}</div><div class="label">Sakit</div></div>
    <div class="butir"><div class="angka">{{ $jumlahIzin }}</div><div class="label">Izin</div></div>
    <div class="butir"><div class="angka">{{ $perKelas->count() }}</div><div class="label">Kelas terdampak</div></div>
</div>

@if($perKelas->count() > 1)
    <div class="mb-3">
        @foreach($perKelas as $kelas => $jumlah)
            <span class="badge bg-light text-dark border me-1 mb-1">{{ $kelas }}: {{ $jumlah }}</span>
        @endforeach
    </div>
@endif

<div class="card">
    <div class="card-body tabel-luas">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th>Alasan</th>
                    <th>Periode izin</th>
                    <th>Status</th>
                    <th class="no-print">Dokumen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ trim($r->nama_siswa) }}</strong><br>
                            <small class="text-muted">NISN: {{ $r->nisn }}</small>
                        </td>
                        <td>{{ $r->kelas_label }}</td>
                        <td>
                            <span class="badge {{ $r->jenis === 'Sakit' ? 'bg-info text-dark' : 'bg-warning text-dark' }}">{{ $r->jenis }}</span>
                        </td>
                        <td>{{ $r->reason }}</td>
                        <td>
                            @if($r->start_date === $r->end_date)
                                {{ $r->start_date }}
                            @else
                                {{ $r->start_date }} s/d {{ $r->end_date }}
                            @endif
                        </td>
                        <td>
                            @if($r->status_approval === 'Approved')
                                <span class="badge bg-success">Disetujui</span>
                            @elseif($r->status_approval === 'Rejected')
                                <span class="badge bg-danger">Ditolak</span>
                                @if($r->rejection_reason)
                                    <div class="small text-muted mt-1" style="max-width:200px;">Alasan: {{ $r->rejection_reason }}</div>
                                @endif
                            @else
                                <span class="badge bg-warning text-dark">Menunggu</span>
                            @endif
                        </td>
                        <td class="no-print">
                            @if($r->document_path)
                                <a href="{{ asset($r->document_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada siswa yang izin atau sakit pada tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
