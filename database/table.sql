/* =========================================================
 DATABASE
 ========================================================= */
CREATE DATABASE ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ecommerce;

/* =========================================================
 USERS
 ========================================================= */
CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    PASSWORD VARCHAR(255) NOT NULL,
    ROLE ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer',
    STATUS ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_role (ROLE),
    INDEX idx_status (STATUS),
    INDEX idx_email (email)
) ENGINE = INNODB;

ALTER TABLE
    users
ADD
    auth_token VARCHAR(128) NULL;

ADD
    google_id VARCHAR(255) NULL,
ADD
    provider ENUM('local', 'google') DEFAULT 'local';

ALTER TABLE
    users
MODIFY
    PASSWORD VARCHAR(255) NULL;

CREATE TABLE password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_pr_user_id (user_id),
    UNIQUE INDEX uq_pr_token_hash (token_hash),
    INDEX idx_pr_expires (expires_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE = INNODB DEFAULT CHARSET = utf8mb4;

CREATE TABLE email_verifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ev_token_hash (token_hash),
    KEY idx_ev_user_id (user_id),
    KEY idx_ev_expires_at (expires_at),
    CONSTRAINT fk_email_verifications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE = INNODB DEFAULT CHARSET = utf8mb4;

/* =========================================================
 CATEGORIES (SELF REFERENCE)
 ========================================================= */
CREATE TABLE categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    parent_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id),
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE
    SET
        NULL
) ENGINE = INNODB;

/* =========================================================
 PRODUCTS
 ========================================================= */
CREATE TABLE products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(150) NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    DESCRIPTION TEXT,
    price DECIMAL(10, 2) NOT NULL,
    cost DECIMAL(10, 2) DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    category_id INT UNSIGNED NULL,
    image_url VARCHAR(255),
    STATUS ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category_id),
    INDEX idx_status (STATUS),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE
    SET
        NULL
) ENGINE = INNODB;

ALTER TABLE
    products
ADD
    STATUS VARCHAR(20) DEFAULT 'active';

/* =========================================================
 ORDERS
 ========================================================= */
CREATE TABLE orders (
    order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    order_type ENUM('pos', 'online') NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('paid', 'unpaid', 'refunded') NOT NULL DEFAULT 'unpaid',
    order_status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE
    SET
        NULL
) ENGINE = INNODB;

/* =========================================================
 ORDER ITEMS
 ========================================================= */
CREATE TABLE order_items (
    order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
) ENGINE = INNODB;

/* =========================================================
 PAYMENTS
 ========================================================= */
CREATE TABLE payment_methods (
    method_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    method_code VARCHAR(30) NOT NULL UNIQUE,
    method_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = INNODB;

CREATE TABLE payments (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    payment_method_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id),
    INDEX idx_method (payment_method_id),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(method_id)
) ENGINE = INNODB;

INSERT INTO
    payment_methods (method_code, method_name)
VALUES
    ('aba', 'ABA Bank'),
    ('acleda', 'ACLEDA Bank'),
    ('wing', 'Wing'),
    ('chipmong', 'Chip Mong Bank'),
    ('bakong', 'Bakong (NBC)');

/* =========================================================
 INVENTORY LOGS
 ========================================================= */
CREATE TABLE inventory_logs (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    change_qty INT NOT NULL,
    reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE = INNODB;

/* =========================================================
 SHIPPING
 ========================================================= */
CREATE TABLE shipping (
    shipping_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    STATUS ENUM('pending', 'shipped', 'delivered') NOT NULL DEFAULT 'pending',
    INDEX idx_order (order_id),
    CONSTRAINT fk_shipping_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE = INNODB;

/* =========================================================
 NOTIFICATIONS
 ========================================================= */
CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    TYPE ENUM(
        'order',
        'payment',
        'inventory',
        'shipping',
        'system'
    ) NOT NULL DEFAULT 'system',
    reference_id INT UNSIGNED NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE = INNODB;

DROP TABLE notification_reads;

/* =========================================================
 FEATURED ITEMS
 ========================================================= */
CREATE TABLE featured_items (
    featured_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    title VARCHAR(150),
    image_url VARCHAR(255) NOT NULL,
    POSITION INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    CONSTRAINT fk_featured_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE = INNODB;

/* =========================================================
 CONTACT MESSAGES
 ========================================================= */
CREATE TABLE contact_messages (
    message_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = INNODB;

ALTER TABLE
    contact_messages
ADD
    COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE
    contact_messages
ADD
    COLUMN read_at TIMESTAMP NULL DEFAULT NULL;