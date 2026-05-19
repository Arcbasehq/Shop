-- Golders schema (MySQL 8 / MariaDB 10.5+)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    is_admin        TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug            VARCHAR(190) NOT NULL UNIQUE,
    name            VARCHAR(190) NOT NULL,
    description     TEXT NOT NULL,
    price_cents     INT UNSIGNED NOT NULL,
    currency        CHAR(3) NOT NULL DEFAULT 'USD',
    stock           INT NOT NULL DEFAULT 0,
    image_path      VARCHAR(255) DEFAULT NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED DEFAULT NULL,
    order_number        CHAR(26) NOT NULL UNIQUE,
    status              ENUM('new','awaiting_payment','paid','shipped','cancelled','expired') NOT NULL DEFAULT 'new',
    subtotal_cents      INT UNSIGNED NOT NULL,
    shipping_cents      INT UNSIGNED NOT NULL DEFAULT 0,
    total_cents         INT UNSIGNED NOT NULL,
    currency            CHAR(3) NOT NULL DEFAULT 'USD',
    email               VARCHAR(190) NOT NULL,
    ship_name           VARCHAR(190) NOT NULL,
    ship_address1       VARCHAR(255) NOT NULL,
    ship_address2       VARCHAR(255) DEFAULT NULL,
    ship_city           VARCHAR(120) NOT NULL,
    ship_state          VARCHAR(120) DEFAULT NULL,
    ship_postcode       VARCHAR(40)  NOT NULL,
    ship_country        CHAR(2) NOT NULL,
    coin                 VARCHAR(16)  DEFAULT NULL,
    deposit_address      VARCHAR(255) DEFAULT NULL,
    expected_coin_amount VARCHAR(64)  DEFAULT NULL,
    callback_token       CHAR(64)     DEFAULT NULL,
    paid_amount          VARCHAR(64)  DEFAULT NULL,
    paid_tx              VARCHAR(190) DEFAULT NULL,
    confirmations        INT UNSIGNED NOT NULL DEFAULT 0,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (deposit_address),
    INDEX (callback_token),
    INDEX (user_id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NOT NULL,
    name_snapshot   VARCHAR(190) NOT NULL,
    price_cents     INT UNSIGNED NOT NULL,
    qty             INT UNSIGNED NOT NULL,
    INDEX (order_id),
    CONSTRAINT fk_items_order  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT fk_items_prod   FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip          VARBINARY(16) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    success     TINYINT(1) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (ip, created_at),
    INDEX (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
