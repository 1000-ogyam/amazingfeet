-- Amazing Feet — multi-size / per-size pricing support
-- Run once in phpMyAdmin (or MySQL CLI) on your production + local DB.
-- File: config/migrations/001_product_style_key.sql

-- 1) Add column (skip this statement if style_key already exists)
ALTER TABLE products
  ADD COLUMN style_key VARCHAR(64) NULL
    COMMENT 'Groups size variants of the same product style'
    AFTER id;

-- 2) Index for faster POS grouping
ALTER TABLE products
  ADD INDEX idx_products_style_key (style_key);

-- 3) Backfill existing products so matching styles share one key
UPDATE products
SET style_key = SHA1(CONCAT(
  category_id, '|',
  LOWER(TRIM(name)), '|',
  gender, '|',
  LOWER(TRIM(COALESCE(design, '')))
))
WHERE style_key IS NULL OR style_key = '';
