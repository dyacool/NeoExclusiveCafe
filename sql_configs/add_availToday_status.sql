-- Add availToday_status column to products table
-- This column will track whether a product is available today (1) or not (0)
-- Only applies to Delivery and Pick Up products

ALTER TABLE products ADD COLUMN availToday_status TINYINT(1) DEFAULT 0 AFTER status_id;

-- Add index for better performance when filtering by availToday_status
CREATE INDEX idx_availToday_status ON products(availToday_status);

-- Update existing Delivery and Pick Up products to have availToday_status = 1 by default
-- (assuming they are available today when first added)
UPDATE products 
SET availToday_status = 1 
WHERE status_id IN (1, 2) AND deleted_at IS NULL;

-- Add comment to document the column purpose
ALTER TABLE products MODIFY COLUMN availToday_status TINYINT(1) DEFAULT 0 COMMENT '1 = Available today, 0 = Not available today (only applies to Delivery and Pick Up products)';
