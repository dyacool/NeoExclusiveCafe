-- Truncate AvailToday Cart Table
-- This will DELETE ALL items from the availtoday_cart table
-- Use this to manually clear the same-day order cart

-- WARNING: This will permanently delete all cart items!
-- Make sure you want to do this before running.

TRUNCATE TABLE availtoday_cart;

-- Verify the truncation (should return 0)
SELECT COUNT(*) as remaining_items FROM availtoday_cart;

-- Expected result: remaining_items = 0

