-- Restore original closing time to 23:42:00 (11:42 PM)
-- Run this after testing is complete

UPDATE business_hours 
SET closing_time = '23:42:00'
WHERE id = 1;

-- Verify the update
SELECT id, 
       opening_time, 
       closing_time, 
       NOW() as current_time,
       CASE 
           WHEN closing_time < TIME(NOW()) THEN 'CLOSED - Cart will be truncated'
           ELSE 'OPEN - Cart remains active'
       END as status
FROM business_hours 
WHERE id = 1;

