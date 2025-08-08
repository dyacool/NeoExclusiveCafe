-- Create availToday_status table for tracking which products are available today
-- This table will track availability for Delivery and Pick Up products

CREATE TABLE availToday_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    is_available_today TINYINT(1) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product (product_id)
);

-- Add index for better performance when filtering by availability
CREATE INDEX idx_availToday_status ON availToday_status(is_available_today);
CREATE INDEX idx_product_availability ON availToday_status(product_id, is_available_today);

-- Insert initial records for existing Delivery and Pick Up products
INSERT INTO availToday_status (product_id, is_available_today)
SELECT p.id, 1
FROM products p
WHERE p.status_id IN (1, 2) AND p.deleted_at IS NULL
ON DUPLICATE KEY UPDATE is_available_today = 1;

-- Add comment to document the table purpose
ALTER TABLE availToday_status COMMENT = 'Tracks which products are available today (1) or not (0) for Delivery and Pick Up products';
