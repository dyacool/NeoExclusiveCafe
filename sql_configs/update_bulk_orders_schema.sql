-- Update bulk_orders table to include new fields and status options
-- Run this script to update existing bulk_orders table

-- Add new columns if they don't exist
ALTER TABLE `bulk_orders` 
ADD COLUMN IF NOT EXISTS `notes` text DEFAULT NULL AFTER `customer_email`,
ADD COLUMN IF NOT EXISTS `admin_notes` text DEFAULT NULL AFTER `admin_updated`;

-- Update the status enum to include new options
ALTER TABLE `bulk_orders` 
MODIFY COLUMN `status` enum('pending','approved','payment_received','ready_for_delivery','cancelled','completed') NOT NULL DEFAULT 'pending';

-- Update any existing 'completed' status to new naming convention if needed
-- (Optional - only if you want to maintain backward compatibility)
