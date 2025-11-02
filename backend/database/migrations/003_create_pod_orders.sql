-- Migration: Create pod_orders table for proof of delivery
-- Purpose: Store photographic proof of delivery for completed orders
-- Date: 2025-11-02

-- Create pod_orders table
CREATE TABLE IF NOT EXISTS `pod_orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `proof_image_path` VARCHAR(255) NOT NULL COMMENT 'Relative path to proof image',
  `submitted_by` VARCHAR(100) NULL COMMENT 'Rider name or ID who submitted proof',
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image_size` INT(11) NULL COMMENT 'File size in bytes',
  `notes` TEXT NULL COMMENT 'Optional notes from rider',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order` (`order_id`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_pod_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Stores proof of delivery images for completed orders';
