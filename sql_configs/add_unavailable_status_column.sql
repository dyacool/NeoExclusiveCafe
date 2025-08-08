-- Add unavailable_status_id column to products table
-- This column will link products to the unavail_products_status table

ALTER TABLE products ADD COLUMN unavailable_status_id INT NULL AFTER status_id;

-- Add foreign key constraint
ALTER TABLE products ADD CONSTRAINT fk_products_unavailable_status 
FOREIGN KEY (unavailable_status_id) REFERENCES unavail_products_status(id) ON DELETE SET NULL;

-- Add index for better performance
CREATE INDEX idx_products_unavailable_status ON products(unavailable_status_id);

-- Add comment to document the column purpose
ALTER TABLE products MODIFY COLUMN unavailable_status_id INT NULL COMMENT 'References unavail_products_status table. NULL = available, NOT NULL = unavailable';
