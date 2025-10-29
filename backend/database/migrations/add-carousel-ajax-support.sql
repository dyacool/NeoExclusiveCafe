-- ============================================
-- Database Migration: Carousel AJAX Image Management Support
-- ============================================
-- This migration prepares the carousel_images table for AJAX-based image management
-- 
-- Changes:
-- 1. Adds Cloudinary-specific columns to carousel_images table
-- 2. Makes image_url column nullable
-- 3. Adds necessary indexes for performance
-- 4. Verifies temp_uploaded_images table exists (reused from product images)
-- ============================================

-- Step 1: Add Cloudinary columns to carousel_images table (if they don't exist)
-- Note: These columns may already exist in your schema
ALTER TABLE carousel_images 
ADD COLUMN IF NOT EXISTS cloud_public_id VARCHAR(255) NULL AFTER image_url,
ADD COLUMN IF NOT EXISTS cloud_provider VARCHAR(50) DEFAULT 'cloudinary' AFTER cloud_public_id,
ADD COLUMN IF NOT EXISTS cloud_url TEXT NULL AFTER cloud_provider;

-- Step 2: Make image_url nullable for Cloudinary-only storage
ALTER TABLE carousel_images 
MODIFY COLUMN image_url VARCHAR(255) NULL;

-- Step 3: Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_cloud_public_id ON carousel_images(cloud_public_id);
CREATE INDEX IF NOT EXISTS idx_display_order ON carousel_images(display_order);

-- Verification queries (run these to verify the migration)
-- DESCRIBE carousel_images;
-- SHOW INDEX FROM carousel_images;
-- SHOW TABLES LIKE 'temp_uploaded_images';
