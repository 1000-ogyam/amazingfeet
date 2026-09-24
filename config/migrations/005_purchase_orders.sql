-- Amazing Feet — Purchase Orders (receive stock)
-- Run once: mysql -u root amazingfeet < config/migrations/005_purchase_orders.sql

CREATE TABLE IF NOT EXISTS purchase_orders (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    po_ref       VARCHAR(30) NOT NULL UNIQUE,
    supplier     VARCHAR(150) NULL,
    status       ENUM('draft','ordered','partial','received','cancelled') NOT NULL DEFAULT 'draft',
    notes        TEXT NULL,
    created_by   INT NOT NULL,
    received_by  INT NULL,
    ordered_at   DATETIME NULL,
    received_at  DATETIME NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by)  REFERENCES users(id),
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_po_status (status),
    INDEX idx_po_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id   INT NOT NULL,
    product_id          INT NOT NULL,
    quantity_ordered    INT NOT NULL DEFAULT 0,
    quantity_received   INT NOT NULL DEFAULT 0,
    unit_cost           DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_poi_po (purchase_order_id),
    INDEX idx_poi_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
