CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    last_attempt DATETIME NOT NULL,
    type VARCHAR(10) NOT NULL,
    UNIQUE KEY unique_ip_type (ip_address, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 