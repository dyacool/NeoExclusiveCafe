-- Add availtoday_status_id column to products table
-- This column will store the ID reference to the availtoday_status table

-- First, create the availtoday_status table
CREATE TABLE IF NOT EXISTS availtoday_status (
    id INT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Insert the default values
INSERT INTO availtoday_status (id, name) VALUES 
(1, 'Pick Up'),
(2, 'Delivery')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Add the availtoday_status_id column to the products table
ALTER TABLE products ADD COLUMN availtoday_status_id INT NULL AFTER status_id;

-- Add foreign key constraint
ALTER TABLE products ADD CONSTRAINT fk_products_availtoday_status 
FOREIGN KEY (availtoday_status_id) REFERENCES availtoday_status(id);

-- Add index for better performance
CREATE INDEX idx_products_availtoday_status ON products(availtoday_status_id);

-- Add comment to document the column purpose
ALTER TABLE products MODIFY COLUMN availtoday_status_id INT NULL COMMENT 'Reference to availtoday_status table for Available Today products (1=Pick Up, 2=Delivery)';
