-- =====================================================
-- Database Cleanup Script for NeoCafe
-- =====================================================
-- Purpose: Reset products and orders data for fresh testing
-- Usage: Execute this script via phpMyAdmin or MySQL command line
-- Warning: This will permanently delete all data in the specified tables
-- =====================================================

-- Disable foreign key checks temporarily to avoid constraint errors
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- DELETE DATA (Using DELETE instead of TRUNCATE for FK safety)
-- =====================================================
-- Note: Using DELETE to handle foreign key constraints properly

-- Step 1: Clear refund vouchers (references order_refunds)
DELETE FROM refund_vouchers;

-- Step 2: Clear order refunds (references orders)
DELETE FROM order_refunds;

-- Step 3: Clear availtoday cart items (references products)
DELETE FROM availtoday_cart;

-- Step 4: Clear regular cart items (references products)
DELETE FROM cart;

-- Step 5: Clear order items (references both orders and products)
DELETE FROM order_items;

-- Step 6: Clear product images
DELETE FROM product_images;

-- Step 7: Clear user blog posts
DELETE FROM user_blog_post;

-- Step 8: Clear orders
DELETE FROM orders;

-- Step 9: Clear products
DELETE FROM products;

-- =====================================================
-- RESET AUTO_INCREMENT (Start IDs from 1)
-- =====================================================

-- Reset products table auto-increment
ALTER TABLE products AUTO_INCREMENT = 1;

-- Reset orders table auto-increment
ALTER TABLE orders AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFICATION QUERIES (Optional - Run to verify cleanup)
-- =====================================================
-- SELECT COUNT(*) AS products_count FROM products;
-- SELECT COUNT(*) AS orders_count FROM orders;
-- SELECT COUNT(*) AS product_images_count FROM product_images;
-- SELECT COUNT(*) AS order_refunds_count FROM order_refunds;
-- SELECT COUNT(*) AS refund_vouchers_count FROM refund_vouchers;
-- SELECT COUNT(*) AS availtoday_cart_count FROM availtoday_cart;
-- SELECT COUNT(*) AS cart_count FROM cart;
-- SELECT COUNT(*) AS order_items_count FROM order_items;
-- SELECT COUNT(*) AS user_blog_post_count FROM user_blog_post;

-- =====================================================
-- Script completed successfully
-- All specified tables have been truncated and auto-increment values reset
-- =====================================================
