-- Create product_day table for storing available days for each product
CREATE TABLE product_day (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create index for better performance
CREATE INDEX idx_product_day_lookup ON product_day(product_id, day_of_week);
