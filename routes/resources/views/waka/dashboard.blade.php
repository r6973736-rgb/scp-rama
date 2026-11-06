@extends('layouts.app')

@section('judul', 'Panel Waka Kurikulum')
@section('sub', 'Atur putaran blok, kunci jadwal, dan pantau kegiatan guru.')

@section('isi')
<style>
    .alert-waka { border: 1px solid var(--garis, #d9e0ea); border-radius: 8px; background: #fff; margin-bottom: 24px; overflow: hidden; }
    .alert-waka .aw-kepala { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 18px; background: #eef3fa; border-bottom: 1px solid var(--garis, #d9e0ea); }
    .alert-waka.ada-alert .aw-kepala { background: #fdecec; border-bottom-color: #f2b8b8; }
    .alert-waka .aw-judul { font-weight: 700; color: var(--navy, #12305c); flex: 1 1 260px; }
    .alert-waka.ada-alert .aw-judul { color: #a61b1b; }
    .alert-waka .aw-jam { font-variant-numeric: tabular-nums; color: var(--redup, #667); font-size: .9rem; }
    .aw-baris { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 12px 18px; border-top: 1px solid var(--garis, #e3e8ef); }
    .aw-baris:first-of-type { border-top: 0; }
    .aw-hitung { min-width: 74px; text-align: center; font-variant-numeric: tabular-nums; font-weight: 700; font-size: 1.25rem; color: #a61b1b; }
    .aw-hitung small { display: block; font-weight: 500; font-size: .7rem; color: var(--redup, #667); }
    .aw-info { flex: 1 1 240px; min-width: 0; }
    .aw-info .aw-mapel { font-weight: 600; }
    .aw-info .aw-meta { color: var(--redup, #667); font-size: .88rem; }
    .aw-tanda { display: flex; gap: 6px; flex-wrap: wrap; }
    .aw-tanda .badge { font-weight: 600; }
    .aw-subjudul { padding: 8px 18px; font-size: .78rem; letter-spacing: .04em; text-transform: uppercase; color: var(--redup, #667); background: #f7f9fc; border-top: 1px solid var(--garis, #e3e8ef); }
    .aw-kosong { padding: 16px 18px; color: var(--redup, #667); }
    .aw-baris.kritis { background: #fff6f6; }
    .aw-baris.kritis .aw-hitung { color: #c00; animation: awKedip 1s infinite; }
    @keyframes awKedip { 50% { opacity: .45; } }
    @media (prefers-reduced-motion: reduce) { .aw-baris.kritis .aw-hitung { animation: none; } }
</style>

<section class="alert-waka" id="alertWaka" aria-live="polite">
    <div class="aw-kepala">
        <div class="aw-judul" id="awJudul">Memuat pantauan kelas…</div>
        <span class="aw-jam" id="awJam"></span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="awNotif">Aktifkan notifikasi browser</button>
    </div>
    <div id="awIsi"></div>
</section>

<div class="ringkas">
    <div class="butir"><div class="angka">Putaran {{ $config->active_cycle }}</div><div class="label">Blok aktif saat ini</div></div>
    <div class="butir"><div class="angka">{{ $config->is_schedule_locked ? 'Terkunci' : 'Terbuka' }}</div><div class="label">Status jadwal guru</div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Siklus blok dan kunci jadwal</div>
            <div class="card-body">
                <form action="{{ route('waka.config.update') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Putaran blok aktif</label>
                        <select name="active_cycle" class="form-select" required>
                            <option value="1" {{ $config->active_cycle == '1' ? 'selected' : '' }}>Putaran 1</option>
                            <option value="2" {{ $config->active_cycle == '2' ? 'selected' : '' }}>Putaran 2</option>
                        </select>
                        <div class="form-text">Mengubah ini menyesuaikan tampilan jadwal di seluruh sistem.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Penguncian jadwal</label>
                        <select name="is_schedule_locked" class="form-select" required>
                            <option value="0" {{ !$config->is_schedule_locked ? 'selected' : '' }}>Terbuka (guru boleh mengubah jadwal)</option>
                            <option value="1" {{ $config->is_schedule_locked ? 'selected' : '' }}>Terkunci (guru tidak boleh mengubah jadwal)</option>
                        </select>
                        <div class="form-text">Jika dikunci, tombol input dan ubah jadwal milik guru dinonaktifkan.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <a href="{{ route('akun.index') }}" class="aksi">
            <span class="lambang"><svg class="ikon"><use href="#i-orang"/></svg></span>
            <div>
                <h3>Kelola akun</h3>
                <p>Buat akun baru dan tentukan hak aksesnya.</p>
            </div>
        </a>
        <a href="{{ route('waka.jurnal.rekap') }}" class="aksi">
            <span class="lambang"><svg class="ikon"><use href="#i-buku"/></svg></span>
            <div>
                <h3>Rekap jurnal mengajar</h3>
                <p>Pantau jurnal kegiatan belajar dari seluruh guru.</p>
            </div>
        </a>
        <a href="{{ route('jadwal.index') }}" class="aksi">
            <span class="lambang"><svg class="ikon"><use href="#i-kalender"/></svg></span>
            <div>
                <h3>Jadwal pelajaran</h3>
                <p>Lihat dan atur jadwal seluruh guru.</p>
            </div>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const URL_DATA = @json(route('waka.alert.data'));
    const BATAS = @json(\App\Support\AlertJamSelesai::BATAS_MENIT);
    let data = @json($alertAwal);
    let diambilPada = Date.now();
    const sudahNotif = new Set();

    const el = (id) => document.getElementById(id);
    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const pad = (n) => String(n).padStart(2, '0');

    function sisaSekarang(item) {
        return item.sisa_detik - Math.floor((Date.now() - diambilPada) / 1000);
    }
    function format(det) {
        det = Math.max(0, det);
        return pad(Math.floor(det / 60)) + ':' + pad(det % 60);
    }
    function tanda(item) {
        const b = (ok, teks) => `<span class="badge ${ok ? 'text-bg-success' : 'text-bg-danger'}">${teks} ${ok ? '✓' : 'belum'}</span>`;
        return `<div class="aw-tanda">${b(item.jurnal_ok, 'Jurnal')}${b(item.presensi_ok, 'Presensi')}</div>`;
    }
    function info(item) {
        return `<div class="aw-info">
            <div class="aw-mapel">${esc(item.kelas)} — ${esc(item.mapel)}</div>
            <div class="aw-meta">${esc(item.guru)} · Ruang ${esc(item.ruangan)} · ${esc(item.mulai)}–${esc(item.selesai)}</div>
        </div>`;
    }

    function gambar() {
        const a = data.alert, b = data.berlangsung, t = data.terlewat;
        const kotak = el('alertWaka');
        kotak.classList.toggle('ada-alert', a.length > 0);

        el('awJudul').textContent = a.length
            ? `⚠ ${a.length} kelas akan selesai ≤ ${BATAS} menit lagi, jurnal/presensi belum lengkap`
            : `Tidak ada kelas yang perlu diingatkan (batas ${BATAS} menit sebelum jam selesai)`;
        el('awJam').textContent = data.hari + ', ' + data.waktu.slice(0, 5) + ' WIB';

        let h = '';
        if (a.length) {
            h += '<div class="aw-subjudul">Segera selesai</div>';
            a.forEach(i => {
                const kritis = sisaSekarang(i) <= 5 * 60 ? ' kritis' : '';
                h += `<div class="aw-baris${kritis}" data-id="${i.id}">
                    <div class="aw-hitung" data-sisa="${i.id}">${format(sisaSekarang(i))}<small>menit:detik</small></div>
                    ${info(i)}${tanda(i)}</div>`;
            });
        }
        if (t.length) {
            h += '<div class="aw-subjudul">Sudah selesai, belum lengkap</div>';
            t.forEach(i => {
                h += `<div class="aw-baris"><div class="aw-hitung" style="color:#888;font-size:.9rem">Lewat<small>${esc(i.selesai)}</small></div>${info(i)}${tanda(i)}</div>`;
            });
        }
        if (b.length) {
            h += '<div class="aw-subjudul">Sedang berlangsung</div>';
            b.forEach(i => {
                h += `<div class="aw-baris"><div class="aw-hitung" data-sisa="${i.id}" style="color:var(--navy,#12305c)">${format(sisaSekarang(i))}<small>sisa</small></div>${info(i)}${tanda(i)}</div>`;
            });
        }
        if (!a.length && !t.length && !b.length) {
            h = '<div class="aw-kosong">Tidak ada kelas yang sedang berlangsung saat ini.</div>';
        }
        el('awIsi').innerHTML = h;

        // Notifikasi browser sekali per jadwal
        if ('Notification' in window && Notification.permission === 'granted') {
            a.forEach(i => {
                if (sudahNotif.has(i.id)) return;
                sudahNotif.add(i.id);
                new Notification('Kelas segera selesai: ' + i.kelas, {
                    body: `${i.mapel} (${i.guru}) selesai ${i.selesai}. Jurnal/presensi belum lengkap.`,
                });
            });
        }
    }

    // Hitung mundur tiap detik tanpa menggambar ulang seluruh daftar
    setInterval(() => {
        [...data.alert, ...data.berlangsung].forEach(i => {
            const s = document.querySelector(`[data-sisa="${i.id}"]`);
            if (s && s.firstChild) s.firstChild.nodeValue = format(sisaSekarang(i));
        });
        const jam = el('awJam');
        // Jam dinding diperbarui dari selisih waktu sejak data diambil
        const [H, M, S] = data.waktu.split(':').map(Number);
        const total = (H * 3600 + M * 60 + S + Math.floor((Date.now() - diambilPada) / 1000)) % 86400;
        jam.textContent = data.hari + ', ' + pad(Math.floor(total / 3600)) + ':' + pad(Math.floor(total % 3600 / 60)) + ':' + pad(total % 60) + ' WIB';
        // Jika ada yang sudah habis, ambil ulang agar statusnya pindah kelompok
        if ([...data.alert, ...data.berlangsung].some(i => sisaSekarang(i) <= 0)) segarkan();
    }, 1000);

    async function segarkan() {
        try {
            const r = await fetch(URL_DATA, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!r.ok) throw new Error(r.status);
            data = await r.json();
            diambilPada = Date.now();
            gambar();
        } catch (e) { /* gagal sementara: tampilan terakhir dipertahankan */ }
    }
    setInterval(segarkan, 30000);

    const tombol = el('awNotif');
    if (!('Notification' in window) || Notification.permission === 'granted') {
        tombol.style.display = 'none';
    }
    tombol.addEventListener('click', async () => {
        const izin = await Notification.requestPermission();
        if (izin === 'granted') { tombol.style.display = 'none'; gambar(); }
    });

    gambar();
})();
</script>
@endpush
