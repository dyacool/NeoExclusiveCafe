-- Add discount_amount and shipping_fee columns to orders table

-- Add discount_amount column
ALTER TABLE orders 
ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 
AFTER total_amount;

-- Add shipping_fee column
ALTER TABLE orders 
ADD COLUMN shipping_fee DECIMAL(10,2) DEFAULT 0.00 
AFTER discount_amount;
