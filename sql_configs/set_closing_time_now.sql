-- Temporarily set closing time to 5 minutes ago for testing
-- This will trigger automatic cart truncation

-- Get current time minus 5 minutes
UPDATE business_hours 
SET closing_time = DATE_SUB(NOW(), INTERVAL 5 MINUTE)
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

