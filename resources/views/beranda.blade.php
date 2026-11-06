@php
    $tujuan = [
        4 => 'waka.dashboard',
        3 => 'kesiswaan.izin.index',
        2 => 'guru.dashboard',
        7 => 'kesiswaan.izin.index',
        5 => 'siswa.izin.index',
    ];
    $rute = $tujuan[session('id_role')] ?? null;
    $sudahMasuk = session('id_user') && $rute;
    $situs = 'https://smkn1bantul.sch.id';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMK Negeri 1 Bantul</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.head-theme')
    <link rel="stylesheet" href="{{ asset('css/beranda.css') }}?v=1">
</head>
<body>

<div class="topstrip">
    <div class="wrap">
        <span>Jl. Parangtritis Km 11, Sabdodadi, Bantul</span>
        <span>Hotline <a href="tel:+6285122603820">+62 851-2260-3820</a></span>
    </div>
</div>

<div class="situs-nav">
    <div class="wrap">
        <nav class="navbar navbar-expand-lg">
            <a class="navbar-brand" href="{{ url('/') }}">
                <img src="{{ asset('images/logo-smkn1bantul.png') }}" alt="Logo SMKN 1 Bantul"
                     onerror="this.onerror=null;this.src='{{ $situs }}/logo.png'">
                <span>
                    <span class="nama d-block">SMK Negeri 1 Bantul</span>
                    <span class="sub">Sekolah Pusat Keunggulan</span>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama" aria-controls="menuUtama" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="menuUtama">
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="#sambutan">Sambutan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#visi-misi">Visi dan misi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#program">Program keahlian</a></li>
                    <li class="nav-item"><a class="nav-link" href="#prestasi">Prestasi dan berita</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                    <li class="nav-item ms-lg-2">
                        @if($sudahMasuk)
                            <a class="btn btn-primary" href="{{ route($rute) }}">Halaman saya</a>
                        @else
                            <a class="btn btn-primary" href="{{ route('login') }}">Masuk</a>
                        @endif
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>

<header class="hero">
    <img class="foto" src="{{ asset('images/gedung-sekolah.jpg') }}" alt="Gedung SMK Negeri 1 Bantul"
         onerror="this.onerror=null;this.src='https://upload.wikimedia.org/wikipedia/commons/b/b6/Front_view_of_SMKN_1_Bantul.jpg'">
    <div class="isi">
        <h1>Selamat datang di SMK Negeri 1 Bantul</h1>
        <p>Center of Excellence School. Sekolah Pusat Keunggulan. SMK Bisa Hebat.</p>
        @if($sudahMasuk)
            <a class="btn btn-putih" href="{{ route($rute) }}">Buka halaman saya</a>
        @else
            <a class="btn btn-putih" href="{{ route('login') }}">Masuk ke sistem</a>
        @endif
        <a class="btn btn-garis ms-2" href="#program">Program keahlian</a>
    </div>
</header>

<div class="layanan">
    <div class="wrap">
        <div class="item">
            <h3>Pengajuan izin dan sakit</h3>
            <p>Siswa mengirim surat izin beserta bukti, lalu wali kelas memverifikasinya.</p>
        </div>
        <div class="item">
            <h3>Jurnal mengajar</h3>
            <p>Guru mencatat jurnal pembelajaran dari jadwal yang sudah ditetapkan.</p>
        </div>
        <div class="item">
            <h3>Jadwal dan akun</h3>
            <p>Waka kurikulum mengatur jadwal pelajaran dan akun pengguna.</p>
        </div>
    </div>
</div>

<section class="bagian" id="sambutan">
    <div class="wrap">
        <h2>Sambutan kepala sekolah</h2>
        <div class="sambutan">
            <div>
                <div class="foto-kepsek">
                    <span>R</span>
                    <img src="{{ asset('images/kepsek.jpeg') }}" alt="Raharjo, S.IP, M.Pd"
                         onerror="if(!this.dataset.t){this.dataset.t=1;this.src='{{ $situs }}/assets/images/kepsek.jpeg'}else{this.style.display='none'}">
                </div>
                <div class="nama-kepsek">Raharjo, S.IP, M.Pd</div>
                <div class="jabatan">Kepala Sekolah SMKN 1 Bantul</div>
            </div>
            <div class="teks">
                <p>Assalamualaikum warahmatullahi wabarakatuh. Salam sejahtera bagi kita semua.</p>
                <p>Saya, Raharjo, M.Pd., Kepala SMK Negeri 1 Bantul, dengan bangga menyampaikan visi dan misi sekolah kami, yaitu mencetak lulusan yang unggul, berkompeten, dan siap bersaing di dunia global. Visi ini kami wujudkan melalui pendidikan yang berbasis pada penguatan karakter, keterampilan, dan penguasaan teknologi. Kami percaya bahwa dengan pendidikan yang berkualitas, kami dapat menyiapkan generasi yang siap menghadapi tantangan masa depan, baik di dunia kerja, wirausaha, maupun pendidikan tinggi.</p>
                <p>Harapan besar kami adalah lulusan SMK Negeri 1 Bantul menjadi generasi yang siap kerja di dunia industri, siap berwirausaha dengan ide-ide kreatifnya, serta siap melanjutkan pendidikan ke jenjang yang lebih tinggi. Teruslah belajar, berinovasi, dan berkontribusi untuk masa depan yang lebih baik.</p>
                <p>Wassalamualaikum warahmatullahi wabarakatuh.</p>
            </div>
        </div>
    </div>
</section>

