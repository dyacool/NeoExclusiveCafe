# Design Document

## Overview

This design creates shared password hashing and verification functions that will be used across all authentication flows (`login-signup.php` and `forgot-pw-reset.php`). The shared functions will eliminate code duplication, remove excessive debugging code, and ensure reliable password handling. The refactoring focuses ONLY on password-related functions, leaving other authentication logic intact.

## Architecture

### Current Issues

**In forgot-pw-reset.php:**
1. **Excessive Debugging Code**: Extensive logging including hex dumps, character analysis, hash comparisons
2. **Potential Hash Corruption**: Multiple verification checks suggest concerns about hash integrity during storage
3. **Code Duplication**: Token validation logic appears in both GET and POST handlers
4. **Mixed Concerns**: Validation, hashing, database operations, and UI rendering are intermingled

**In login-signup.php:**
1. **Excessive Login Debugging**: Raw POST data logging, hex dumps of passwords, character-by-character analysis
2. **Verbose Error Logging**: Detailed debugging sections that expose sensitive information
3. **Inconsistent Input Handling**: Some inputs trimmed, others not (especially passwords)
4. **Mixed Debugging States**: Production code mixed with extensive debugging statements
5. **Forgot Password Logging**: Excessive logging in the password reset request flow

### Proposed Structure

**New File: backend/pages/admin-includes/auth-helpers.php**
```php
<?php
// Shared password hashing function
function hashPassword($password) { ... }

// Shared password verification function  
function verifyPassword($password, $hash) { ... }
?>
```

**Changes to login-signup.php:**
```
1. Add: require_once 'auth-helpers.php' at top
2. Signup flow: Replace password_hash() → hashPassword()
3. Login flow: Replace password_verify() → verifyPassword()
4. Remove: All excessive debugging logs
5. Keep: All existing validation, transaction, email, session logic
```

**Changes to forgot-pw-reset.php:**
```
1. Add: require_once 'auth-helpers.php' at top
2. Password reset: Replace password_hash() → hashPassword()
3. Remove: All excessive debugging logs
4. Keep: All existing token validation, form display logic
```

## Components and Interfaces

### Shared Authentication Helper File

**Location**: `backend/pages/admin-includes/auth-helpers.php`

This new file will contain shared password functions used across all authentication flows.

### 1. Shared Password Hashing Function

**Purpose**: Provide consistent password hashing across all authentication flows

**Interface**:
```php
function hashPassword($password) {
    // Returns: ['success' => bool, 'hash' => string|null, 'error' => string|null]
}
```

**Logic**:
- Trim whitespace from password
- Validate password is not empty
- Generate bcrypt hash with cost factor 10
- Immediately verify the generated hash works
- If verification fails, return error
- Return success with hash

**Usage**:
- Signup: Hash new user password
- Password Reset: Hash new password after reset
- Any future password change functionality

### 2. Shared Password Verification Function

**Purpose**: Provide consistent password verification across all authentication flows

**Interface**:
```php
function verifyPassword($password, $hash) {
    // Returns: bool (true if password matches hash, false otherwise)
}
```

**Logic**:
- Trim whitespace from password
- Use password_verify() to check password against hash
- Return boolean result
- No logging of sensitive data

**Usage**:
- Login: Verify user credentials
- Any future password confirmation functionality

### 3. Integration Points

**In login-signup.php:**
- Replace inline `password_hash()` in signup with `hashPassword()`
- Replace inline `password_verify()` in login with `verifyPassword()`
- Remove all excessive debugging logs
- Keep existing validation, transaction, and email logic

**In forgot-pw-reset.php:**
- Replace inline `password_hash()` with `hashPassword()`
- Remove all excessive debugging logs
- Keep existing token validation and form display logic

## Data Models

### Database Schema (users table)

Relevant columns:
```sql
id INT PRIMARY KEY
username VARCHAR(255)
email VARCHAR(255)
password VARCHAR(255)  -- Must be at least 255 chars for bcrypt hashes
reset_token_hash VARCHAR(255)
reset_token_expires_at DATETIME
is_verified TINYINT(1)
```

### Password Hash Format

