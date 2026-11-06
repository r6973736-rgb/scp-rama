@extends('layouts.app')

@section('judul', 'Dashboard')
@section('sub', 'Jadwal pelajaran, pengecekan bentrok, dan jurnal mengajar guru.')

@section('isi')
<div class="row g-4">
    <div class="col-md-6">
        <a href="{{ route('jadwal.index') }}" class="aksi aksi-besar">
            <span class="lambang"><svg class="ikon"><use href="#i-kalender"/></svg></span>
            <div><h3>Kelola jadwal pelajaran</h3><p>Atur jadwal dan uji bentrok otomatis.</p></div>
            <span class="btn btn-primary buka">Buka jadwal</span>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('jurnal.index') }}" class="aksi aksi-besar">
            <span class="lambang"><svg class="ikon"><use href="#i-buku"/></svg></span>
            <div><h3>Jurnal mengajar</h3><p>Catat materi dan foto kegiatan belajar.</p></div>
            <span class="btn btn-outline-primary buka">Buka jurnal</span>
        </a>
    </div>
</div>
@endsection
