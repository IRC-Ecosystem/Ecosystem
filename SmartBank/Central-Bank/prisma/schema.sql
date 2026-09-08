-- CreateTable
CREATE TABLE `users` (
    `id` CHAR(36) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `password_hash` VARCHAR(191) NOT NULL,
    `role` ENUM('WALLET_USER', 'MERCHANT', 'CENTRAL_BANK_ADMIN', 'AUDITOR', 'SYSTEM_SERVICE', 'MANAGER', 'TELLER') NOT NULL DEFAULT 'WALLET_USER',
    `kyc_tier` ENUM('BASIC', 'VERIFIED') NOT NULL DEFAULT 'BASIC',
    `status` ENUM('ACTIVE', 'SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `phone` VARCHAR(50) NULL,
    `pin_hash` VARCHAR(191) NULL,
    `account_number` VARCHAR(16) NULL,
    `identity_document_type` VARCHAR(32) NULL,
    `identity_document_number` VARCHAR(64) NULL,
    `identity_document_name` VARCHAR(191) NULL,
    `identity_document_data_url` LONGTEXT NULL,
    `identity_document_uploaded_at` DATETIME(3) NULL,
    `pending_role` VARCHAR(32) NULL,
    `pending_role_requested_at` DATETIME(3) NULL,

    UNIQUE INDEX `users_email_key`(`email`),
    UNIQUE INDEX `users_phone_key`(`phone`),
    UNIQUE INDEX `users_account_number_key`(`account_number`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `wallet_accounts` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NULL,
    `account_code` VARCHAR(64) NULL,
    `account_type` ENUM('CENTRAL_RESERVE', 'ISSUANCE_ACCOUNT', 'USER_WALLET', 'MERCHANT_WALLET', 'FEE_BANK', 'FEE_GATEWAY', 'FEE_MARKETPLACE', 'FEE_POS', 'FEE_SUPPLIER', 'FEE_LOGISTICS', 'TAX_SINK', 'LOAN_POOL_ACCOUNT', 'BURN_OR_SINK_ACCOUNT', 'CLEARING_ACCOUNT') NOT NULL,
    `currency` VARCHAR(16) NOT NULL DEFAULT 'CBDC_IDR',
    `available_balance` BIGINT NOT NULL DEFAULT 0,
    `hold_balance` BIGINT NOT NULL DEFAULT 0,
    `status` ENUM('ACTIVE', 'FROZEN', 'CLOSED') NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `wallet_accounts_account_code_key`(`account_code`),
    INDEX `wallet_accounts_user_id_idx`(`user_id`),
    INDEX `wallet_accounts_account_code_idx`(`account_code`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `monetary_policy_events` (
    `id` CHAR(36) NOT NULL,
    `event_type` ENUM('INITIAL_SUPPLY', 'INITIAL_DISTRIBUTION', 'LOAN_POOL_FUNDING', 'STIMULUS', 'ISSUANCE', 'BURN', 'RESERVE_ADJUSTMENT') NOT NULL,
    `amount` BIGINT NOT NULL,
    `reason` VARCHAR(512) NOT NULL,
    `created_by` CHAR(36) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `transactions` (
    `id` CHAR(36) NOT NULL,
    `transaction_type` ENUM('LOAN_POOL_FUNDING', 'INITIAL_DISTRIBUTION', 'TOP_UP', 'WITHDRAWAL', 'TRANSFER', 'PAYMENT', 'LOAN_DISBURSEMENT', 'LOAN_REPAYMENT', 'REVERSAL', 'ISSUANCE', 'BURN') NOT NULL,
    `status` ENUM('CREATED', 'VALIDATED', 'AUTHORIZED', 'SETTLED', 'FAILED', 'REVERSED') NOT NULL,
    `source_app` VARCHAR(64) NOT NULL,
    `payer_wallet_id` CHAR(36) NULL,
    `payee_wallet_id` CHAR(36) NULL,
    `gross_amount` BIGINT NOT NULL,
    `total_debit` BIGINT NOT NULL,
    `fee_total` BIGINT NOT NULL DEFAULT 0,
    `tax_total` BIGINT NOT NULL DEFAULT 0,
    `idempotency_key` VARCHAR(191) NULL,
    `original_transaction_id` CHAR(36) NULL,
    `metadata` JSON NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `settled_at` DATETIME(3) NULL,

    INDEX `transactions_idempotency_key_idx`(`idempotency_key`),
    INDEX `transactions_payer_wallet_id_idx`(`payer_wallet_id`),
    INDEX `transactions_payee_wallet_id_idx`(`payee_wallet_id`),
    INDEX `transactions_created_at_idx`(`created_at`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `ledger_entries` (
    `id` CHAR(36) NOT NULL,
    `transaction_id` CHAR(36) NOT NULL,
    `entry_no` INTEGER NOT NULL,
    `account_id` CHAR(36) NOT NULL,
    `direction` ENUM('DEBIT', 'CREDIT') NOT NULL,
    `amount` BIGINT NOT NULL,
    `currency` VARCHAR(16) NOT NULL DEFAULT 'CBDC_IDR',
    `balance_after` BIGINT NULL,
    `description` VARCHAR(512) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `ledger_entries_transaction_id_idx`(`transaction_id`),
    INDEX `ledger_entries_account_id_idx`(`account_id`),
    INDEX `ledger_entries_created_at_idx`(`created_at`),
    UNIQUE INDEX `ledger_entries_transaction_id_entry_no_key`(`transaction_id`, `entry_no`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `payment_requests` (
    `id` CHAR(36) NOT NULL,
    `source_app` VARCHAR(64) NOT NULL,
    `payer_wallet_id` CHAR(36) NOT NULL,
    `payee_wallet_id` CHAR(36) NOT NULL,
    `gross_amount` BIGINT NOT NULL,
    `amount_due` BIGINT NOT NULL,
    `status` ENUM('PENDING', 'EXPIRED', 'PAID', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    `description` VARCHAR(512) NOT NULL,
    `metadata` JSON NULL,
    `expires_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `paid_transaction_id` CHAR(36) NULL,

    UNIQUE INDEX `payment_requests_paid_transaction_id_key`(`paid_transaction_id`),
    INDEX `payment_requests_payer_wallet_id_idx`(`payer_wallet_id`),
    INDEX `payment_requests_payee_wallet_id_idx`(`payee_wallet_id`),
    INDEX `payment_requests_status_idx`(`status`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `fee_rules` (
    `id` CHAR(36) NOT NULL,
    `source_app` VARCHAR(64) NOT NULL,
    `fee_type` ENUM('BANK', 'GATEWAY', 'MARKETPLACE', 'POS', 'SUPPLIER', 'LOGISTICS', 'TAX') NOT NULL,
    `bps` INTEGER NULL,
    `flat_amount` BIGINT NULL,
    `destination_account_id` CHAR(36) NOT NULL,
    `active` BOOLEAN NOT NULL DEFAULT true,

    INDEX `fee_rules_source_app_active_idx`(`source_app`, `active`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `loans` (
    `id` CHAR(36) NOT NULL,
    `borrower_wallet_id` CHAR(36) NOT NULL,
    `principal` BIGINT NOT NULL,
    `interest_amount` BIGINT NOT NULL,
    `total_due` BIGINT NOT NULL,
    `paid_amount` BIGINT NOT NULL DEFAULT 0,
    `status` ENUM('PENDING', 'DISBURSED', 'PARTIAL_PAID', 'PAID', 'DEFAULTED', 'REJECTED') NOT NULL DEFAULT 'DISBURSED',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `disbursed_at` DATETIME(3) NULL,
    `due_at` DATETIME(3) NULL,
    `recommended_by` CHAR(36) NULL,
    `recommended_at` DATETIME(3) NULL,
    `recommendation_note` VARCHAR(512) NULL,

    INDEX `loans_borrower_wallet_id_idx`(`borrower_wallet_id`),
    INDEX `loans_status_idx`(`status`),
    INDEX `loans_recommended_by_idx`(`recommended_by`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
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

-- CreateTable
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
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `audit_logs` (
    `id` CHAR(36) NOT NULL,
    `actor_user_id` CHAR(36) NULL,
    `service_name` VARCHAR(64) NOT NULL,
    `action` VARCHAR(128) NOT NULL,
    `target_type` VARCHAR(64) NOT NULL,
    `target_id` VARCHAR(191) NOT NULL,
    `request_id` VARCHAR(191) NOT NULL,
    `reason_code` VARCHAR(128) NULL,
    `metadata` JSON NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `audit_logs_target_type_target_id_idx`(`target_type`, `target_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `idempotency_keys` (
    `id` CHAR(36) NOT NULL,
    `idempotency_key` VARCHAR(191) NOT NULL,
    `route` VARCHAR(191) NOT NULL,
    `actor_id` VARCHAR(191) NULL,
    `request_hash` VARCHAR(128) NOT NULL,
    `response_body` JSON NULL,
    `status` ENUM('PROCESSING', 'COMPLETED', 'FAILED') NOT NULL DEFAULT 'PROCESSING',
    `locked_until` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `idempotency_keys_idempotency_key_route_actor_id_key`(`idempotency_key`, `route`, `actor_id`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AddForeignKey
ALTER TABLE `wallet_accounts` ADD CONSTRAINT `wallet_accounts_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `transactions` ADD CONSTRAINT `transactions_payer_wallet_id_fkey` FOREIGN KEY (`payer_wallet_id`) REFERENCES `wallet_accounts`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `transactions` ADD CONSTRAINT `transactions_payee_wallet_id_fkey` FOREIGN KEY (`payee_wallet_id`) REFERENCES `wallet_accounts`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `transactions` ADD CONSTRAINT `transactions_original_transaction_id_fkey` FOREIGN KEY (`original_transaction_id`) REFERENCES `transactions`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `ledger_entries` ADD CONSTRAINT `ledger_entries_transaction_id_fkey` FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `ledger_entries` ADD CONSTRAINT `ledger_entries_account_id_fkey` FOREIGN KEY (`account_id`) REFERENCES `wallet_accounts`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `payment_requests` ADD CONSTRAINT `payment_requests_payer_wallet_id_fkey` FOREIGN KEY (`payer_wallet_id`) REFERENCES `wallet_accounts`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `payment_requests` ADD CONSTRAINT `payment_requests_payee_wallet_id_fkey` FOREIGN KEY (`payee_wallet_id`) REFERENCES `wallet_accounts`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `payment_requests` ADD CONSTRAINT `payment_requests_paid_transaction_id_fkey` FOREIGN KEY (`paid_transaction_id`) REFERENCES `transactions`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `loans` ADD CONSTRAINT `loans_borrower_wallet_id_fkey` FOREIGN KEY (`borrower_wallet_id`) REFERENCES `wallet_accounts`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `notifications` ADD CONSTRAINT `notifications_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

