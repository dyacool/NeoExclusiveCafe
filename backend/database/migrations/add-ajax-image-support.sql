-- ============================================
-- Database Migration: AJAX Image Management Support
-- ============================================
-- This migration prepares the database for AJAX-based image management
-- 
-- Changes:
-- 1. Makes image_url column nullable in product_images table
-- 2. Creates temp_uploaded_images table for orphan tracking
-- 3. Adds necessary indexes for performance
-- ============================================

-- Step 1: Make image_url nullable in product_images table
-- This allows images to be stored only in Cloudinary without local paths
ALTER TABLE product_images 
MODIFY COLUMN image_url VARCHAR(255) NULL;

-- Step 2: Create temp_uploaded_images table for orphan tracking
-- This table tracks images uploaded via AJAX that haven't been associated with a product yet
CREATE TABLE IF NOT EXISTS temp_uploaded_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    public_id VARCHAR(255) NOT NULL UNIQUE,
    cloud_url TEXT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uploaded_at (uploaded_at),
    INDEX idx_public_id (public_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verification queries (run these to verify the migration)
-- DESCRIBE product_images;
-- DESCRIBE temp_uploaded_images;
-- SHOW INDEX FROM temp_uploaded_images;
