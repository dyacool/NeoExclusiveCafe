-- Create availToday_timer table
CREATE TABLE IF NOT EXISTS `availToday_timer` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `timer_value` TIME NOT NULL COMMENT 'The time value for the available today timer (e.g., 17:00:00)',
    `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Whether this timer is currently active',
    `description` VARCHAR(255) DEFAULT 'Available today timer' COMMENT 'Description of what this timer represents',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default value (5:00 PM)
INSERT INTO `availToday_timer` (`timer_value`, `description`) VALUES ('17:00:00', 'Default closing time for available today products');

-- Optional: Add index for better query performance
CREATE INDEX `idx_availToday_timer_active` ON `availToday_timer` (`is_active`);
CREATE INDEX `idx_availToday_timer_updated` ON `availToday_timer` (`updated_at`);
