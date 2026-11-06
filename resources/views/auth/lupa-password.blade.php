<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa password - SMK Negeri 1 Bantul</title>
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

        <h2 style="font-size:1.35rem;color:#fff;margin:0 0 6px">Lupa password</h2>
        <p style="color:#dbe7f8;font-size:.92rem;margin:0 0 20px">
            Masukkan NISN atau NIP Anda. Permintaan diteruskan ke Waka Kurikulum, yang akan memberi password sementara.
        </p>

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

        <form action="{{ route('lupa-password.kirim') }}" method="POST">
            @csrf
            <div class="kolom">
                <label class="sr-only" for="username">NISN / NIP</label>
                <input type="text" id="username" name="username" class="isian" placeholder="NISN / NIP"
                       required value="{{ old('username') }}" autofocus autocomplete="username">
            </div>
            <div class="kolom">
                <label class="sr-only" for="kontak">Nomor HP yang bisa dihubungi</label>
                <input type="text" id="kontak" name="kontak" class="isian" placeholder="Nomor HP yang bisa dihubungi (boleh kosong)"
                       value="{{ old('kontak') }}" autocomplete="tel">
            </div>

            <hr>

            <button type="submit" class="btn-masuk">Kirim permintaan</button>
        </form>

        <p class="masuk-tautan"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </div>

    <div class="masuk-foot">&copy; {{ date('Y') }} SMK Negeri 1 Bantul</div>
</div>
</body>
</html>
