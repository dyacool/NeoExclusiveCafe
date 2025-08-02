-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- First, remove the existing admin
DELETE FROM user_roles WHERE user_id IN (SELECT id FROM users WHERE email = 'annadechavez@hotmail.com');
DELETE FROM users WHERE email = 'annadechavez@hotmail.com';

-- Reset auto-increment
ALTER TABLE users AUTO_INCREMENT = 1;

-- Create fresh admin user with a known password hash
INSERT INTO users (
    firstname,
    lastname,
    username,
    email,
    password,
    is_admin,
    is_verified,
    verification_token,
    verification_token_expires_at
) VALUES (
    'Annalyn',
    'De Chavez',
    'admin',
    'annadechavez@hotmail.com',
    '$2y$10$YourNewPasswordHashHere',  -- We'll replace this with the actual hash
    1,
    1,
    NULL,
    NULL
);

-- Get the admin user ID
SET @admin_id = LAST_INSERT_ID();

-- Make sure admin role exists
INSERT INTO admin_roles (name, description)
SELECT 'admin', 'Full administrative access'
WHERE NOT EXISTS (SELECT 1 FROM admin_roles WHERE name = 'admin');

-- Assign admin role
INSERT INTO user_roles (user_id, role_id)
SELECT @admin_id, id FROM admin_roles WHERE name = 'admin';

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1; 