<section class="bagian putih" id="visi-misi">
    <div class="wrap">
        <h2>Visi dan misi</h2>
        <p class="visi">&ldquo;Terwujudnya peserta didik dan lulusan yang berkualitas, berkarakter, berwawasan lingkungan, dan mampu berkolaborasi.&rdquo;</p>
        <div class="misi">
            <div>
                <h3>Berkualitas</h3>
                <p>Menyiapkan tamatan yang mampu mengisi dan menciptakan lapangan kerja serta mengembangkan profesionalitas di bidang bisnis.</p>
            </div>
            <div>
                <h3>Berkarakter</h3>
                <p>Mengimplementasikan iman, takwa, dan nilai-nilai karakter bangsa dalam kehidupan sehari-hari.</p>
            </div>
            <div>
                <h3>Berwawasan lingkungan</h3>
                <p>Melaksanakan pembelajaran berbasis lingkungan serta mengaplikasikannya dalam kehidupan sehari-hari.</p>
            </div>
            <div>
                <h3>Mampu berkolaborasi</h3>
                <p>Melaksanakan pembelajaran yang berbasis sains dan teknologi untuk membangun jejaring dan komunikasi efektif.</p>
            </div>
        </div>
    </div>
</section>

<section class="bagian" id="program">
    <div class="wrap">
        <h2>Program keahlian</h2>
        <div class="program">
            <a href="{{ $situs }}" target="_blank" rel="noopener">Akuntansi dan Keuangan Lembaga</a>
            <a href="{{ $situs }}" target="_blank" rel="noopener">Layanan Perbankan Syariah</a>
            <a href="{{ $situs }}" target="_blank" rel="noopener">Manajemen Perkantoran dan Layanan Bisnis</a>
            <a href="{{ $situs }}" target="_blank" rel="noopener">Pemasaran</a>
            <a href="{{ $situs }}" target="_blank" rel="noopener">Desain Komunikasi Visual</a>
            <a href="{{ $situs }}" target="_blank" rel="noopener">Rekayasa Perangkat Lunak</a>
            <a href="{{ $situs }}" target="_blank" rel="noopener">Teknik Komputer Jaringan</a>
        </div>
    </div>
</section>

<section class="bagian putih" id="prestasi">
    <div class="wrap dua-kolom">
        <div class="daftar-prestasi">
            <h2>Prestasi</h2>
            <div class="butir">
                <div class="juara">Medallion for Excellence, Web Technologies</div>
                <p class="siapa">Muhammad Eksa Arifa, XII RPL 1</p>
                <div class="ket">Tingkat LKS Nasional 2024</div>
            </div>
            <div class="butir">
                <div class="juara">Juara 1 Festival Inovasi Kewirausahaan Siswa Indonesia</div>
                <p class="siapa">Haryo Djati R (XII RPL 1) dan Anggara Deni A (XII MP 2)</p>
                <div class="ket">Tingkat FIKSI D.I. Yogyakarta</div>
            </div>
            <div class="butir">
                <div class="juara">Juara 1 Web Technologies</div>
                <p class="siapa">Muhammad Eksa Arifa, XII RPL 1</p>
                <div class="ket">Tingkat LKS Provinsi D.I. Yogyakarta</div>
            </div>
            <a class="tautan-lanjut" href="{{ $situs }}/prestasi" target="_blank" rel="noopener">Semua prestasi di situs sekolah</a>
        </div>
        <div class="daftar-berita">
            <h2>Berita</h2>
            <a href="{{ $situs }}/berita/smkn-1-bantul-perkuat-kemitraan-industri-melalui-penandatanganan-mou-dengan-bnet" target="_blank" rel="noopener">SMKN 1 Bantul perkuat kemitraan industri melalui penandatanganan MoU dengan Bnet</a>
            <a href="{{ $situs }}/berita/pengumuman-dan-daftar-ulang-spmb-20262027" target="_blank" rel="noopener">Pengumuman dan daftar ulang SPMB 2026/2027</a>
            <a href="{{ $situs }}/berita/selamat-untuk-576-siswa-baru-smkn-1-bantul" target="_blank" rel="noopener">Selamat untuk 576 siswa baru SMKN 1 Bantul</a>
            <a href="{{ $situs }}/berita/snbp-2026" target="_blank" rel="noopener">SNBP 2026</a>
            <a href="{{ $situs }}/berita/tim-publikasi-smkn-1-bantul" target="_blank" rel="noopener">Tim Publikasi SMKN 1 Bantul</a>
            <a class="tautan-lanjut" style="border:0;padding:0" href="{{ $situs }}/berita" target="_blank" rel="noopener">Berita lainnya</a>
        </div>
    </div>
</section>

<footer class="footer-sekolah" id="kontak">
    <div class="wrap">
        <div class="kolom">
            <div>
                <h4>SMK Negeri 1 Bantul</h4>
                <p>Jl. Parangtritis No.KM.11, Dukuh, Sabdodadi, Kec. Bantul, Kab. Bantul, Daerah Istimewa Yogyakarta 55715</p>
                <p>Hotline: <a href="tel:+6285122603820">+62 851-2260-3820</a></p>
            </div>
            <div>
                <h4>Tautan</h4>
                <p><a href="{{ $situs }}" target="_blank" rel="noopener">Situs resmi sekolah</a></p>
                <p><a href="https://instagram.com/smkn1bantul" target="_blank" rel="noopener">Instagram @smkn1bantul</a></p>
            </div>
            <div>
                <h4>Sistem sekolah</h4>
                @if($sudahMasuk)
                    <p><a href="{{ route($rute) }}">Halaman saya</a></p>
                @else
                    <p><a href="{{ route('login') }}">Masuk</a></p>
                    <p><a href="{{ route('register') }}">Daftar akun</a></p>
                @endif
            </div>
        </div>
        <div class="hak">Copyright &copy; {{ date('Y') }} SMKN 1 Bantul. Profil, prestasi, dan berita bersumber dari smkn1bantul.sch.id.</div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
