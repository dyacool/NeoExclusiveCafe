-- Create table for order update flags
CREATE TABLE IF NOT EXISTS order_update_flags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flag_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_flag_type_created (flag_type, created_at)
);
