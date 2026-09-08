-- AlterTable
-- Sync schema.prisma -> MySQL: add phone and pin_hash columns that were
-- declared in schema but never migrated (drift from prisma db push history).

ALTER TABLE `users`
  ADD COLUMN `phone` VARCHAR(50) NULL,
  ADD COLUMN `pin_hash` VARCHAR(191) NULL;

-- Unique index for phone (nullable, so multiple NULLs allowed in MySQL).
CREATE UNIQUE INDEX `users_phone_key` ON `users` (`phone`);
