-- Amazing Feet — add SKU code per product size
-- Run once on production + local MySQL

ALTER TABLE products
  ADD COLUMN sku VARCHAR(100) NULL
    COMMENT 'Internal SKU code for this size variant'
    AFTER size;

ALTER TABLE products
  ADD UNIQUE KEY uq_products_sku (sku);

-- Optional: copy existing barcodes into empty SKUs where helpful
UPDATE products
SET sku = barcode
WHERE (sku IS NULL OR sku = '')
  AND barcode IS NOT NULL
  AND barcode <> '';
