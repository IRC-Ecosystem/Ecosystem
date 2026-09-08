ALTER TABLE stores
    ADD COLUMN smartbank_external_id VARCHAR(191) NULL UNIQUE AFTER status;

UPDATE stores
SET smartbank_external_id = CONCAT('marketplace-store-', id)
WHERE smartbank_external_id IS NULL;

ALTER TABLE orders
    ADD COLUMN store_id INT NULL AFTER user_id,
    ADD COLUMN seller_external_id VARCHAR(191) NULL AFTER store_id,
    ADD CONSTRAINT fk_order_store FOREIGN KEY (store_id) REFERENCES stores(id);

UPDATE orders o
JOIN order_items oi ON oi.order_id = o.id
SET o.store_id = oi.store_id,
    o.seller_external_id = CONCAT('marketplace-store-', oi.store_id)
WHERE o.store_id IS NULL;
