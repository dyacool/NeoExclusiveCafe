-- Restructure product statuses
-- Remove unavailable statuses from product_statuses table
-- Create new unavail_products_status table

-- Step 1: Create the new unavail_products_status table
CREATE TABLE unavail_products_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Step 2: Insert the unavailable statuses into the new table
INSERT INTO unavail_products_status (name) VALUES 
('Unavailable Pick Up'),
('Unavailable Delivery'),
('Unavailable Today');

-- Step 3: Remove the unavailable statuses from product_statuses table
DELETE FROM product_statuses WHERE id IN (4, 5);

-- Step 4: Update any products that were using the removed statuses
-- Set them to a default status (Pick Up = 1) or you can choose another appropriate status
UPDATE products SET status_id = 1 WHERE status_id IN (4, 5);

-- Step 5: Add comment to document the table purpose
ALTER TABLE unavail_products_status COMMENT = 'Tracks different types of unavailable product statuses';
