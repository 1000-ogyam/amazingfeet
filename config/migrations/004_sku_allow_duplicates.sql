-- Allow the same SKU on multiple sizes (e.g. T266370 for all sizes).
-- Run once on production + local after 003_product_sku.sql

ALTER TABLE products DROP INDEX uq_products_sku;

ALTER TABLE products ADD INDEX idx_products_sku (sku);
