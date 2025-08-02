-- Create table for default order limit
CREATE TABLE IF NOT EXISTS order_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    default_limit INT NOT NULL DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create table for date-specific limits
CREATE TABLE IF NOT EXISTS date_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    limit_value INT NOT NULL DEFAULT 0,
    not_accepting_orders BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
);

-- Create table for order date status
CREATE TABLE IF NOT EXISTS orderdate_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    status ENUM('accepting', 'not_accepting') NOT NULL DEFAULT 'accepting',
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
);

-- Insert default limit if not exists
INSERT IGNORE INTO order_limits (id, default_limit) VALUES (1, 10); 