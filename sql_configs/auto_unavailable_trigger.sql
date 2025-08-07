-- SQL script to add automatic status update trigger for products with quantity 0

-- First, let's check if the trigger already exists and drop it if it does
DROP TRIGGER IF EXISTS auto_set_unavailable_on_zero_quantity;

-- Create trigger to automatically set status to appropriate unavailable status when quantity reaches 0
DELIMITER //
CREATE TRIGGER auto_set_unavailable_on_zero_quantity
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    -- If quantity is 0 or less, set status to appropriate unavailable status based on current type
    IF NEW.quantity <= 0 THEN
        DECLARE new_status_id INT DEFAULT 5; -- Default to Unavailable Delivery
        
        -- Determine the appropriate unavailable status based on current status
        IF NEW.status_id = 1 THEN
            -- Currently Pick Up - set to Unavailable Pick Up (ID 4)
            SET new_status_id = 4;
        ELSEIF NEW.status_id = 2 THEN
            -- Currently Delivery - set to Unavailable Delivery (ID 5)
            SET new_status_id = 5;
        ELSE
            -- For any other status, default to Unavailable Delivery (ID 5)
            SET new_status_id = 5;
        END IF;
        
        -- Update the status
        UPDATE products SET status_id = new_status_id WHERE id = NEW.id;
    END IF;
END//
DELIMITER ;

-- Also create a trigger for INSERT to handle new products with 0 quantity
DROP TRIGGER IF EXISTS auto_set_unavailable_on_insert;

DELIMITER //
CREATE TRIGGER auto_set_unavailable_on_insert
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
    -- If quantity is 0 or less, automatically set status to Unavailable Delivery (ID 5)
    IF NEW.quantity <= 0 THEN
        SET NEW.status_id = 5;
    END IF;
END//
DELIMITER ;

-- Show the created triggers
SHOW TRIGGERS LIKE 'products';
