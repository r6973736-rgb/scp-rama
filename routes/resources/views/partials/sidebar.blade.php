@php
    $role = (int) session('id_role');
    $namaPeran = [1 => 'Admin', 2 => 'Guru', 3 => 'Wali kelas', 4 => 'Waka kurikulum', 5 => 'Siswa', 7 => 'Guru BK'];
    $peran = $namaPeran[$role] ?? '';

    // [teks, nama route, pola route aktif, ikon]
    $menuGuru = [['Beranda', 'guru.dashboard', 'guru.dashboard', 'home'],
                 ['Jurnal & presensi', 'jurnal.index', ['jurnal.*', 'presensi.*'], 'buku'],
                 ['Jadwal pelajaran', 'jadwal.index', 'jadwal.*', 'kalender']];
    $menuIzin = ['Verifikasi izin', 'kesiswaan.izin.index', 'kesiswaan.*', 'cek'];

    // Jumlah siswa izin/sakit hari ini (wali kelas: kelasnya saja, BK & Waka: semua) sebagai lencana menu
    $jumlahIzinHariIni = 0;
    if (in_array($role, [3, 4, 7], true)) {
        try {
            $jumlahIzinHariIni = \App\Support\LaporanIzinHarian::jumlahHariIni();
        } catch (\Throwable $e) {
            $jumlahIzinHariIni = 0;
        }
    }
    $menuRekapPresensi = ['Rekap presensi', 'rekap.presensi', 'rekap.*', 'buku'];
    $menuLaporanIzin = ['Laporan izin harian', 'laporan.izin.harian', 'laporan.*', 'surat', $jumlahIzinHariIni ?: null];

    // Jumlah permintaan lupa password yang menunggu (hanya untuk Waka)
    $jumlahReset = 0;
    if ($role === 4) {
        try {
            $jumlahReset = \Illuminate\Support\Facades\DB::table('permintaan_reset_password')->where('status', 'Menunggu')->count();
        } catch (\Throwable $e) {
            $jumlahReset = 0; // tabel belum dibuat
        }
    }

    // Wali kelas / Waka yang juga mengajar: NIP-nya ada di tabel guru
    $mengajar = in_array($role, [3, 4], true)
        && \Illuminate\Support\Facades\DB::table('guru')->where('nip', session('username'))->exists();
    $menuJurnal  = ['Jurnal & presensi', 'jurnal.index', ['jurnal.*', 'presensi.*'], 'buku'];
    $menuJadwal  = ['Jadwal pelajaran', 'jadwal.index', 'jadwal.*', 'kalender'];

    if ($role === 5) {
        $menu = [['Pengajuan izin', 'siswa.izin.index', 'siswa.*', 'surat']];
    } elseif ($role === 3) {
        $menu = [$menuIzin, $menuLaporanIzin, $menuRekapPresensi];
        if ($mengajar) { $menu[] = $menuJurnal; $menu[] = $menuJadwal; }
    } elseif ($role === 4) {
        $menu = [['Panel Waka', 'waka.dashboard', 'waka.dashboard', 'home'],
                 ['Rekap jurnal', 'waka.jurnal.rekap', 'waka.jurnal.*', 'buku'],
                 $menuLaporanIzin,
                 $menuRekapPresensi,
                 ['Jadwal pelajaran', 'jadwal.index', 'jadwal.*', 'kalender'],
                 ['Manajemen data', 'data.index', 'data.*', 'data'],
                 ['Kelola akun', 'akun.index', 'akun.*', 'orang'],
                 ['Reset password', 'reset.index', 'reset.*', 'kunci', $jumlahReset ?: null]];
        if ($mengajar) { array_splice($menu, 1, 0, [$menuJurnal]); }
    } elseif ($role === 7) {
        // Guru BK: hanya memverifikasi izin siswa (tidak mengajar, tanpa jurnal & jadwal)
        $menu = [$menuIzin, $menuLaporanIzin];
    } elseif ($role === 2) {
        $menu = $menuGuru;
    } else {
        $menu = [];
    }

    // Rumah/Dashboard awal role
    $rumah = $menu ? route($menu[0][1]) : route('login');
@endphp

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></symbol>
    <symbol id="i-buku" viewBox="0 0 24 24"><path d="M2 5h7a3 3 0 0 1 3 3v12a2 2 0 0 0-2-2H2z"/><path d="M22 5h-7a3 3 0 0 0-3 3v12a2 2 0 0 1 2-2h8z"/></symbol>
    <symbol id="i-kalender" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></symbol>
    <symbol id="i-orang" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M18 14c2.5.3 4 2 4 4.5"/></symbol>
    <symbol id="i-surat" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></symbol>
    <symbol id="i-cek" viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/><path d="M9 14l2 2 4-4"/></symbol>
    <symbol id="i-keluar" viewBox="0 0 24 24"><path d="M10 4H5v16h5"/><path d="M15 8l4 4-4 4M19 12H9"/></symbol>
    <symbol id="i-kunci" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></symbol>
    <symbol id="i-data" viewBox="0 0 24 24"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
</svg>

<aside class="samping">
    <div class="samping-atas">
        <a class="merek" href="{{ $rumah }}">
            <img src="{{ asset('images/logo-smkn1bantul.png') }}" alt="Logo SMKN 1 Bantul"
                 onerror="this.onerror=null;this.src='https://smkn1bantul.sch.id/logo.png'">
            <span>
                <span class="nama">SMK Negeri 1 Bantul</span>
                <span class="sub">Jadwal, jurnal, dan izin</span>
            </span>
        </a>
        <button class="tombol-menu" type="button" data-bs-toggle="collapse" data-bs-target="#menuSamping"
                aria-controls="menuSamping" aria-expanded="false" aria-label="Buka menu">
            <svg class="ikon"><use href="#i-menu"/></svg>
        </button>
    </div>

    {{-- Kartu Profil Pengguna di Bagian Paling Atas Sidebar --}}
    <div class="samping-profil">
        <a href="{{ route('profil.index') }}" class="kartu-profil-atas {{ request()->routeIs('profil.*') ? 'aktif' : '' }}" title="Buka Profil Saya">
            <div class="avatar-lingkar">
                {{ strtoupper(substr(session('nama_lengkap', 'U'), 0, 1)) }}
            </div>
            <div class="profil-teks">
                <div class="profil-nama">{{ session('nama_lengkap') }}</div>
                <div class="profil-peran-badge">{{ $peran }}</div>
            </div>
            <svg class="profil-panah" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </a>
    </div>

    <div class="samping-isi collapse d-lg-flex" id="menuSamping">
        <nav class="menu" aria-label="Menu utama">
            @foreach($menu as $m)
                <a href="{{ route($m[1]) }}" class="{{ request()->routeIs(...(array) $m[2]) ? 'aktif' : '' }}"
                   @if(request()->routeIs(...(array) $m[2])) aria-current="page" @endif>
                    <svg class="ikon"><use href="#i-{{ $m[3] }}"/></svg>{{ $m[0] }}
                    @if(!empty($m[4]))<span class="lencana">{{ $m[4] }}</span>@endif
                </a>
            @endforeach
        </nav>

        <div class="pengguna">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-keluar" onclick="return confirm('Yakin ingin keluar?')">
                    <svg class="ikon"><use href="#i-keluar"/></svg>Keluar
                </button>
            </form>
        </div>
    </div>
</aside>
