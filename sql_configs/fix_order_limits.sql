-- Drop existing table to restructure
DROP TABLE IF EXISTS `date_limits`;

-- Create date_limits table
CREATE TABLE `date_limits` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` date NOT NULL,
    `limit_value` int(11) NOT NULL DEFAULT 10,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Drop existing table to restructure
DROP TABLE IF EXISTS `order_limits`;

-- Create order_limits table for default limit
CREATE TABLE `order_limits` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `default_limit` int(11) NOT NULL DEFAULT 10,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings row
INSERT INTO `order_limits` (`id`, `default_limit`) 
VALUES (1, 10)
ON DUPLICATE KEY UPDATE default_limit = VALUES(default_limit); 