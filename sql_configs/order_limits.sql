-- Drop existing table to restructure
DROP TABLE IF EXISTS `order_limits`;

-- Create order_limits table
CREATE TABLE `order_limits` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` date DEFAULT NULL,
    `limit_count` int(11) DEFAULT NULL,
    `default_limit` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings row (with no date and no limit_count, only default_limit)
INSERT INTO `order_limits` (`id`, `date`, `limit_count`, `default_limit`) 
VALUES (1, NULL, NULL, 10);

-- Insert date-specific limits (with no default_limit)
INSERT INTO `order_limits` (`date`, `limit_count`, `default_limit`) 
VALUES 
    (DATE_ADD(CURDATE(), INTERVAL 1 DAY), 5, NULL),
    (DATE_ADD(CURDATE(), INTERVAL 2 DAY), 8, NULL)
ON DUPLICATE KEY UPDATE limit_count = VALUES(limit_count); 