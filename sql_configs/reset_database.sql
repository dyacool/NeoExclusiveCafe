-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Delete all users except admin
DELETE FROM users WHERE email != 'annadechavez@hotmail.com';

-- Reset auto-increment for users table
ALTER TABLE users AUTO_INCREMENT = 1;

-- Delete all records from related tables (user_roles removed)
DELETE FROM admin_role_permissions;
DELETE FROM admin_permissions;
DELETE FROM admin_roles;

-- Reset auto-increment for all tables
ALTER TABLE admin_permissions AUTO_INCREMENT = 1;
ALTER TABLE admin_roles AUTO_INCREMENT = 1;

-- Reinsert admin permissions
INSERT INTO admin_permissions (name, description) VALUES
('manage_users', 'Can manage user accounts'),
('manage_products', 'Can manage products'),
('manage_orders', 'Can manage orders'),
('manage_blog', 'Can manage blog posts'),
('view_analytics', 'Can view analytics and reports');

-- Insert admin role
INSERT INTO admin_roles (name, description) VALUES
('admin', 'Full administrative access to all features');

-- Assign all permissions to admin role
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM admin_roles WHERE name = 'admin'),
    id
FROM admin_permissions;

-- Admin role assignment removed - using is_admin flag only

-- Update admin user settings
UPDATE users 
SET 
    firstname = 'Annalyn',
    lastname = 'De Chavez',
    username = 'admin',
    is_admin = TRUE,
    is_verified = TRUE,
    verification_token = NULL,
    verification_token_expires_at = NULL
WHERE email = 'annadechavez@hotmail.com';

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1; 