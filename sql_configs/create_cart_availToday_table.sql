-- Create cart_availToday table for storing Available Today cart items
-- This table stores cart items specifically for Available Today products (status_id = 3)

CREATE TABLE IF NOT EXISTS `cart_availToday` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `price` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cart items for Available Today products';

-- Add some sample data for testing (optional - remove if not needed)
-- INSERT INTO `cart_availToday` (`user_id`, `product_id`, `quantity`, `price`) VALUES
-- (1, 1, 2, 150.00),
-- (1, 2, 1, 200.00),
-- (2, 1, 3, 150.00);

-- Create index for better performance on common queries
CREATE INDEX `idx_user_product_quantity` ON `cart_availToday` (`user_id`, `product_id`, `quantity`);
CREATE INDEX `idx_price_quantity` ON `cart_availToday` (`price`, `quantity`);

-- Add trigger to update the updated_at timestamp
DELIMITER $$
CREATE TRIGGER `update_cart_availToday_timestamp` 
    BEFORE UPDATE ON `cart_availToday` 
    FOR EACH ROW 
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END$$
DELIMITER ;

-- View to get cart items with product details for Available Today cart
CREATE OR REPLACE VIEW `view_cart_availToday_details` AS
SELECT 
    c.id AS cart_id,
    c.user_id,
    c.product_id,
    c.quantity,
    c.price AS cart_price,
    c.created_at,
    c.updated_at,
    p.name AS product_name,
    p.description AS product_description,
    p.price AS current_price,
    p.quantity AS stock_quantity,
    p.status_id,
    ps.name AS status_name,
    pi.image_url,
    (c.quantity * c.price) AS total_price,
    GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days
FROM cart_availToday c
LEFT JOIN products p ON c.product_id = p.id
LEFT JOIN product_statuses ps ON p.status_id = ps.id
LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
LEFT JOIN product_day pd ON p.id = pd.product_id
WHERE p.deleted_at IS NULL
GROUP BY c.id, c.user_id, c.product_id, c.quantity, c.price, c.created_at, c.updated_at, 
         p.name, p.description, p.price, p.quantity, p.status_id, ps.name, pi.image_url
ORDER BY c.created_at DESC;

-- Stored procedure to add item to Available Today cart
DELIMITER $$
CREATE PROCEDURE `AddToAvailTodayCart`(
    IN p_user_id INT,
    IN p_product_id INT,
    IN p_quantity INT,
    IN p_price DECIMAL(10,2)
)
BEGIN
    DECLARE existing_quantity INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Check if item already exists in cart
    SELECT quantity INTO existing_quantity 
    FROM cart_availToday 
    WHERE user_id = p_user_id AND product_id = p_product_id;
    
    IF existing_quantity > 0 THEN
        -- Update existing item
        UPDATE cart_availToday 
        SET quantity = quantity + p_quantity,
            price = p_price,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = p_user_id AND product_id = p_product_id;
    ELSE
        -- Insert new item
        INSERT INTO cart_availToday (user_id, product_id, quantity, price)
        VALUES (p_user_id, p_product_id, p_quantity, p_price);
    END IF;
    
    COMMIT;
END$$
DELIMITER ;

-- Stored procedure to update cart item quantity
DELIMITER $$
CREATE PROCEDURE `UpdateAvailTodayCartQuantity`(
    IN p_user_id INT,
    IN p_product_id INT,
    IN p_new_quantity INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    IF p_new_quantity <= 0 THEN
        -- Remove item if quantity is 0 or negative
        DELETE FROM cart_availToday 
        WHERE user_id = p_user_id AND product_id = p_product_id;
    ELSE
        -- Update quantity
        UPDATE cart_availToday 
        SET quantity = p_new_quantity,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = p_user_id AND product_id = p_product_id;
    END IF;
    
    COMMIT;
END$$
DELIMITER ;

-- Stored procedure to clear user's Available Today cart
DELIMITER $$
CREATE PROCEDURE `ClearAvailTodayCart`(
    IN p_user_id INT
)
BEGIN
    DELETE FROM cart_availToday WHERE user_id = p_user_id;
END$$
DELIMITER ;

-- Function to get Available Today cart total for a user
DELIMITER $$
CREATE FUNCTION `GetAvailTodayCartTotal`(p_user_id INT) 
RETURNS DECIMAL(10,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE cart_total DECIMAL(10,2) DEFAULT 0.00;
    
    SELECT COALESCE(SUM(quantity * price), 0.00) INTO cart_total
    FROM cart_availToday
    WHERE user_id = p_user_id;
    
    RETURN cart_total;
END$$
DELIMITER ;

-- Function to get Available Today cart item count for a user
DELIMITER $$
CREATE FUNCTION `GetAvailTodayCartCount`(p_user_id INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE item_count INT DEFAULT 0;
    
    SELECT COALESCE(SUM(quantity), 0) INTO item_count
    FROM cart_availToday
    WHERE user_id = p_user_id;
    
    RETURN item_count;
END$$
DELIMITER ;
