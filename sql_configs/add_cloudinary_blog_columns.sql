-- Add Cloudinary columns to blog_posts table (admin blog)
ALTER TABLE `blog_posts` 
ADD COLUMN IF NOT EXISTS `cloud_url` VARCHAR(500) NULL AFTER `image_path`,
ADD COLUMN IF NOT EXISTS `cloud_public_id` VARCHAR(255) NULL AFTER `cloud_url`,
ADD COLUMN IF NOT EXISTS `cloud_provider` VARCHAR(50) DEFAULT 'cloudinary' AFTER `cloud_public_id`;

-- Add Cloudinary columns to user_blog_post table (user blog)
ALTER TABLE `user_blog_post` 
ADD COLUMN IF NOT EXISTS `cloud_url` VARCHAR(500) NULL AFTER `image_path`,
ADD COLUMN IF NOT EXISTS `cloud_public_id` VARCHAR(255) NULL AFTER `cloud_url`,
ADD COLUMN IF NOT EXISTS `cloud_provider` VARCHAR(50) DEFAULT 'cloudinary' AFTER `cloud_public_id`;

-- Add index on cloud_public_id for faster lookups
ALTER TABLE `blog_posts` 
ADD INDEX IF NOT EXISTS `idx_cloud_public_id` (`cloud_public_id`);

ALTER TABLE `user_blog_post` 
ADD INDEX IF NOT EXISTS `idx_cloud_public_id` (`cloud_public_id`);
