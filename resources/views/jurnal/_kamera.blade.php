{{-- Ambil foto bukti KBM langsung dari kamera (tanpa memilih dari galeri/file).
     Pemakaian: sertakan partial ini di dalam form, dengan variabel wajib true atau false. --}}
@php $kamId = 'kam' . uniqid(); @endphp
<div id="{{ $kamId }}" data-wajib="{{ ($wajib ?? false) ? '1' : '0' }}">
    <input type="file" name="photo" class="d-none" accept="image/jpeg" data-role="input">

    <div class="mb-2 d-none" data-role="previewWrap">
        <img data-role="preview" class="img-thumbnail" style="max-height:180px" alt="Foto bukti KBM">
    </div>

    <div class="mb-2 d-none" data-role="live">
        <video data-role="video" autoplay playsinline muted
               style="width:100%;max-width:420px;border-radius:8px;background:#000"></video>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" data-role="buka">Buka kamera</button>
        <button type="button" class="btn btn-success btn-sm d-none" data-role="ambil">Ambil foto</button>
        <button type="button" class="btn btn-outline-secondary btn-sm d-none" data-role="batal">Batal</button>
    </div>

    {{-- Cadangan bila browser tidak mengizinkan kamera langsung (mis. bukan HTTPS): membuka kamera HP --}}
    <div class="mt-2 d-none" data-role="fallback">
        <input type="file" class="form-control form-control-sm" accept="image/*" capture="environment" data-role="fallbackInput">
    </div>

    <small class="text-muted d-block mt-1" data-role="pesan">
        Foto harus diambil langsung dengan kamera, tidak bisa memilih dari galeri.
    </small>
</div>

<script>
(function () {
    var root = document.getElementById(@json($kamId));
    function q(r) { return root.querySelector('[data-role="' + r + '"]'); }

    var input = q('input'), preview = q('preview'), previewWrap = q('previewWrap');
    var live = q('live'), video = q('video');
    var buka = q('buka'), ambil = q('ambil'), batal = q('batal');
    var fallback = q('fallback'), fallbackInput = q('fallbackInput'), pesan = q('pesan');
    var wajib = root.getAttribute('data-wajib') === '1';
    var stream = null;

    function setFile(file) {
        var dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        preview.src = URL.createObjectURL(file);
        previewWrap.classList.remove('d-none');
        buka.textContent = 'Ulangi foto';
    }

    function stop() {
        if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
        video.srcObject = null;
        live.classList.add('d-none');
        ambil.classList.add('d-none');
        batal.classList.add('d-none');
        buka.classList.remove('d-none');
    }

    buka.addEventListener('click', function () {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            // Browser memblokir kamera langsung (biasanya karena bukan HTTPS): pakai kamera bawaan HP
            fallback.classList.remove('d-none');
            buka.classList.add('d-none');
            pesan.textContent = 'Kamera langsung tidak tersedia di alamat ini. Gunakan tombol di atas untuk membuka kamera.';
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
            .then(function (s) {
                stream = s;
                video.srcObject = s;
                live.classList.remove('d-none');
                ambil.classList.remove('d-none');
                batal.classList.remove('d-none');
                buka.classList.add('d-none');
                pesan.textContent = 'Arahkan kamera ke kegiatan belajar mengajar, lalu tekan Ambil foto.';
            })
            .catch(function (e) {
                pesan.textContent = (e && e.name === 'NotFoundError')
                    ? 'Kamera tidak ditemukan di perangkat ini.'
                    : 'Kamera tidak bisa dibuka. Izinkan akses kamera pada browser lalu coba lagi.';
            });
    });

    ambil.addEventListener('click', function () {
        var w = video.videoWidth, h = video.videoHeight;
        if (!w || !h) { return; }
        var skala = Math.min(1, 1280 / Math.max(w, h));
        var c = document.createElement('canvas');
        c.width = Math.round(w * skala);
        c.height = Math.round(h * skala);
        c.getContext('2d').drawImage(video, 0, 0, c.width, c.height);
        c.toBlob(function (blob) {
            if (!blob) { return; }
            setFile(new File([blob], 'kbm_' + Date.now() + '.jpg', { type: 'image/jpeg' }));
            stop();
            pesan.textContent = 'Foto berhasil diambil.';
        }, 'image/jpeg', 0.85);
    });

    batal.addEventListener('click', function () {
        stop();
        pesan.textContent = 'Foto harus diambil langsung dengan kamera, tidak bisa memilih dari galeri.';
    });

    fallbackInput.addEventListener('change', function () {
        if (fallbackInput.files.length) {
            setFile(fallbackInput.files[0]);
            pesan.textContent = 'Foto berhasil diambil.';
        }
    });

    var form = root.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (wajib && input.files.length === 0) {
                e.preventDefault();
                pesan.textContent = 'Foto bukti KBM wajib diambil dengan kamera sebelum menyimpan.';
                pesan.classList.add('text-danger');
                root.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
    window.addEventListener('beforeunload', stop);
})();
</script>
