@php
    $namaPeran = [1 => 'Admin', 2 => 'Guru', 3 => 'Wali kelas', 4 => 'Waka kurikulum', 5 => 'Siswa', 7 => 'Guru BK'];
    $peran = $namaPeran[session('id_role')] ?? null;
@endphp
<header class="school-header">
    <div class="inner">
        <a class="brand" href="{{ url('/') }}">
            <img src="{{ asset('images/logo-smkn1bantul.png') }}" alt="Logo SMKN 1 Bantul"
                 onerror="this.onerror=null;this.src='https://smkn1bantul.sch.id/logo.png'">
            <span>
                <span class="nama d-block">SMK Negeri 1 Bantul</span>
                <span class="sub d-block">Presensi, jurnal, dan perizinan</span>
            </span>
        </a>
        @if(session('nama_lengkap'))
            <div class="akun">
                <div class="nm">{{ session('nama_lengkap') }}</div>
                @if($peran)<div class="peran">{{ $peran }}</div>@endif
            </div>
        @endif
    </div>
</header>
