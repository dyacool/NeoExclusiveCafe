-- Rollback script for product statuses restructuring
-- This script restores the original product_statuses table structure

-- Step 1: Drop the new unavail_products_status table
DROP TABLE IF EXISTS unavail_products_status;

-- Step 2: Re-insert the removed statuses back into product_statuses
INSERT INTO product_statuses (id, name) VALUES 
(4, 'Unavailable Pick Up'),
(5, 'Unavailable Delivery');

-- Step 3: Update any products that were changed back to their original status
-- Note: This assumes products were set to status_id = 1 (Pick Up) during restructuring
-- You may need to manually review and update these based on your specific needs
-- UPDATE products SET status_id = 4 WHERE /* your specific condition */;
-- UPDATE products SET status_id = 5 WHERE /* your specific condition */;
