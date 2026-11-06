@extends('layouts.app')

@section('judul', 'Edit jadwal pelajaran')
@section('sub', 'Ubah data jadwal mengajar dan periksa potensi bentrok waktu, kelas, atau ruangan.')

@section('isi')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Form Edit Jadwal Pelajaran (Putaran: {{ $jadwal->cycle_type }})</span>
                <a href="{{ route('jadwal.index') }}" class="btn btn-sm btn-outline-secondary">← Kembali</a>
            </div>
            <div class="card-body">
                <form action="{{ route('jadwal.update', $jadwal->id_jadwal) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <!-- Tahun Ajaran -->
                        <div class="col-md-6">
                            <label class="form-label">Tahun Ajaran / Semester</label>
                            <select name="id_tahun_ajaran" class="form-select" required>
                                <option value="">-- Pilih Tahun Ajaran --</option>
                                @foreach($tahunAjaran as $ta)
                                    <option value="{{ $ta->id_tahun_ajaran }}" {{ old('id_tahun_ajaran', $jadwal->id_tahun_ajaran) == $ta->id_tahun_ajaran ? 'selected' : '' }}>
                                        {{ $ta->nama_semester ?? $ta->id_tahun_ajaran }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Kelas -->
                        <div class="col-md-6">
                            <label class="form-label">Kelas</label>
                            <select name="id_kelas" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($kelas as $k)
                                    <option value="{{ $k->id_kelas }}" {{ old('id_kelas', $jadwal->id_kelas) == $k->id_kelas ? 'selected' : '' }}>
                                        {{ $k->tingkat ?? '' }} {{ $k->id_jurusan ?? $k->nama_kelas ?? $k->id_kelas }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Mata Pelajaran -->
                        <div class="col-md-6">
                            <label class="form-label">Mata Pelajaran</label>
                            <select name="id_mapel" class="form-select" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                @foreach($mapels as $m)
                                    <option value="{{ $m->id_mapel }}" {{ old('id_mapel', $jadwal->id_mapel) == $m->id_mapel ? 'selected' : '' }}>
                                        {{ $m->nama_mapel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Ruangan -->
                        <div class="col-md-6">
                            <label class="form-label">Ruangan Default</label>
                            <select name="id_ruangan_default" class="form-select" required>
                                <option value="">-- Pilih Ruangan --</option>
                                @foreach($ruangans as $r)
                                    <option value="{{ $r->id_ruangan }}" {{ old('id_ruangan_default', $jadwal->id_ruangan_default) == $r->id_ruangan ? 'selected' : '' }}>
                                        {{ $r->nama_ruangan ?? 'Ruang '.$r->id_ruangan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Guru Pengampu -->
                        <div class="col-md-6">
                            <label class="form-label">Guru Pengampu</label>
                            @if(isset($guruLogin) && $guruLogin && session('id_role') != 4)
                                <input type="hidden" name="id_guru" value="{{ $guruLogin->id_guru }}">
                                <input type="text" class="form-control bg-light text-dark fw-bold" value="{{ $guruLogin->nama_guru }}" readonly>
                                <small class="text-muted">Terkunci sesuai akun guru Anda.</small>
                            @else
                                <select name="id_guru" class="form-select" required>
                                    <option value="">-- Pilih Guru --</option>
                                    @foreach($gurus as $g)
                                        <option value="{{ $g->id_guru }}" {{ old('id_guru', $jadwal->id_guru) == $g->id_guru ? 'selected' : '' }}>
                                            {{ $g->nama_guru }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <!-- Hari -->
                        <div class="col-md-6">
                            <label class="form-label">Hari</label>
                            <select name="hari" id="hari" class="form-select" required>
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $h)
                                    <option value="{{ $h }}" {{ old('hari', $jadwal->hari) == $h ? 'selected' : '' }}>{{ $h }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Sesi / jam pelajaran -->
                        @if(empty($sesiPerHari))
                            <div class="col-12"><div class="alert alert-warning py-2 mb-0">Data sesi belum ada. Jalankan <code>database/sql/sesi_jam.sql</code> di phpMyAdmin.</div></div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Mulai Jam ke-</label>
                            <select name="sesi_mulai" id="sesi_mulai" class="form-select" required>
                                <option value="">-- Pilih --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sampai Jam ke-</label>
                            <select name="sesi_selesai" id="sesi_selesai" class="form-select" required>
                                <option value="">-- Pilih --</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <small class="text-muted" id="info-sesi">Pilih hari terlebih dahulu.</small>
                            @if(!$sesiMulai)
                                <small class="text-danger d-block">Jam lama ({{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}) tidak cocok dengan sesi mana pun. Pilih ulang sesinya.</small>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('jadwal.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('jadwal._sesi_script', ['sesiPerHari' => $sesiPerHari, 'mulaiAwal' => old('sesi_mulai', $sesiMulai), 'selesaiAwal' => old('sesi_selesai', $sesiSelesai)])
@endpush
