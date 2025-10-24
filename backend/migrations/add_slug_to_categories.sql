-- Migration: Add slug column to categories table
-- Date: 2025-10-23

-- Step 1: Add slug column (allow NULL temporarily)
ALTER TABLE `categories` 
ADD COLUMN `slug` VARCHAR(255) NULL AFTER `name`;

-- Step 2: Generate slugs for existing categories
UPDATE `categories` SET `slug` = 'pastries' WHERE `id` = 2;
UPDATE `categories` SET `slug` = 'bread' WHERE `id` = 4;
UPDATE `categories` SET `slug` = 'cakes' WHERE `id` = 5;

-- Step 3: For any remaining categories without slugs, generate from name
UPDATE `categories` 
SET `slug` = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(name, ' ', '-'), '&', 'and'), '''', ''), ',', '')) 
WHERE `slug` IS NULL OR `slug` = '';

-- Step 4: Now make slug NOT NULL and add unique constraint
ALTER TABLE `categories` 
MODIFY COLUMN `slug` VARCHAR(255) NOT NULL,
ADD UNIQUE KEY `unique_slug` (`slug`);

-- Verify the changes
SELECT id, name, slug, is_active, display_order FROM categories ORDER BY display_order ASC;
