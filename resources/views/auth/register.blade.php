<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - SMK Negeri 1 Bantul</title>
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

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">
            @csrf
            <div class="kolom">
                <label class="sr-only" for="username">Username</label>
                <input type="text" id="username" name="username" class="isian" placeholder="NISN (dipakai sebagai username)" required value="{{ old('username') }}" autofocus>
            </div>
            <div class="kolom">
                <label class="sr-only" for="nama_lengkap">Nama lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" class="isian" placeholder="Nama lengkap" required value="{{ old('nama_lengkap') }}">
            </div>
            <div class="kolom">
                <label class="sr-only" for="no_hp">No. HP</label>
                <input type="text" id="no_hp" name="no_hp" class="isian" placeholder="No. HP" value="{{ old('no_hp') }}">
            </div>
            <div class="kolom">
                <label class="sr-only" for="password">Password</label>
                <input type="password" id="password" name="password" class="isian" placeholder="Password" required autocomplete="new-password">
            </div>
            <div class="kolom">
                <label class="sr-only" for="password_confirmation">Ulangi password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="isian" placeholder="Ulangi password" required autocomplete="new-password">
            </div>

            <hr>

            <button type="submit" class="btn-masuk">Daftar</button>
        </form>

        <p class="masuk-tautan">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </div>

    <div class="masuk-foot">&copy; {{ date('Y') }} SMK Negeri 1 Bantul</div>
</div>
</body>
</html>
