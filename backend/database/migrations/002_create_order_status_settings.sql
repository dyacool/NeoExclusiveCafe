-- Migration: Create order_status_settings table and add indexes to orders table
-- Purpose: Enable automatic order status management with toggle preference storage
-- Date: 2025-11-02

-- Create order_status_settings table
CREATE TABLE IF NOT EXISTS `order_status_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) DEFAULT NULL COMMENT 'NULL for global setting, or specific admin user ID',
  `auto_status_enabled` TINYINT(1) DEFAULT 0 COMMENT '0 = manual, 1 = automatic',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Stores auto-status toggle preferences for order management';

-- Add indexes to orders table for performance optimization
-- Index for delivery_date lookups
CREATE INDEX IF NOT EXISTS `idx_delivery_date` ON `orders` (`delivery_date`);

-- Index for pickup_date lookups
CREATE INDEX IF NOT EXISTS `idx_pickup_date` ON `orders` (`pickup_date`);

-- Index for status filtering
CREATE INDEX IF NOT EXISTS `idx_status` ON `orders` (`status`);

-- Composite index for efficient auto-update queries
CREATE INDEX IF NOT EXISTS `idx_delivery_method_date_status` 
ON `orders` (`delivery_method`, `delivery_date`, `status`);

-- Composite index for pickup queries
CREATE INDEX IF NOT EXISTS `idx_delivery_method_pickup_status` 
ON `orders` (`delivery_method`, `pickup_date`, `status`);

-- Insert default global setting (disabled by default)
INSERT INTO `order_status_settings` (`admin_id`, `auto_status_enabled`) 
VALUES (NULL, 0)
ON DUPLICATE KEY UPDATE `auto_status_enabled` = `auto_status_enabled`;
