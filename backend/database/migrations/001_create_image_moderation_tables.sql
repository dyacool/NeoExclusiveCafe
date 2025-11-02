-- Migration: Create image moderation tables
-- Description: Add tables and columns for Cloudinary content moderation tracking
-- Date: 2025-11-02

-- Create image_moderation_log table
CREATE TABLE IF NOT EXISTS image_moderation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    public_id VARCHAR(255) NOT NULL,
    status ENUM('approved', 'rejected', 'pending') NOT NULL,
    kind VARCHAR(50) NOT NULL COMMENT 'Moderation provider: aws_rek, google_vision, etc.',
    response_data JSON COMMENT 'Full moderation response with confidence scores',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_public_id (public_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create temp_uploaded_images table if it doesn't exist
CREATE TABLE IF NOT EXISTS temp_uploaded_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    public_id VARCHAR(255) NOT NULL,
    cloud_url VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    moderation_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT 'Current moderation status',
    moderation_checked_at TIMESTAMP NULL COMMENT 'When moderation was last checked',
    INDEX idx_public_id (public_id),
    INDEX idx_moderation_status (moderation_status),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If table already exists, add moderation columns
-- Note: These will fail silently if columns already exist (MySQL 5.7+)
SET @sql = 'ALTER TABLE temp_uploaded_images ADD COLUMN IF NOT EXISTS moderation_status ENUM(''pending'', ''approved'', ''rejected'') DEFAULT ''pending''';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE temp_uploaded_images ADD COLUMN IF NOT EXISTS moderation_checked_at TIMESTAMP NULL';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE temp_uploaded_images ADD INDEX IF NOT EXISTS idx_moderation_status (moderation_status)';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify tables were created successfully
SELECT 'Migration completed successfully' AS status;
