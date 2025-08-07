-- Update product_statuses table
-- Change id 3 from "Unavailable" to "Available Today pick up"
UPDATE product_statuses SET name = 'Available Today pick up' WHERE id = 3;

-- Add new status with id 4: "Unavailable Pick Up"
INSERT INTO product_statuses (id, name) VALUES (4, 'Unavailable Pick Up');

-- Add new status with id 5: "Unavailable Delivery"
INSERT INTO product_statuses (id, name) VALUES (5, 'Unavailable Delivery');

-- Display the updated table
SELECT * FROM product_statuses ORDER BY id;
