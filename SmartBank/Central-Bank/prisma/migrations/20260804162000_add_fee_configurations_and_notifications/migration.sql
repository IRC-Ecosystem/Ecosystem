CREATE TABLE `fee_configurations` (
    `id` CHAR(36) NOT NULL,
    `type` ENUM('LOAN_POOL_FUNDING', 'INITIAL_DISTRIBUTION', 'TOP_UP', 'WITHDRAWAL', 'TRANSFER', 'PAYMENT', 'LOAN_DISBURSEMENT', 'LOAN_REPAYMENT', 'REVERSAL', 'ISSUANCE', 'BURN') NOT NULL,
    `mode` ENUM('FLAT', 'PERCENT') NOT NULL,
    `value` BIGINT NOT NULL,
    `min_fee` BIGINT NULL,
    `max_fee` BIGINT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT true,
    `updated_by` CHAR(36) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    UNIQUE INDEX `fee_configurations_type_key`(`type`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `type` ENUM('OTP_LINKING_REQUESTED', 'OTP_VERIFIED', 'OTP_EXPIRED', 'OTP_BLOCKED', 'PAYMENT_SETTLED', 'PAYMENT_FAILED', 'WALLET_LINKED', 'WALLET_UNLINKED') NOT NULL,
    `channel` ENUM('IN_APP', 'SMS', 'EMAIL', 'PUSH') NOT NULL DEFAULT 'IN_APP',
    `source_app` VARCHAR(64) NULL,
    `source_ref` VARCHAR(191) NULL,
    `title` VARCHAR(191) NOT NULL,
    `body` VARCHAR(1000) NOT NULL,
    `payload` JSON NULL,
    `read_at` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    INDEX `notifications_user_id_read_at_idx`(`user_id`, `read_at`),
    INDEX `notifications_user_id_created_at_idx`(`user_id`, `created_at`),
    INDEX `notifications_type_created_at_idx`(`type`, `created_at`),
    PRIMARY KEY (`id`),
    CONSTRAINT `notifications_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