- **Algorithm**: bcrypt (PASSWORD_BCRYPT)
- **Cost Factor**: 10 (consistent with signup)
- **Expected Length**: 60 characters
- **Format**: `$2y$10$...` (bcrypt identifier + salt + hash)

## Error Handling

### Error Categories

1. **Token Errors**:
   - Token not found → "Invalid token" + redirect to login
   - Token expired → "Token has expired" + redirect to login

2. **Validation Errors**:
   - Password too short → "Password must be at least 8 characters long"
   - Passwords don't match → "Passwords do not match"

3. **System Errors**:
   - Database connection failure → "Database connection error. Please try again."
   - Hash generation failure → "Password hashing error. Please contact support."
   - Database update failure → "Database error occurred. Please try again."

### Error Display

- Use JavaScript alerts for immediate feedback
- Use styled alert divs for better UX
- Never expose technical details to users
- Log system errors for administrator review

### Logging Strategy

**Production Logging** (minimal):
- Log only critical errors (hash generation failures)
- Log format: Simple error_log() messages
- No sensitive data in logs (no passwords, no hashes)

**Remove from login-signup.php**:
- All "=== RAW POST DATA ===" sections
- All "=== PROCESSED DATA ===" sections
- All "=== LOGIN ATTEMPT DEBUG START ===" sections
- Hex dumps of passwords and usernames
- Character-by-character analysis
- Hash comparison logging
- Detailed password_verify() debugging

**Remove from forgot-pw-reset.php**:
- All "=== PASSWORD RESET DEBUG START ===" sections
- Hex dumps of passwords
- Character-by-character password analysis
- Hash comparison details
- "CRITICAL" warnings and verification loops
- "BEFORE bind_param" and "AFTER bind_param" logging

## Testing Strategy

### Manual Testing Checklist

1. **Happy Path**:
   - Request password reset via email
   - Click reset link
   - Enter new password (8+ chars)
   - Confirm password matches
   - Submit form
   - Verify success message
   - Login with new password

2. **Validation Tests**:
   - Password too short (< 8 chars) → Error displayed
   - Passwords don't match → Error displayed
   - Whitespace in password → Trimmed correctly

3. **Token Tests**:
   - Expired token → Error + redirect
   - Invalid token → Error + redirect
   - Used token (already reset) → Error + redirect

4. **Security Tests**:
   - Verify hash is bcrypt format
   - Verify hash length is 60 characters
   - Verify new password works for login
   - Verify old password no longer works

### Database Verification

After password reset:
```sql
SELECT 
    id,
    username,
    LENGTH(password) as hash_length,
    SUBSTRING(password, 1, 4) as hash_prefix,
    reset_token_hash,
    reset_token_expires_at,
    is_verified
FROM users 
WHERE id = [user_id];
```

Expected results:
- `hash_length`: 60
- `hash_prefix`: `$2y$`
- `reset_token_hash`: NULL
- `reset_token_expires_at`: NULL
- `is_verified`: 1

## Implementation Notes

### Code Organization

**New file: backend/pages/admin-includes/auth-helpers.php**
- `hashPassword()` function
- `verifyPassword()` function
- Minimal error logging

**Modified files keep existing structure:**
- login-signup.php: Keep all existing sections, just replace password functions and remove debug logs
- forgot-pw-reset.php: Keep all existing sections, just replace password functions and remove debug logs

### Consistency with Existing Code

- Use same database connection pattern (`$conn` from `database.php`)
- Use same bcrypt parameters as signup (`PASSWORD_BCRYPT`, cost 10)
- Use same alert/redirect patterns as other auth pages
- Maintain existing UI styling and structure

### Security Considerations

- Always trim password inputs to prevent whitespace issues
- Use prepared statements for all database queries
- Clear reset tokens after successful password change
- Set reasonable token expiration (30 minutes, consistent with signup)
- Use HTTPS for all password reset operations (already configured)
- Verify hash immediately after generation before storage

### Performance Considerations

- Bcrypt cost factor 10 provides good security/performance balance
- Single database query for token validation
- Single database query for password update
- No unnecessary hash regeneration or verification loops
