-- Alasan penolakan izin siswa
-- Jalankan di phpMyAdmin pada database yang dipakai aplikasi (tab SQL).

ALTER TABLE `leave_requests`
  ADD COLUMN `rejection_reason` VARCHAR(500) NULL AFTER `verified_by`;
