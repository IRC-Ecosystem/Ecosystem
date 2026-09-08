-- AlterTable: tambah kolom rekomendasi Teller di Loan
-- Alur: Teller screening pinjaman kecil (≤ 50.000) → set recommendedBy
-- → Manager final-approve via endpoint existing (settleLoanApproval).
-- Status enum TIDAK berubah; rekomendasi = PENDING + recommendedBy != null.

ALTER TABLE `loans`
  ADD COLUMN `recommended_by` CHAR(36) NULL,
  ADD COLUMN `recommended_at` DATETIME(3) NULL,
  ADD COLUMN `recommendation_note` VARCHAR(512) NULL;

-- Index untuk filter Manager: PENDING + recommendedBy IS NULL vs NOT NULL
CREATE INDEX `loans_recommended_by_idx` ON `loans` (`recommended_by`);

-- Index untuk filter Manager: PENDING + status
-- (idx_loans_status sudah ada di schema tapi pastikan; aman di-redeclare)
CREATE INDEX `loans_status_idx` ON `loans` (`status`);
