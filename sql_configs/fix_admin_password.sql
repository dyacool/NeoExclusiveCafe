-- Update admin user password to 'admin123'
UPDATE users 
SET password = '$2y$10$PXxUqMZBqwJaFyYuGxVQWuHoKpZ9.7O3gbrehZOhEeJh/VRJj4nDm'  -- This is 'admin123' hashed
WHERE username = 'admin' AND is_admin = 1;

-- Ensure the admin is verified
UPDATE users 
SET is_verified = 1 
WHERE username = 'admin' AND is_admin = 1; 