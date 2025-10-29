-- ============================================
-- Database Migration: Profile Picture AJAX Image Management Support
-- ============================================
-- This migration prepares the users table for AJAX-based profile picture management
-- 
-- Changes:
-- 1. Adds Cloudinary-specific columns to users table
-- 2. Adds necessary indexes for performance
-- 3. Verifies temp_uploaded_images table exists (reused from product/carousel images)
-- ============================================

-- Step 1: Add Cloudinary columns to users table (if they don't exist)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS cloud_url TEXT NULL AFTER profile_image,
ADD COLUMN IF NOT EXISTS cloud_public_id VARCHAR(255) NULL AFTER cloud_url,
ADD COLUMN IF NOT EXISTS cloud_provider VARCHAR(50) DEFAULT 'cloudinary' AFTER cloud_public_id;

-- Step 2: Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_cloud_public_id ON users(cloud_public_id);

-- Verification queries (run these to verify the migration)
-- DESCRIBE users;
-- SHOW INDEX FROM users;
-- SHOW TABLES LIKE 'temp_uploaded_images';
