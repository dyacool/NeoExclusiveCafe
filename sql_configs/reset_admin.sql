-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- First, remove any existing admin users
DELETE FROM users WHERE is_admin = 1;

-- Reset auto-increment
ALTER TABLE users AUTO_INCREMENT = 1;

-- Create fresh admin user with password: admin123
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
    'Admin',
    'User',
    'admin',
    'admin@neoexclusivecafe.com',
    '$2y$10$8jxXgKqnZJRJ4.1SNZOyVOGq9YpbUVz0QJLXgWqZsJQBJGmZq8Kw2', -- hashed password: admin123
    1,
    1,
    NULL,
    NULL
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1; 