@extends('layouts.app')

@section('judul', 'Edit jurnal mengajar')
@section('sub', 'Ubah topik materi atau perbarui foto bukti kegiatan belajar mengajar.')

@section('isi')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Form Edit Jurnal Mengajar</span>
                <a href="{{ route('jurnal.index') }}" class="btn btn-sm btn-outline-secondary">← Kembali</a>
            </div>
            <div class="card-body">
                <form action="{{ route('jurnal.update', $jurnal->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Pilih Jadwal Mengajar</label>
                        <select name="schedule_id" class="form-select" required>
                            <option value="">-- Pilih Jadwal Lengkap --</option>
                            @foreach($jadwals as $j)
                                <option value="{{ $j->id_jadwal }}" {{ old('schedule_id', $jurnal->schedule_id) == $j->id_jadwal ? 'selected' : '' }}>
                                    [{{ $j->id_tahun_ajaran }} | Putaran {{ $j->cycle_type }}] {{ $j->hari }} ({{ $j->jam_mulai }} - {{ $j->jam_selesai }}) | Kelas: {{ optional($j->kelas)->nama_kelas ?? $j->id_kelas }} | Mapel: {{ $j->id_mapel }} | Ruang: {{ $j->id_ruangan_default }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi Topik / Materi</label>
                        <textarea name="topic_description" class="form-control" rows="4" required placeholder="Tuliskan materi pembelajaran...">{{ old('topic_description', $jurnal->topic_description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Bukti KBM</label>
                        @if($jurnal->photo_path)
                            <div class="mb-2">
                                <img src="{{ asset($jurnal->photo_path) }}" alt="Bukti KBM" class="img-thumbnail" style="max-height: 180px;">
                                <div class="small text-muted mt-1">Foto saat ini terpasang. Unggah foto baru di bawah jika ingin menggantinya.</div>
                            </div>
                        @endif
                        @include('jurnal._kamera', ['wajib' => false])
                        <small class="text-muted">Tidak perlu mengambil foto baru jika foto lama tidak ingin diganti.</small>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('jurnal.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

