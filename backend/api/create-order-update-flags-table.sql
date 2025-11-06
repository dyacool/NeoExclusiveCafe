-- Create order_update_flags table for real-time order notifications
-- This table stores temporary flags to notify polling systems of new orders

CREATE TABLE IF NOT EXISTS order_update_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flag_type VARCHAR(50) NOT NULL COMMENT 'Type of flag: new_order, order_updated, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the flag was created',
    
    INDEX idx_flag_type (flag_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Temporary flags for real-time order notifications via polling';

-- Create cleanup event to automatically delete old flags (older than 1 minute)
CREATE EVENT IF NOT EXISTS cleanup_old_order_flags
ON SCHEDULE EVERY 30 SECOND
DO
  DELETE FROM order_update_flags WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE);
