-- Chatbot OTP Table
CREATE TABLE IF NOT EXISTS `chatbot_otp` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) DEFAULT NULL,
  `admin_email` VARCHAR(255) NOT NULL,
  `otp_code` VARCHAR(6) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `is_used` TINYINT(1) DEFAULT 0,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_email` (`admin_email`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_otp_code` (`otp_code`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chatbot Access Tokens Table
CREATE TABLE IF NOT EXISTS `chatbot_access_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `revoked` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chatbot Database Settings Table
CREATE TABLE IF NOT EXISTS `chatbot_database_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `database_type` VARCHAR(50) DEFAULT 'MySQL',
  `source_name` VARCHAR(255) DEFAULT 'Primary Database',
  `connection_string` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `config_json` JSON DEFAULT NULL,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default database settings
INSERT INTO `chatbot_database_settings` 
(`database_type`, `source_name`, `status`, `updated_at`, `created_at`) 
VALUES 
('MySQL', 'Primary Database', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- Clean up expired OTPs (optional, can be run as a scheduled task)
DELETE FROM `chatbot_otp` WHERE `expires_at` < DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Clean up expired access tokens (optional, can be run as a scheduled task)
DELETE FROM `chatbot_access_tokens` WHERE `expires_at` < DATE_SUB(NOW(), INTERVAL 1 DAY);
