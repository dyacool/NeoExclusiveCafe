<?php
/**
 * Authentication Helper Functions
 * 
 * Shared password hashing and verification functions used across
 * all authentication flows (login, signup, password reset).
 */

/**
 * Hash a password using bcrypt with consistent settings
 * 
 * @param string $password The plain-text password to hash
 * @return array ['success' => bool, 'hash' => string|null, 'error' => string|null]
 */
function hashPassword($password) {
    // Trim whitespace from password
    $password = trim($password);
    
    // Validate password is not empty
    if (empty($password)) {
        return [
            'success' => false,
            'hash' => null,
            'error' => 'Password cannot be empty'
        ];
    }
    
    // Generate bcrypt hash with cost factor 10
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    // Check if hash generation failed
    if ($hash === false) {
        error_log('Password hash generation failed');
        return [
            'success' => false,
            'hash' => null,
            'error' => 'Failed to generate password hash'
        ];
    }
    
    // Immediately verify the generated hash works
    if (!password_verify($password, $hash)) {
        error_log('Password hash verification failed immediately after generation');
        return [
            'success' => false,
            'hash' => null,
            'error' => 'Hash verification failed'
        ];
    }
    
    // Return success with the hash
    return [
        'success' => true,
        'hash' => $hash,
        'error' => null
    ];
}

/**
 * Verify a password against a stored hash
 * 
 * @param string $password The plain-text password to verify
 * @param string $hash The stored bcrypt hash
 * @return bool True if password matches hash, false otherwise
 */
function verifyPassword($password, $hash) {
    // Trim whitespace from password
    $password = trim($password);
    
    // Use password_verify to check password against hash
    return password_verify($password, $hash);
}
