/* =========================================================
   DROP & CREATE DATABASE
========================================================= */
DROP DATABASE IF EXISTS ecommerce;
CREATE DATABASE ecommerce;
USE ecommerce;

/* =========================================================
   USERS
========================================================= */
CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    PASSWORD VARCHAR(255),
    ROLE ENUM('admin','staff','customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=INNODB;

/* =========================================================
   CATEGORIES (SELF-REFERENCE)
========================================================= */
CREATE TABLE categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id)
        REFERENCES categories(category_id)
        ON DELETE SET NULL
) ENGINE=INNODB;

/* =========================================================
   PRODUCTS
========================================================= */
CREATE TABLE products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(150),
    sku VARCHAR(50) UNIQUE,
    DESCRIPTION TEXT,
    price DECIMAL(10,2),
    cost DECIMAL(10,2),
    stock INT DEFAULT 0,
    category_id INT UNSIGNED,
    image_url VARCHAR(255),
    STATUS ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(category_id)
        ON DELETE SET NULL
) ENGINE=INNODB;

/* =========================================================
   ORDERS
========================================================= */
CREATE TABLE orders (
    order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    order_type ENUM('pos','online') NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid','unpaid','refunded') DEFAULT 'unpaid',
    order_status ENUM('pending','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);

/* =========================================================
   ORDER ITEMS
========================================================= */
CREATE TABLE order_items (
    order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE RESTRICT
) ENGINE=INNODB;

/* =========================================================
   PAYMENTS
========================================================= */
CREATE TABLE payments (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    payment_method ENUM('cash','card','upi','paypal','bank'),
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE
) ENGINE=INNODB;

/* =========================================================
   INVENTORY LOGS
========================================================= */
CREATE TABLE inventory_logs (
    log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    change_qty INT NOT NULL,
    reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_inventory_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE
) ENGINE=INNODB;

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
    STATUS ENUM('pending','shipped','delivered') DEFAULT 'pending',

    CONSTRAINT fk_shipping_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE
) ENGINE=INNODB;

CREATE TABLE featured_items (
    featured_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    title VARCHAR(150),
    image_url VARCHAR(255) NOT NULL,
    POSITION INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE
) ENGINE=INNODB;



CREATE TABLE contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(100),
    email VARCHAR(100),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* =========================================================
   NOTIFICATIONS
========================================================= */
CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    TYPE ENUM('order','payment','inventory','shipping','system') DEFAULT 'system',
    reference_id INT UNSIGNED NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=INNODB;


