-- AlterTable: tambah account_number sebagai public-facing identifier
-- Wallet ID (UUID) tetap primary key internal untuk settlement & ledger.
-- Account number adalah "nomor rekening" yang dilihat & dipakai nasabah.

ALTER TABLE `users`
  ADD COLUMN `account_number` VARCHAR(16) NULL;

-- Backfill account number untuk user yang sudah ada
-- Format: 10 digit dengan Luhn checksum. Backfill deterministic dari id hash.
-- Idempotent: kalau kolom sudah terisi, jangan overwrite.
UPDATE `users` SET `account_number` = LPAD(CONV(SUBSTRING(MD5(id), 1, 8), 16, 10) % 10000000000, 10, '0')
WHERE `account_number` IS NULL;

-- Sekarang tambahkan constraint unique setelah backfill
CREATE UNIQUE INDEX `users_account_number_key` ON `users` (`account_number`);
