-- Jalankan di phpMyAdmin: pilih database db_skansaba_ukk, tab SQL, tempel, klik Kirim.
CREATE TABLE IF NOT EXISTS `permintaan_reset_password` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kontak` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Menunggu','Selesai','Ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `diproses_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
