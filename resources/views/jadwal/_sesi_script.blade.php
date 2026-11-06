<script>
(function () {
    const sesiPerHari = @json($sesiPerHari);
    const awalMulai   = @json($mulaiAwal ?? null);
    const awalSelesai = @json($selesaiAwal ?? null);

    const selHari    = document.getElementById('hari');
    const selMulai   = document.getElementById('sesi_mulai');
    const selSelesai = document.getElementById('sesi_selesai');
    const info       = document.getElementById('info-sesi');

    function opsi(daftar, terpilih) {
        const teks = '<option value="">-- Pilih --</option>' + daftar.map(s =>
            `<option value="${s.sesi_ke}" ${String(terpilih) === String(s.sesi_ke) ? 'selected' : ''}>Jam ke-${s.sesi_ke} (${s.mulai} - ${s.selesai})</option>`
        ).join('');
        return teks;
    }

    function isiSesi(m, s) {
        const daftar = sesiPerHari[selHari.value] || [];
        selMulai.innerHTML   = opsi(daftar, m);
        selSelesai.innerHTML = opsi(daftar, s);
        ringkas();
    }

    function ringkas() {
        const daftar = sesiPerHari[selHari.value] || [];
        if (!selHari.value) { info.textContent = 'Pilih hari terlebih dahulu.'; return; }
        if (!daftar.length) { info.textContent = 'Belum ada data sesi untuk hari ini.'; return; }
        const a = daftar.find(x => String(x.sesi_ke) === selMulai.value);
        const b = daftar.find(x => String(x.sesi_ke) === selSelesai.value);
        if (!a || !b) { info.textContent = 'Pilih jam ke berapa pelajaran dimulai dan selesai.'; return; }
        if (b.sesi_ke < a.sesi_ke) { info.textContent = '"Sampai jam ke" tidak boleh lebih kecil dari "Mulai jam ke".'; return; }
        info.textContent = 'Waktu pelajaran: ' + a.mulai + ' - ' + b.selesai + ' (' + (b.sesi_ke - a.sesi_ke + 1) + ' jam pelajaran)';
    }

    selHari.addEventListener('change', () => isiSesi(null, null));
    selMulai.addEventListener('change', () => {
        // jika "sampai" belum dipilih, samakan dengan "mulai" (pelajaran 1 jam)
        if (!selSelesai.value) selSelesai.value = selMulai.value;
        ringkas();
    });
    selSelesai.addEventListener('change', ringkas);

    isiSesi(awalMulai, awalSelesai);
})();
</script>
