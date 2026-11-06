@extends('layouts.app')

@section('judul', 'Rekap jurnal mengajar')
@section('sub', 'Seluruh jurnal yang diisi guru.')

@section('isi')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th>Tanggal</th>
                        <th>Nama Guru</th>
                        <th>Kelas & Mapel</th>
                        <th>Topik / Materi</th>
                        <th class="text-center">Foto KBM</th>
                        <th class="text-center">Presensi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jurnalList as $index => $jurnal)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $jurnal->date }}</td>
                            <td>{{ $jurnal->jadwal->guru->nama_guru ?? 'Guru Tidak Ditemukan' }}</td>
                            <td>
                                <strong>
                                    @if($jurnal->jadwal && $jurnal->jadwal->kelas)
                                        {{ $jurnal->jadwal->kelas->tingkat }} {{ $jurnal->jadwal->kelas->id_jurusan }}
                                    @else
                                        {{ $jurnal->jadwal->id_kelas ?? '-' }}
                                    @endif
                                </strong><br>
                                <small class="text-muted">{{ $jurnal->jadwal->mataPelajaran->nama_mapel ?? '-' }}</small>
                            </td>
                            <td>{{ $jurnal->topic_description }}</td>
                            <td class="text-center">
                                @if($jurnal->photo_path)
                                    <a href="{{ asset($jurnal->photo_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Foto</a>
                                @else
                                    <span class="badge bg-secondary">Tidak ada</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $p = $presensiMap[$jurnal->schedule_id . '|' . substr($jurnal->date, 0, 10)] ?? null;
                                    $hitung = $p ? $p->detail->groupBy('status_kehadiran') : collect();
                                @endphp
                                @if($p)
                                    <span class="badge bg-success">H {{ $hitung->get('Hadir', collect())->count() }}</span>
                                    <span class="badge bg-info text-dark">S {{ $hitung->get('Sakit', collect())->count() }}</span>
                                    <span class="badge bg-warning text-dark">I {{ $hitung->get('Izin', collect())->count() }}</span>
                                    <span class="badge bg-danger">A {{ $hitung->get('Alfa', collect())->count() }}</span><br>
                                    <a href="{{ route('presensi.detail', $p->id_presensi) }}" class="btn btn-sm btn-outline-dark mt-1">Detail</a>
                                @else
                                    <span class="badge bg-secondary">Belum diisi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada rekap jurnal dari guru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
