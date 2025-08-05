-- Update product statuses to reflect delivery and pickup options
-- This script updates the product_statuses table and related product references

-- First, let's see what statuses currently exist
SELECT * FROM product_statuses;

-- Update the status names
UPDATE product_statuses SET name = 'Delivery' WHERE name = 'Bread of the Week';
UPDATE product_statuses SET name = 'Pickup' WHERE name = 'Available';

-- Verify the changes
SELECT * FROM product_statuses;

-- Note: The status_id values remain the same:
-- status_id = 1: Delivery (formerly Bread of the Week)
-- status_id = 2: Pickup (formerly Available)  
-- status_id = 3: Unavailable (unchanged) 