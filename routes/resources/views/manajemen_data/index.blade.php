@extends('layouts.app')

@section('judul', 'Manajemen data')
@section('sub', 'Kelola data induk sekolah: jurusan, kelas, mata pelajaran, ruangan, guru, dan tahun ajaran.')

@section('isi')
@php
    $kolomTampil = $aktif['kolom'];
    $teksSel = function ($nama, $k, $nilai) use ($opsi) {
        if ($k[1] === 'check') { return $nilai ? 'Aktif' : '-'; }
        if ($k[1] === 'select' && $nilai !== null && $nilai !== '') {
            $daftar = is_string($k[3] ?? null) ? ($opsi[$k[3]] ?? []) : ($k[3] ?? []);
            return $daftar[$nilai] ?? $nilai;
        }
        return ($nilai === null || $nilai === '') ? '-' : $nilai;
    };
@endphp

<ul class="nav nav-pills mb-3 flex-wrap gap-1">
    @foreach($skema as $kode => $s)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $kode ? 'active' : '' }}" href="{{ route('data.index', ['tab' => $kode]) }}">
                {{ $s['label'] }} <span class="badge bg-light text-dark ms-1">{{ $jumlah[$kode] }}</span>
            </a>
        </li>
    @endforeach
</ul>

<div class="row g-4">
    <div class="col-xl-4 col-lg-5">
        <div class="card mb-4">
            <div class="card-header">Tambah {{ strtolower($aktif['label']) }}</div>
            <div class="card-body">
                <form action="{{ route('data.store', $tab) }}" method="POST">
                    @csrf
                    @unless($aktif['auto'])
                        <div class="mb-3">
                            <label class="form-label" for="tambah_pk">{{ $aktif['pk_label'] }}</label>
                            <input type="text" name="{{ $aktif['pk'] }}" id="tambah_pk" class="form-control"
                                   value="{{ old($aktif['pk']) }}" required>
                        </div>
                    @endunless
                    @foreach($aktif['kolom'] as $nama => $k)
                        @include('manajemen_data._field', ['nama' => $nama, 'k' => $k, 'opsi' => $opsi, 'awal' => old($nama), 'idp' => 'tambah'])
                    @endforeach
                    <button type="submit" class="btn btn-primary w-100">Simpan</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Import {{ strtolower($aktif['label']) }} dari {{ $bisaExcel ? 'CSV / Excel' : 'CSV' }}</div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Judul kolom (baris pertama): <code>{{ implode(', ', array_merge($aktif['auto'] ? [] : [$aktif['pk']], array_keys($aktif['kolom']))) }}</code>.
                    @if($tab === 'kelas') Kolom <code>id_wali_kelas</code> boleh diisi NIP guru. @endif
                    @if($tab === 'tahun') Kolom <code>is_aktif</code>: isi 1 untuk aktif, 0 untuk tidak. @endif
                    @if($tab === 'guru') Isi kolom NIP sebagai teks agar angkanya tidak berubah. @endif
                </p>
                <a href="{{ route('data.template', $tab) }}" class="btn btn-sm btn-outline-secondary mb-3">Unduh template CSV</a>

                <form action="{{ route('data.import', $tab) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="berkas" class="form-control" accept="{{ $bisaExcel ? '.csv,.txt,.xlsx' : '.csv,.txt' }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kalau datanya sudah ada</label>
                        <select name="mode" class="form-select">
                            <option value="lewati">Lewati (jangan diubah)</option>
                            <option value="perbarui">Perbarui dengan isi berkas</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Import</button>
                </form>
                <p class="small text-muted mt-2 mb-0">Kalau ada satu baris yang salah, seluruh import dibatalkan dan nomor baris yang salah ditampilkan.</p>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7">
        <div class="card">
            <div class="card-header">Daftar {{ strtolower($aktif['label']) }}</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px">No</th>
                                <th>{{ $aktif['pk_label'] }}</th>
                                @foreach($kolomTampil as $nama => $k)
                                    <th>{{ $k[1] === 'check' ? 'Status' : $k[0] }}</th>
                                @endforeach
                                <th style="width:140px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($baris as $i => $b)
                                @php $pk = $b->{$aktif['pk']}; @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $pk }}</td>
                                    @foreach($kolomTampil as $nama => $k)
                                        <td>{{ $teksSel($nama, $k, $b->{$nama}) }}</td>
                                    @endforeach
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit"
                                                data-id="{{ $pk }}"
                                                data-nilai="{{ json_encode((array) $b) }}">Ubah</button>
                                        <form action="{{ route('data.destroy', [$tab, $pk]) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Hapus {{ strtolower($aktif['label']) }} ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($kolomTampil) + 3 }}" class="text-center text-muted">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal ubah data --}}
<div class="modal fade" id="modalUbah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="formUbah" method="POST" action="">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Ubah {{ strtolower($aktif['label']) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ $aktif['pk_label'] }}</label>
                    <input type="text" id="ubah_pk" class="form-control" disabled>
                </div>
                @foreach($aktif['kolom'] as $nama => $k)
                    @include('manajemen_data._field', ['nama' => $nama, 'k' => $k, 'opsi' => $opsi, 'awal' => null, 'idp' => 'ubah'])
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const urlUbah = @json(route('data.update', [$tab, '__ID__']));
    const modalUbah = new bootstrap.Modal(document.getElementById('modalUbah'));
    document.querySelectorAll('.btn-edit').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            const nilai = JSON.parse(tombol.dataset.nilai);
            document.getElementById('formUbah').action = urlUbah.replace('__ID__', encodeURIComponent(tombol.dataset.id));
            document.getElementById('ubah_pk').value = tombol.dataset.id;
            Object.keys(nilai).forEach(function (nama) {
                const el = document.getElementById('ubah_' + nama);
                if (!el) return;
                if (el.type === 'checkbox') { el.checked = !!Number(nilai[nama]); }
                else { el.value = nilai[nama] === null ? '' : nilai[nama]; }
            });
            modalUbah.show();
        });
    });
</script>
@endpush
