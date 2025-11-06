-- Create pending_payments table to handle session loss during PayMongo redirects
-- This table stores payment data as a backup when session cookies are lost

CREATE TABLE IF NOT EXISTS pending_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    payment_id VARCHAR(255) NOT NULL UNIQUE COMMENT 'PayMongo source_id or payment_intent_id',
    payment_type ENUM('source', 'payment_intent') NOT NULL COMMENT 'Type of PayMongo payment',
    order_type ENUM('regular', 'availtoday') NOT NULL COMMENT 'Order type',
    amount DECIMAL(10,2) NOT NULL COMMENT 'Payment amount in PHP',
    payment_method VARCHAR(50) NOT NULL COMMENT 'gcash, paymaya, or card',
    order_data TEXT NOT NULL COMMENT 'JSON encoded order data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 1 HOUR) COMMENT 'Auto-expire after 1 hour',
    
    INDEX idx_payment_id (payment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Backup storage for pending PayMongo payments to handle session loss';

-- Create cleanup event to automatically delete expired payments
CREATE EVENT IF NOT EXISTS cleanup_expired_pending_payments
ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM pending_payments WHERE expires_at < NOW();
