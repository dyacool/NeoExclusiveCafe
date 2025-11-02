-- Migration: Add Cloudinary support to pod_orders table
-- Purpose: Store Cloudinary public_id for delivery proof images
-- Date: 2025-11-02

-- Add cloudinary_public_id column
ALTER TABLE `pod_orders` 
ADD COLUMN `cloudinary_public_id` VARCHAR(255) NULL COMMENT 'Cloudinary public ID for the proof image' AFTER `proof_image_path`;

-- Add index for cloudinary_public_id
ALTER TABLE `pod_orders` 
ADD INDEX `idx_cloudinary_public_id` (`cloudinary_public_id`);

-- Update proof_image_path comment to indicate it now stores Cloudinary URL
ALTER TABLE `pod_orders` 
MODIFY COLUMN `proof_image_path` VARCHAR(500) NOT NULL COMMENT 'Cloudinary URL or relative path to proof image';
