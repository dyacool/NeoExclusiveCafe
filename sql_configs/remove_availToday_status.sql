-- Remove availToday_status column from products table
-- This script undoes the changes made by add_availToday_status.sql

-- Drop the index first
DROP INDEX idx_availToday_status ON products;

-- Remove the column
ALTER TABLE products DROP COLUMN availToday_status;
