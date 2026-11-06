@extends('layouts.app')

@section('judul', 'Pengajuan izin dan sakit')
@section('sub', 'Kirim surat izin beserta bukti, lalu pantau keputusan wali kelas di sini.')

@section('isi')
@php
    $jumlahSetuju = $riwayatIzin->where('status_approval', 'Approved')->count();
    $jumlahTolak  = $riwayatIzin->where('status_approval', 'Rejected')->count();
    $jumlahTunggu = $riwayatIzin->count() - $jumlahSetuju - $jumlahTolak;
@endphp

<div class="ringkas">
    <div class="butir"><div class="angka">{{ $riwayatIzin->count() }}</div><div class="label">Total pengajuan</div></div>
    <div class="butir"><div class="angka">{{ $jumlahTunggu }}</div><div class="label">Menunggu</div></div>
    <div class="butir"><div class="angka">{{ $jumlahSetuju }}</div><div class="label">Disetujui</div></div>
    <div class="butir"><div class="angka">{{ $jumlahTolak }}</div><div class="label">Ditolak</div></div>
</div>

<div class="row g-4">
    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-header">Form surat izin</div>
            <div class="card-body">
                <form action="{{ route('siswa.izin.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Tanggal mulai</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tanggal selesai</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Contoh: sakit demam, izin acara keluarga"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Bukti (surat dokter atau foto)</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,image/*" required>
                        <div class="catatan mt-1">Format PDF, JPG, atau PNG. Maksimal 2 MB.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kirim pengajuan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7">
        <div class="card">
            <div class="card-header">Riwayat pengajuan saya</div>
            <div class="card-body tabel-luas">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Alasan</th>
                            <th>Dokumen</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayatIzin as $izin)
                        <tr>
                            <td>{{ $izin->start_date }} s/d {{ $izin->end_date }}</td>
                            <td>{{ $izin->reason }}</td>
                            <td>
                                @if($izin->document_path)
                                    <a href="{{ asset($izin->document_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($izin->status_approval == 'Approved')
                                    <span class="badge bg-success">Disetujui</span>
                                @elseif($izin->status_approval == 'Rejected')
                                    <span class="badge bg-danger">Ditolak</span>
                                    @if($izin->rejection_reason)
                                        <div class="small text-danger mt-1" style="max-width:240px;">Alasan: {{ $izin->rejection_reason }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-warning text-dark">Menunggu</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pengajuan. Isi formulir di samping untuk mengirim yang pertama.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
