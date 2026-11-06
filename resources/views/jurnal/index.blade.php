@extends('layouts.app')
@section('judul', 'Jurnal mengajar')
@section('sub', 'Isi jurnal dan presensi siswa sekaligus: pilih jadwal, daftar siswa langsung muncul.')
@section('isi')
@if(!$kolomKelasAda)
    <div class="alert alert-warning">
        Kolom <code>siswa.id_kelas</code> belum ada di database sehingga presensi belum bisa dipakai.
        Jalankan <code>php artisan migrate</code>, lalu muat ulang halaman ini.
    </div>
@endif
<div class="row g-4">
    <!-- Form Jurnal + Presensi (satu kali simpan) -->
    <div class="col-12">
        <form action="{{ route('jurnal.store') }}" method="POST" enctype="multipart/form-data" id="formJurnal">
            @csrf
            <div class="card mb-4">
                <div class="card-header">Langkah 1 &mdash; Jurnal mengajar &amp; foto KBM</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">Pilih Jadwal Mengajar</label>
                            <select name="schedule_id" id="selJadwal" class="form-select" required>
                                <option value="">-- Pilih Jadwal Lengkap --</option>
                                @foreach($jadwals as $j)
                                    @php $sudah = in_array($j->id_jadwal, $sudahHariIni); @endphp
                                    <option value="{{ $j->id_jadwal }}" {{ $sudah ? 'disabled' : '' }} {{ old('schedule_id') == $j->id_jadwal ? 'selected' : '' }}>
                                        [{{ $j->id_tahun_ajaran }} | {{ $j->cycle_type }}] {{ $j->hari }} ({{ $j->jam_mulai }} - {{ $j->jam_selesai }}) | Kelas: {{ optional($j->kelas)->nama_kelas ?? $j->id_kelas }} | Mapel: {{ $j->id_mapel }} | Ruang: {{ $j->id_ruangan_default }}{{ $sudah ? ' (sudah diisi hari ini)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Foto Bukti KBM (langsung dari kamera)</label>
                            @include('jurnal._kamera', ['wajib' => true])
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi Topik / Materi</label>
                            <textarea name="topic_description" class="form-control" rows="3" required placeholder="Tuliskan materi pembelajaran...">{{ old('topic_description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4" id="kartuPresensi">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Langkah 2 &mdash; Presensi siswa <span id="infoKelas" class="text-muted fw-normal"></span></span>
                    <span id="ringkasan" class="small"></span>
                </div>
                <div class="card-body">
                    <div id="pesanAwal" class="text-muted">Pilih jadwal di atas, daftar siswa kelas tersebut akan muncul di sini.</div>
                    <div id="pesanKosong" class="alert alert-info mb-0 d-none">
                        Tidak ada siswa aktif yang terdaftar di kelas ini. Jurnal tetap bisa disimpan tanpa presensi;
                        atur kelas siswa lewat Kelola akun (Waka).
                    </div>
                    <div id="areaPresensi" class="d-none">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Catatan KBM</label>
                                <input type="text" name="catatan_kbm" class="form-control" maxlength="500" placeholder="Opsional..." value="{{ old('catatan_kbm') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="setAll('Hadir')">Semua Hadir</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="setAll('Alfa')">Semua Alfa</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:3rem">#</th>
                                        <th>Nama Siswa</th>
                                        <th>NISN</th>
                                        <th style="min-width:260px">Status Kehadiran</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="barisSiswa"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Simpan Jurnal &amp; Presensi</button>
            <p class="text-muted small mt-2 mb-0">Jurnal dan presensi disimpan bersamaan. Siswa dengan izin/sakit yang sudah disetujui otomatis terisi Sakit/Izin.</p>
        </form>
    </div>

    <!-- Riwayat Jurnal -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">Riwayat jurnal mengajar</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Detail Jadwal</th>
                                <th>Topik</th>
                                <th>Foto</th>
                                <th>Presensi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jurnalList as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->date }}</td>
                                    <td>
                                        @if($item->jadwal)
                                            <span class="badge bg-secondary mb-1">TA: {{ $item->jadwal->id_tahun_ajaran }}</span>
                                            <span class="badge bg-info text-dark mb-1">{{ $item->jadwal->cycle_type }}</span><br>
                                            <strong>{{ optional($item->jadwal->kelas)->nama_kelas ?? $item->jadwal->id_kelas }}</strong> - {{ $item->jadwal->mataPelajaran->nama_mapel ?? $item->jadwal->id_mapel }}<br>
                                            <small class="text-muted">{{ $item->jadwal->hari }}, {{ $item->jadwal->jam_mulai }} - {{ $item->jadwal->jam_selesai }}</small>
                                        @else
                                            <span class="text-muted">Jadwal dihapus</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->topic_description }}</td>
                                    <td>
                                        @if($item->photo_path)
                                            <a href="{{ asset($item->photo_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Lihat</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $p = $presensiMap[$item->schedule_id . '|' . substr($item->date, 0, 10)] ?? null;
                                            $hitung = $p ? $p->detail->groupBy('status_kehadiran') : collect();
                                        @endphp
                                        @if($p)
                                            <span class="badge bg-success">H {{ $hitung->get('Hadir', collect())->count() }}</span>
                                            <span class="badge bg-info text-dark">S {{ $hitung->get('Sakit', collect())->count() }}</span>
                                            <span class="badge bg-warning text-dark">I {{ $hitung->get('Izin', collect())->count() }}</span>
                                            <span class="badge bg-danger">A {{ $hitung->get('Alfa', collect())->count() }}</span><br>
                                            <a href="{{ route('presensi.detail', $p->id_presensi) }}" class="btn btn-sm btn-outline-dark mt-1">Detail</a>
                                        @else
                                            <a href="{{ route('presensi.isi', ['id_jurnal' => $item->id]) }}" class="btn btn-sm btn-success">Isi presensi</a>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(substr((string) $item->date, 0, 10) === date('Y-m-d'))
                                            <a href="{{ route('jurnal.edit', $item->id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                        @else
                                            <span class="text-muted small" title="Jurnal hanya bisa diedit pada hari yang sama">Terkunci</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada jurnal.</td>
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
<script>
(function () {
    var siswaPerJadwal = @json($siswaPerJadwal);
    var oldStatus = @json(old('kehadiran', []));
    var oldKet = @json(old('keterangan', []));
    var warna = { Hadir: 'success', Sakit: 'info', Izin: 'warning', Alfa: 'danger' };

    var sel = document.getElementById('selJadwal');
    var baris = document.getElementById('barisSiswa');
    var area = document.getElementById('areaPresensi');
    var kosong = document.getElementById('pesanKosong');
    var awal = document.getElementById('pesanAwal');
    var info = document.getElementById('infoKelas');
    var ringkasan = document.getElementById('ringkasan');

    function hitung() {
        var n = { Hadir: 0, Sakit: 0, Izin: 0, Alfa: 0 };
        baris.querySelectorAll('input[type=radio]:checked').forEach(function (r) { n[r.value]++; });
        ringkasan.textContent = 'Hadir ' + n.Hadir + ' · Sakit ' + n.Sakit + ' · Izin ' + n.Izin + ' · Alfa ' + n.Alfa;
    }

    function el(tag, attrs, text) {
        var e = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) { e.setAttribute(k, attrs[k]); });
        if (text !== undefined) e.textContent = text;
        return e;
    }

    function render() {
        baris.innerHTML = '';
        var id = sel.value;
        area.classList.add('d-none'); kosong.classList.add('d-none'); awal.classList.add('d-none');
        info.textContent = ''; ringkasan.textContent = '';
        if (!id) { awal.classList.remove('d-none'); return; }

        var daftar = siswaPerJadwal[id] || [];
        var opt = sel.options[sel.selectedIndex];
        info.textContent = '(' + daftar.length + ' siswa)';
        if (daftar.length === 0) { kosong.classList.remove('d-none'); return; }

        daftar.forEach(function (s, i) {
            var status = oldStatus[s.id] || s.status;
            var ket = (oldKet[s.id] !== undefined) ? oldKet[s.id] : s.ket;
            var tr = el('tr');
            tr.appendChild(el('td', { 'class': 'text-muted' }, String(i + 1)));
            tr.appendChild(el('td', { 'class': 'fw-semibold' }, s.nama));
            var tdN = el('td'); tdN.appendChild(el('small', { 'class': 'text-muted' }, s.nisn)); tr.appendChild(tdN);

            var tdS = el('td'); var wrap = el('div', { 'class': 'd-flex gap-2 flex-wrap' });
            Object.keys(warna).forEach(function (st) {
                var box = el('div', { 'class': 'form-check form-check-inline m-0' });
                var rid = 's' + s.id + '_' + st;
                var inp = el('input', { 'class': 'form-check-input', type: 'radio', name: 'kehadiran[' + s.id + ']', id: rid, value: st, required: 'required' });
                if (st === status) inp.checked = true;
                inp.addEventListener('change', hitung);
                var lab = el('label', { 'class': 'form-check-label badge bg-' + warna[st] + (st === 'Sakit' || st === 'Izin' ? ' text-dark' : ''), 'for': rid }, st);
                box.appendChild(inp); box.appendChild(lab); wrap.appendChild(box);
            });
            tdS.appendChild(wrap); tr.appendChild(tdS);

            var tdK = el('td');
            var k = el('input', { type: 'text', name: 'keterangan[' + s.id + ']', 'class': 'form-control form-control-sm', placeholder: 'Keterangan (opsional)', maxlength: '255' });
            k.value = ket || '';
            tdK.appendChild(k); tr.appendChild(tdK);
            baris.appendChild(tr);
        });
        area.classList.remove('d-none');
        hitung();
    }

    window.setAll = function (status) {
        baris.querySelectorAll('input[type=radio][value="' + status + '"]').forEach(function (r) { r.checked = true; });
        hitung();
    };

    sel.addEventListener('change', render);
    render();
})();
</script>
@endpush
