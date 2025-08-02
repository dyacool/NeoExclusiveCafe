-- Delete all users except admin
DELETE FROM users WHERE email != 'annadechavez@hotmail.com';

-- Update admin user settings with new verification token
UPDATE users 
SET 
    firstname = 'Annalyn',
    lastname = 'De Chavez',
    username = 'admin',
    password = '$2y$10$8jxXgKqnZJRJ4.1SNZOyVOGq9YpbUVz0QJLXgWqZsJQBJGmZq8Kw2', -- This is 'admin123' hashed
    is_admin = TRUE,
    is_verified = TRUE,
    verification_token = NULL,
    verification_token_expires_at = NULL
WHERE email = 'annadechavez@hotmail.com'; 