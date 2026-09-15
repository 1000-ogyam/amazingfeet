-- Amazing Feet POS — Database Schema
CREATE DATABASE IF NOT EXISTS amazingfeet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE amazingfeet;

-- ─── Users ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) UNIQUE NOT NULL,
    phone      VARCHAR(20) NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('owner','staff') NOT NULL DEFAULT 'staff',
    pin        VARCHAR(10) NULL COMMENT 'Quick 4-digit POS PIN',
    is_active  TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── Categories ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    slug       VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0
);

-- ─── Products (each size/design = one SKU row) ───────────────
CREATE TABLE IF NOT EXISTS products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    category_id     INT NOT NULL,
    name            VARCHAR(150) NOT NULL,
    gender          ENUM('Boys','Girls','Unisex','Ladies') NOT NULL,
    design          VARCHAR(100) NULL COMMENT 'Style/design name',
    size            VARCHAR(10) NOT NULL COMMENT 'Numeric or text size',
    barcode         VARCHAR(100) UNIQUE NULL,
    cost_price      DECIMAL(10,2) NOT NULL DEFAULT 0,
    selling_price   DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantity        INT NOT NULL DEFAULT 0,
    low_stock_threshold INT DEFAULT 3,
    image           VARCHAR(255) NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- ─── Sale locations ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS locations (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    type       ENUM('shop','school','other') DEFAULT 'shop',
    is_active  TINYINT(1) DEFAULT 1
);

-- ─── Sales (header) ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sales (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sale_ref        VARCHAR(20) UNIQUE NOT NULL,
    staff_id        INT NOT NULL,
    location_id     INT NOT NULL,
    customer_id     INT NULL,
    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method  ENUM('cash','momo','card','split') NOT NULL DEFAULT 'cash',
    amount_tendered DECIMAL(10,2) NULL,
    change_due      DECIMAL(10,2) NULL,
    momo_ref        VARCHAR(100) NULL,
    notes           TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id)    REFERENCES users(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- ─── Sale line items ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sale_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    sale_id       INT NOT NULL,
    product_id    INT NOT NULL,
    quantity      INT NOT NULL DEFAULT 1,
    unit_price    DECIMAL(10,2) NOT NULL,
    cost_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
    line_total    DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id)    REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ─── Stock adjustments (manual additions / corrections) ──────
CREATE TABLE IF NOT EXISTS stock_adjustments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id    INT NOT NULL,
    type       ENUM('addition','correction','return','damaged') NOT NULL,
    quantity   INT NOT NULL COMMENT 'Positive = add, Negative = remove',
    note       TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id)    REFERENCES users(id)
);

-- ─── Weekly targets ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS weekly_targets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL COMMENT 'NULL = overall target',
    week_start  DATE NOT NULL,
    week_end    DATE NOT NULL,
    target_units INT NOT NULL DEFAULT 0,
    target_revenue DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_by  INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)  REFERENCES users(id)
);

-- ─── Customers ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(20) NULL,
    shoe_size  VARCHAR(10) NULL,
    notes      TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── Low stock alerts log ────────────────────────────────────
CREATE TABLE IF NOT EXISTS stock_alerts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity   INT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ═══════════════════════════════════════════════════════════
-- SEED DATA
-- ═══════════════════════════════════════════════════════════

-- Categories
INSERT INTO categories (name, slug, sort_order) VALUES
('School Shoes',          'school-shoes',  1),
('Exclusive Ladies Shoes','ladies-shoes',  2),
('Pre-Loved Shoes',       'pre-loved',     3);

-- Locations
INSERT INTO locations (name, type) VALUES
('Shop',       'shop'),
('School A',   'school'),
('School B',   'school');

-- Owner account  (password: owner123)
INSERT INTO users (name, email, phone, password, role, pin) VALUES
('Ama Peprah', 'ama@amazingfeet.com', '+233200000000',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'owner', '1234');

-- Staff account  (password: staff123)
INSERT INTO users (name, email, phone, password, role, pin) VALUES
('Shop Staff', 'staff@amazingfeet.com', '+233201234567',
 '$2y$10$TKh8H1.LeofqeuaUiqhNUujPt2NiSnq7kHhc8MbqHPnKNlhKi5HiW',
 'staff', '5678');

-- Sample products
INSERT INTO products (category_id, name, gender, design, size, cost_price, selling_price, quantity, barcode) VALUES
(1, 'Amazing Feet School Shoe', 'Boys',  'Classic Black', '30', 45.00, 90.00,  12, 'AF-B-CB-30'),
(1, 'Amazing Feet School Shoe', 'Boys',  'Classic Black', '31', 45.00, 90.00,  10, 'AF-B-CB-31'),
(1, 'Amazing Feet School Shoe', 'Boys',  'Classic Black', '32', 45.00, 90.00,   8, 'AF-B-CB-32'),
(1, 'Amazing Feet School Shoe', 'Boys',  'Classic Black', '33', 45.00, 90.00,   5, 'AF-B-CB-33'),
(1, 'Amazing Feet School Shoe', 'Boys',  'Classic Black', '34', 45.00, 90.00,   3, 'AF-B-CB-34'),
(1, 'Amazing Feet School Shoe', 'Girls', 'Classic Black', '30', 45.00, 90.00,  15, 'AF-G-CB-30'),
(1, 'Amazing Feet School Shoe', 'Girls', 'Classic Black', '31', 45.00, 90.00,  11, 'AF-G-CB-31'),
(1, 'Amazing Feet School Shoe', 'Girls', 'Classic Black', '32', 45.00, 90.00,   7, 'AF-G-CB-32'),
(1, 'Amazing Feet School Shoe', 'Girls', 'Patent Finish', '30', 48.00, 95.00,   9, 'AF-G-PF-30'),
(1, 'Amazing Feet School Shoe', 'Girls', 'Patent Finish', '31', 48.00, 95.00,   6, 'AF-G-PF-31'),
(2, 'Exclusive Heel',           'Ladies','Stiletto',      '37', 80.00, 180.00,  4, 'AF-L-ST-37'),
(2, 'Exclusive Heel',           'Ladies','Stiletto',      '38', 80.00, 180.00,  3, 'AF-L-ST-38'),
(2, 'Exclusive Flat',           'Ladies','Ballerina',     '37', 60.00, 130.00,  6, 'AF-L-BL-37'),
(3, 'Pre-Loved Sneakers',       'Ladies','Mixed',         '38', 15.00,  55.00,  8, 'AF-PL-SN-38'),
(3, 'Pre-Loved Sandals',        'Ladies','Mixed',         '37', 12.00,  45.00,  5, 'AF-PL-SD-37');
