-- Restore Proper Closing Time
-- Your current closing time is 14:59:00 (2:59 PM) which is too early
-- This will restore it to 23:42:00 (11:42 PM) - the original setting

UPDATE business_hours 
SET closing_time = '23:42:00'
WHERE id = 1;

-- Verify the update
SELECT id, opening_time, closing_time, updated_at 
FROM business_hours 
WHERE id = 1;

-- Expected result:
-- opening_time: 06:00:00 (6:00 AM)
-- closing_time: 23:42:00 (11:42 PM)

