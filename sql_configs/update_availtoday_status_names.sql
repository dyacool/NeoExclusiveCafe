-- Update availtoday_status names to match the code expectations
-- Change "Pick Up" to "Pick-Up" (hyphen instead of space)
-- Change "Delivery and Pick Up" to "Delivery or Pick-Up"

UPDATE availtoday_status 
SET name = 'Pick-Up' 
WHERE id = 1;

UPDATE availtoday_status 
SET name = 'Delivery or Pick-Up' 
WHERE id = 3;

-- Verify the updates
SELECT * FROM availtoday_status ORDER BY id;

