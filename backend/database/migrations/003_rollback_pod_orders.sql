-- Rollback Migration: Remove pod_orders table
-- Purpose: Rollback proof of delivery feature
-- Date: 2025-11-02

-- Drop foreign key constraint first
ALTER TABLE `pod_orders` DROP FOREIGN KEY `fk_pod_order_id`;

-- Drop pod_orders table
DROP TABLE IF EXISTS `pod_orders`;
