-- Rollback Migration: Remove order_status_settings table and indexes
-- Purpose: Rollback automatic order status management feature
-- Date: 2025-11-02

-- Drop indexes from orders table
DROP INDEX IF EXISTS `idx_delivery_date` ON `orders`;
DROP INDEX IF EXISTS `idx_pickup_date` ON `orders`;
DROP INDEX IF EXISTS `idx_status` ON `orders`;
DROP INDEX IF EXISTS `idx_delivery_method_date_status` ON `orders`;
DROP INDEX IF EXISTS `idx_delivery_method_pickup_status` ON `orders`;

-- Drop order_status_settings table
DROP TABLE IF EXISTS `order_status_settings`;
