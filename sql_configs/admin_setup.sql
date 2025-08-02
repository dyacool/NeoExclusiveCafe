-- First delete all non-admin users
DELETE FROM users WHERE email != 'annadechavez@hotmail.com';

-- Drop existing admin-related tables in correct order
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS admin_role_permissions;
DROP TABLE IF EXISTS admin_permissions;
DROP TABLE IF EXISTS admin_roles;

-- Create admin_permissions table
CREATE TABLE IF NOT EXISTS admin_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin_roles table
CREATE TABLE IF NOT EXISTS admin_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin_role_permissions table (junction table)
CREATE TABLE IF NOT EXISTS admin_role_permissions (
    role_id INT,
    permission_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES admin_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES admin_permissions(id) ON DELETE CASCADE
);

-- Create user_roles table (junction table)
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT,
    role_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES admin_roles(id) ON DELETE CASCADE
);

-- Insert default permissions
INSERT INTO admin_permissions (name, description) VALUES
('manage_users', 'Can manage user accounts'),
('manage_products', 'Can manage products'),
('manage_orders', 'Can manage orders'),
('manage_blog', 'Can manage blog posts'),
('view_analytics', 'Can view analytics and reports');

-- Insert single admin role
INSERT INTO admin_roles (name, description) VALUES
('admin', 'Full administrative access to all features');

-- Assign all permissions to admin role
INSERT INTO admin_role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM admin_roles WHERE name = 'admin'),
    id
FROM admin_permissions;

-- Update admin user to ensure correct settings
UPDATE users 
SET 
    firstname = 'Annalyn',
    lastname = 'De Chavez',
    username = 'admin',
    is_admin = TRUE,
    is_verified = TRUE
WHERE email = 'annadechavez@hotmail.com'; 