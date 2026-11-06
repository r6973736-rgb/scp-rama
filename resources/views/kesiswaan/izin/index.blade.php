@extends('layouts.app')

@section('judul', 'Verifikasi izin siswa')
@section('sub', 'Pengajuan izin dari siswa menunggu keputusan wali kelas atau guru BK.')

@section('isi')
@php
    $jumlahSetuju = $pendingLeaves->where('status_approval', 'Approved')->count();
    $jumlahTolak  = $pendingLeaves->where('status_approval', 'Rejected')->count();
    $jumlahTunggu = $pendingLeaves->count() - $jumlahSetuju - $jumlahTolak;
@endphp

<div class="ringkas">
    <div class="butir"><div class="angka">{{ $jumlahTunggu }}</div><div class="label">Menunggu keputusan</div></div>
    <div class="butir"><div class="angka">{{ $jumlahSetuju }}</div><div class="label">Disetujui</div></div>
    <div class="butir"><div class="angka">{{ $jumlahTolak }}</div><div class="label">Ditolak</div></div>
</div>

<!-- Laporan izin siswa hari ini (masuk otomatis) -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Laporan izin hari ini &middot; {{ $tanggalHariIni }}</span>
        <a href="{{ route('laporan.izin.harian') }}" class="btn btn-sm btn-outline-primary">Buka laporan lengkap</a>
    </div>
    <div class="card-body">
        @if($laporanHariIni->isEmpty())
            <div class="text-muted">Tidak ada siswa yang izin atau sakit hari ini.</div>
        @else
            <div class="mb-3">
                <span class="badge bg-dark me-1">{{ $laporanHariIni->count() }} siswa tidak masuk</span>
                <span class="badge bg-info text-dark me-1">Sakit {{ $laporanHariIni->where('jenis', 'Sakit')->count() }}</span>
                <span class="badge bg-warning text-dark">Izin {{ $laporanHariIni->where('jenis', 'Izin')->count() }}</span>
            </div>
            <div class="tabel-luas">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th>Siswa</th><th>Kelas</th><th>Jenis</th><th>Alasan</th></tr>
                    </thead>
                    <tbody>
                        @foreach($laporanHariIni->take(8) as $r)
                            <tr>
                                <td>{{ trim($r->nama_siswa) }}</td>
                                <td>{{ $r->kelas_label }}</td>
                                <td><span class="badge {{ $r->jenis === 'Sakit' ? 'bg-info text-dark' : 'bg-warning text-dark' }}">{{ $r->jenis }}</span></td>
                                <td>{{ \Illuminate\Support\Str::limit($r->reason, 60) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($laporanHariIni->count() > 8)
                <div class="catatan mt-2">dan {{ $laporanHariIni->count() - 8 }} siswa lainnya. Lihat selengkapnya di laporan lengkap.</div>
            @endif
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">Daftar pengajuan izin siswa</div>
    <div class="card-body tabel-luas">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Siswa</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Dokumen</th>
                    <th>Status</th>
                    <th class="text-center">Keputusan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingLeaves as $item)
                <tr>
                    <td>
                        <strong>{{ $item->student->nama_siswa ?? 'Siswa tidak ditemukan' }}</strong><br>
                        <small class="text-muted">NISN: {{ $item->student->nisn ?? '-' }}</small>
                    </td>
                    <td>{{ $item->start_date }} s/d {{ $item->end_date }}</td>
                    <td>{{ $item->reason }}</td>
                    <td>
                        @if($item->document_path)
                            <a href="{{ asset($item->document_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($item->status_approval == 'Approved')
                            <span class="badge bg-success">Disetujui</span>
                        @elseif($item->status_approval == 'Rejected')
                            <span class="badge bg-danger">Ditolak</span>
                            @if($item->rejection_reason)
                                <div class="small text-muted mt-1" style="max-width:220px;">Alasan: {{ $item->rejection_reason }}</div>
                            @endif
                        @else
                            <span class="badge bg-warning text-dark">Menunggu</span>
                        @endif
                    </td>
                    <td class="text-center text-nowrap">
                        <form action="{{ route('kesiswaan.izin.update', $item->id) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status_approval" value="Approved">
                            <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                        </form>
                        <button type="button" class="btn btn-sm btn-danger"
                                data-bs-toggle="modal" data-bs-target="#modalTolak"
                                data-action="{{ route('kesiswaan.izin.update', $item->id) }}"
                                data-nama="{{ $item->student->nama_siswa ?? '-' }}"
                                data-alasan="{{ $item->rejection_reason }}">Tolak</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengajuan izin yang masuk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

<!-- Modal: alasan penolakan -->
<div class="modal fade" id="modalTolak" tabindex="-1" aria-labelledby="judulModalTolak" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formTolak" class="modal-content">
            @csrf
            <input type="hidden" name="status_approval" value="Rejected">
            <div class="modal-header">
                <h5 class="modal-title" id="judulModalTolak">Tolak pengajuan izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Siswa: <strong id="namaTolak"></strong></p>
                <label for="alasanTolak" class="form-label">Alasan penolakan <span class="text-danger">*</span></label>
                <textarea name="rejection_reason" id="alasanTolak" class="form-control" rows="4"
                          minlength="5" maxlength="500" required
                          placeholder="Contoh: Surat dokter tidak terbaca, mohon unggah ulang."></textarea>
                <div class="form-text">Alasan ini akan terlihat oleh siswa.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak pengajuan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('modalTolak').addEventListener('show.bs.modal', function (e) {
        const tombol = e.relatedTarget;
        document.getElementById('formTolak').action = tombol.dataset.action;
        document.getElementById('namaTolak').textContent = tombol.dataset.nama;
        document.getElementById('alasanTolak').value = tombol.dataset.alasan || '';
    });
</script>
@endpush
