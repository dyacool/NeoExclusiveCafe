-- Reset Admin Password to: admin1234
-- This script resets the admin user password
-- Use this when you need to regain access to the admin account

-- Generate password hash for 'admin1234'
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'ainepascua4@gmail.com' 
  AND is_admin = 1;

-- Verify the update
SELECT id, username, email, is_admin, 
       SUBSTRING(password, 1, 20) as password_hash_preview
FROM users 
WHERE email = 'ainepascua4@gmail.com';

-- Expected result after update:
-- Username: admin
-- Email: ainepascua4@gmail.com
-- Password: admin1234

