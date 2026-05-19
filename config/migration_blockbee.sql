-- Run this on an existing DB that was created against the old BTCPay schema.
-- Fresh installs don't need it (schema.sql already has the new columns).
ALTER TABLE orders
    DROP COLUMN IF EXISTS btcpay_invoice_id,
    DROP COLUMN IF EXISTS paid_coin,
    ADD COLUMN coin                 VARCHAR(16)  DEFAULT NULL AFTER ship_country,
    ADD COLUMN deposit_address      VARCHAR(255) DEFAULT NULL AFTER coin,
    ADD COLUMN expected_coin_amount VARCHAR(64)  DEFAULT NULL AFTER deposit_address,
    ADD COLUMN callback_token       CHAR(64)     DEFAULT NULL AFTER expected_coin_amount,
    ADD COLUMN confirmations        INT UNSIGNED NOT NULL DEFAULT 0 AFTER paid_tx,
    MODIFY paid_amount VARCHAR(64) DEFAULT NULL,
    MODIFY paid_tx     VARCHAR(190) DEFAULT NULL;

ALTER TABLE orders
    ADD INDEX (deposit_address),
    ADD INDEX (callback_token);
