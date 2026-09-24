-- Amazing Feet — Returns/exchanges, suppliers, PO supplier_id
-- Run once: mysql -u root amazingfeet < config/migrations/007_returns_suppliers.sql

CREATE TABLE IF NOT EXISTS suppliers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    phone      VARCHAR(30) NULL,
    email      VARCHAR(120) NULL,
    notes      TEXT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_suppliers_name (name),
    INDEX idx_suppliers_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link POs to suppliers (keep free-text supplier for display/search)
ALTER TABLE purchase_orders
    ADD COLUMN supplier_id INT NULL AFTER supplier,
    ADD INDEX idx_po_supplier (supplier_id),
    ADD CONSTRAINT fk_po_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS sale_returns (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    return_ref      VARCHAR(30) NOT NULL UNIQUE,
    sale_id         INT NOT NULL,
    processed_by    INT NOT NULL,
    reason          VARCHAR(255) NULL,
    refund_method   ENUM('cash','momo','card','store_credit','none') NOT NULL DEFAULT 'cash',
    refund_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    exchange_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    exchange_sale_id INT NULL,
    notes           TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (processed_by) REFERENCES users(id),
    FOREIGN KEY (exchange_sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    INDEX idx_ret_sale (sale_id),
    INDEX idx_ret_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sale_return_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    return_id     INT NOT NULL,
    sale_item_id  INT NOT NULL,
    product_id    INT NOT NULL,
    quantity      INT NOT NULL DEFAULT 1,
    unit_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
    line_total    DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (return_id) REFERENCES sale_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_item_id) REFERENCES sale_items(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_sri_return (return_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sale_return_exchanges (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    return_id   INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
    line_total  DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (return_id) REFERENCES sale_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_sre_return (return_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
