@extends('layouts.app')

@section('judul', 'Beranda guru')
@section('sub', 'Selamat datang, ' . session('nama_lengkap') . '.')

@section('isi')
<div class="row g-4">
    <div class="col-md-6">
        <a href="{{ route('jurnal.index') }}" class="aksi aksi-besar">
            <span class="lambang"><svg class="ikon"><use href="#i-buku"/></svg></span>
            <div>
                <h3>Jurnal mengajar</h3>
                <p>Isi jurnal kegiatan belajar mengajar harian beserta foto kegiatan.</p>
            </div>
            <span class="btn btn-primary buka">Buka jurnal</span>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('jadwal.index') }}" class="aksi aksi-besar">
            <span class="lambang"><svg class="ikon"><use href="#i-kalender"/></svg></span>
            <div>
                <h3>Jadwal pelajaran</h3>
                <p>Lihat jadwal mengajar, status putaran blok, dan pengecekan jadwal bentrok.</p>
            </div>
            <span class="btn btn-outline-primary buka">Buka jadwal</span>
        </a>
    </div>
</div>
@endsection
