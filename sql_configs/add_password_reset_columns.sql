-- Add password reset columns to users table if they don't exist

-- Check if reset_token_hash column exists, if not add it
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'reset_token_hash';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN reset_token_hash VARCHAR(64) DEFAULT NULL AFTER password',
    'SELECT "reset_token_hash column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if reset_token_expires_at column exists, if not add it
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'reset_token_expires_at';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN reset_token_expires_at DATETIME DEFAULT NULL AFTER reset_token_hash',
    'SELECT "reset_token_expires_at column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add unique index for reset_token_hash if it doesn't exist
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND INDEX_NAME = 'reset_token_hash';

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE users ADD UNIQUE KEY reset_token_hash (reset_token_hash)',
    'SELECT "reset_token_hash index already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
