-- Amazing Feet — add SKU code per product (may be shared across sizes)
-- Run once on production + local MySQL

ALTER TABLE products
  ADD COLUMN sku VARCHAR(100) NULL
    COMMENT 'Style/SKU code (may be shared across sizes)'
    AFTER size;

ALTER TABLE products
  ADD INDEX idx_products_sku (sku);

-- Optional: copy existing barcodes into empty SKUs where helpful
UPDATE products
SET sku = barcode
WHERE (sku IS NULL OR sku = '')
  AND barcode IS NOT NULL
  AND barcode <> '';
