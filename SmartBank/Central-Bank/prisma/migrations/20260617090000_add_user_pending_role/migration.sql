-- AlterTable
ALTER TABLE `users`
  ADD COLUMN `pending_role` VARCHAR(32) NULL,
  ADD COLUMN `pending_role_requested_at` DATETIME(3) NULL;
