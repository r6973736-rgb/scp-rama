@extends('layouts.app')

@section('judul', 'Jadwal pelajaran')
@section('sub', 'Atur jadwal mengajar dan uji bentrok otomatis sebelum disimpan.')

@section('isi')
<div class="row g-4">
            <!-- Kolom Kiri: Form Input Jadwal -->
            <div class="col-xl-5 col-lg-5">
                <div class="card">
                    <div class="card-header">Form Input Jadwal & Uji Anti-Tabrakan</div>
                    <div class="card-body">
                        <form action="{{ route('jadwal.store') }}" method="POST">
                            @csrf
                            
                            <!-- Dropdown Tahun Ajaran -->
                            <div class="mb-3">
                                <label class="form-label">ID Tahun Ajaran</label>
                                <select name="id_tahun_ajaran" class="form-select" required>
                                    <option value="">-- Pilih Tahun Ajaran / Semester --</option>
                                    @foreach($tahunAjaran as $ta)
                                        <option value="{{ $ta->id_tahun_ajaran }}" {{ old('id_tahun_ajaran') == $ta->id_tahun_ajaran ? 'selected' : '' }}>
                                            {{ $ta->nama_semester ?? $ta->id_tahun_ajaran }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Dropdown Kelas -->
                            <div class="mb-3">
                                <label class="form-label">ID Kelas</label>
                                <select name="id_kelas" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelas as $k)
                                        <option value="{{ $k->id_kelas }}" {{ old('id_kelas') == $k->id_kelas ? 'selected' : '' }}>
                                            {{ $k->tingkat ?? '' }} {{ $k->id_jurusan ?? $k->nama_kelas ?? $k->id_kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Dropdown Mata Pelajaran -->
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran</label>
                                <select name="id_mapel" class="form-select" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    @foreach($mapels as $m)
                                        <option value="{{ $m->id_mapel }}" {{ old('id_mapel') == $m->id_mapel ? 'selected' : '' }}>
                                            {{ $m->nama_mapel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Kolom Guru Pengampu (Otomatis Terkunci Jika Login Sebagai Guru) -->
                            <div class="mb-3">
                                <label class="form-label">Guru Pengampu</label>
                                @if(isset($guruLogin) && $guruLogin)
                                    <!-- Jika yang login adalah Guru, kunci kolom dan isi otomatis namanya -->
                                    <input type="hidden" name="id_guru" value="{{ $guruLogin->id_guru }}">
                                    <input type="text" class="form-control bg-light text-dark fw-bold" value="{{ $guruLogin->nama_guru }}" readonly>
                                    <small class="text-muted">Guru pengampu terisi otomatis sesuai akun Anda.</small>
                                @else
                                    <!-- Jika yang login Admin / Waka, tampilkan dropdown pilihan guru bebas -->
                                    <select name="id_guru" class="form-select" required>
                                        <option value="">-- Pilih Guru --</option>
                                        @foreach($gurus as $g)
                                            <option value="{{ $g->id_guru }}" {{ old('id_guru') == $g->id_guru ? 'selected' : '' }}>
                                                {{ $g->nama_guru }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <!-- Dropdown Ruangan -->
                            <div class="mb-3">
                                <label class="form-label">Ruangan Default</label>
                                <select name="id_ruangan_default" class="form-select" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach($ruangans as $r)
                                        <option value="{{ $r->id_ruangan }}" {{ old('id_ruangan_default') == $r->id_ruangan ? 'selected' : '' }}>
                                            {{ $r->nama_ruangan ?? 'Ruang '.$r->id_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Hari -->
                            <div class="mb-3">
                                <label class="form-label">Hari</label>
                                <select name="hari" id="hari" class="form-select" required>
                                    <option value="">-- Pilih Hari --</option>
                                    <option value="Senin" {{ old('hari') == 'Senin' ? 'selected' : '' }}>Senin</option>
                                    <option value="Selasa" {{ old('hari') == 'Selasa' ? 'selected' : '' }}>Selasa</option>
                                    <option value="Rabu" {{ old('hari') == 'Rabu' ? 'selected' : '' }}>Rabu</option>
                                    <option value="Kamis" {{ old('hari') == 'Kamis' ? 'selected' : '' }}>Kamis</option>
                                    <option value="Jumat" {{ old('hari') == 'Jumat' ? 'selected' : '' }}>Jumat</option>
                                </select>
                            </div>

                            <!-- Sesi / jam pelajaran (mengikuti jadwal ruang sekolah) -->
                            @if(empty($sesiPerHari))
                                <div class="alert alert-warning py-2">Data sesi belum ada. Jalankan <code>database/sql/sesi_jam.sql</code> di phpMyAdmin.</div>
                            @endif
                            <div class="row mb-1">
                                <div class="col-6">
                                    <label class="form-label">Mulai Jam ke-</label>
                                    <select name="sesi_mulai" id="sesi_mulai" class="form-select" required>
                                        <option value="">-- Pilih --</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Sampai Jam ke-</label>
                                    <select name="sesi_selesai" id="sesi_selesai" class="form-select" required>
                                        <option value="">-- Pilih --</option>
                                    </select>
                                </div>
                            </div>
                            <small class="text-muted d-block mb-3" id="info-sesi">Pilih hari terlebih dahulu.</small>

                            <button type="submit" class="btn btn-primary w-100">Simpan & Uji Jadwal</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Daftar Jadwal Aktif -->
            <div class="col-xl-7 col-lg-7">
                <div class="card">
                    <div class="card-header">Daftar Jadwal Pelajaran Aktif</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tahun Ajaran</th>
                                        <th>Putaran</th>
                                        <th>Hari / Jam</th>
                                        <th>Kelas & Mapel</th>
                                        <th>Guru & Ruangan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($jadwals as $index => $j)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><span class="badge bg-secondary">{{ $j->tahunAjaran->nama_semester ?? $j->id_tahun_ajaran }}</span></td>
                                            <td>
                                                <span class="badge bg-info text-dark">{{ $j->cycle_type }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $j->hari }}</strong><br>
                                                @if(!empty($sesiLabel[$j->id_jadwal]))
                                                    <span class="badge bg-light text-dark border">{{ $sesiLabel[$j->id_jadwal] }}</span><br>
                                                @endif
                                                <small>{{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}</small>
                                            </td>
                                            <td>
                                                {{ $j->kelas->nama_kelas ?? $j->id_kelas }}<br>
                                                <small class="text-muted">{{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</small>
                                            </td>
                                            <td>
                                                Guru: {{ $j->guru->nama_guru ?? $j->id_guru }}<br>
                                                <small class="text-muted">Ruang: {{ $j->ruangan->nama_ruangan ?? $j->id_ruangan_default }}</small>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <a href="{{ route('jadwal.edit', $j->id_jadwal) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                                <form action="{{ route('jadwal.destroy', $j->id_jadwal) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Belum ada data jadwal milik Anda.</td>
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
@include('jadwal._sesi_script', ['sesiPerHari' => $sesiPerHari, 'mulaiAwal' => old('sesi_mulai'), 'selesaiAwal' => old('sesi_selesai')])
@endpush
