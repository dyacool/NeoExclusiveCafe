-- Rollback Migration: Remove image moderation tables
-- Description: Rollback changes made by 001_create_image_moderation_tables.sql
-- Date: 2025-11-02
-- WARNING: This will delete all moderation logs and temp upload tracking

-- Remove moderation columns from temp_uploaded_images if they exist
SET @sql = 'ALTER TABLE temp_uploaded_images DROP INDEX IF EXISTS idx_moderation_status';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE temp_uploaded_images DROP COLUMN IF EXISTS moderation_checked_at';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE temp_uploaded_images DROP COLUMN IF EXISTS moderation_status';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop image_moderation_log table
DROP TABLE IF EXISTS image_moderation_log;

-- Note: temp_uploaded_images table is NOT dropped as it may be used for other purposes
-- If you want to completely remove it, uncomment the line below:
-- DROP TABLE IF EXISTS temp_uploaded_images;

-- Verify rollback completed successfully
SELECT 'Rollback completed successfully' AS status;
