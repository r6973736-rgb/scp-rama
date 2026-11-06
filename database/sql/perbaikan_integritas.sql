-- Perbaikan integritas data (jalankan di phpMyAdmin pada database yang dipakai aplikasi).
-- Tujuan: mencegah data ganda langsung di tingkat database.

-- LANGKAH 1: cek dulu apakah sudah ada data ganda. Ketiga query ini harus KOSONG.
SELECT id_jadwal, tanggal, COUNT(*) AS jumlah FROM presensi_header GROUP BY id_jadwal, tanggal HAVING COUNT(*) > 1;
SELECT id_presensi, id_siswa, COUNT(*) AS jumlah FROM presensi_detail GROUP BY id_presensi, id_siswa HAVING COUNT(*) > 1;
SELECT schedule_id, `date`, COUNT(*) AS jumlah FROM journals GROUP BY schedule_id, `date` HAVING COUNT(*) > 1;

-- LANGKAH 2: bila ketiganya kosong, jalankan tiga perintah di bawah ini.
-- (Bila ada baris yang muncul di langkah 1, hapus data gandanya dulu.)
ALTER TABLE `presensi_header` ADD UNIQUE KEY `presensi_header_jadwal_tanggal_unique` (`id_jadwal`, `tanggal`);
ALTER TABLE `presensi_detail` ADD UNIQUE KEY `presensi_detail_presensi_siswa_unique` (`id_presensi`, `id_siswa`);
ALTER TABLE `journals`        ADD UNIQUE KEY `journals_schedule_date_unique` (`schedule_id`, `date`);

-- OPSIONAL: tabel attendances dari rancangan awal sudah tidak dipakai (diganti presensi_header/detail).
-- DROP TABLE `attendances`;
