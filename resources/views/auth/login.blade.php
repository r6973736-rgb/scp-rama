<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SMK Negeri 1 Bantul</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.head-theme')
</head>
<body class="halaman-masuk">
<div class="masuk-latar">
    <img src="{{ asset('images/gedung-sekolah.jpg') }}" alt=""
         onerror="this.onerror=null;this.src='https://upload.wikimedia.org/wikipedia/commons/b/b6/Front_view_of_SMKN_1_Bantul.jpg'">
</div>

<div class="masuk-wrap">
    <div class="masuk-panel">
            <div class="masuk-merek">
                <img src="{{ asset('images/logo-smkn1bantul.png') }}" alt="Logo SMKN 1 Bantul"
                     onerror="this.onerror=null;this.src='https://smkn1bantul.sch.id/logo.png'">
                <div>
                    <div class="nama">SMK Negeri 1</div>
                    <div class="kota">Bantul</div>
                </div>
            </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="kolom">
                <label class="sr-only" for="username">NISN / NIP</label>
                <input type="text" id="username" name="username" class="isian" placeholder="NISN / NIP"
                       required value="{{ old('username') }}" autofocus autocomplete="username">
            </div>

            <div class="kolom">
                <label class="sr-only" for="password">Password</label>
                <div class="isian-grup">
                    <input type="password" id="password" name="password" class="isian" placeholder="Password"
                           required autocomplete="current-password">
                    <button type="button" class="tombol-lihat" id="lihatPassword" aria-pressed="false">Lihat</button>
                </div>
            </div>

            <div style="text-align:right;margin:-4px 0 0;font-size:.88rem">
                <a href="{{ route('lupa-password') }}" style="color:#dbe7f8">Lupa password?</a>
            </div>

            <hr>

            <button type="submit" class="btn-masuk">Masuk</button>
        </form>

        <p class="masuk-tautan">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
    </div>

    <div class="masuk-foot">
        &copy; {{ date('Y') }} SMK Negeri 1 Bantul
    </div>
</div>

<script>
    (function () {
        var tombol = document.getElementById('lihatPassword');
        var isian = document.getElementById('password');
        tombol.addEventListener('click', function () {
            var tampil = isian.type === 'password';
            isian.type = tampil ? 'text' : 'password';
            tombol.textContent = tampil ? 'Sembunyikan' : 'Lihat';
            tombol.setAttribute('aria-pressed', tampil ? 'true' : 'false');
        });
    })();
</script>
</body>
</html>
