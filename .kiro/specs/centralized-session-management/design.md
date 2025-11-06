# Design Document: Centralized Session Management

## Overview

The centralized session management system will provide a unified API for handling authentication and session state across the NeoCafe application. The core component is a `SessionManager` class that encapsulates all session-related logic, replacing scattered session checks with consistent, maintainable methods.

The design focuses on:
- **Simplicity**: Static methods for easy access without instantiation
- **Backward Compatibility**: Works with existing session structure
- **Security**: Consistent validation logic prevents authentication bypass
- **Developer Experience**: Clear API with helpful methods for common tasks

## Architecture

### Component Structure

```
includes/
  └── session-manager.php (SessionManager class)

frontend/
  ├── user-includes/
  │   ├── user-header.php (refactored)
  │   ├── preview-mode.php (refactored)
  │   └── navbar/
  │       └── customer-navigation.php (refactored)
  ├── pages/
  │   ├── profile/*.php (refactored)
  │   ├── cart/*.php (refactored)
  │   ├── notifications/*.php (refactored)
  │   └── products/*.php (refactored)
  └── login/user/*.php (minimal changes)

backend/
  └── login/admin/*.php (no changes needed)
```

### Session Variable Structure

**User Session (Customer):**
```php
$_SESSION['user_id']        // int: User ID
$_SESSION['user_username']  // string: Username
$_SESSION['user_firstname'] // string: First name
$_SESSION['user_lastname']  // string: Last name
$_SESSION['user_role']      // string: 'user'
$_SESSION['is_verified']    // bool: Email verified
```

**Admin Session:**
```php
$_SESSION['admin_id']       // int: Admin ID
$_SESSION['admin_username'] // string: Username
$_SESSION['admin_firstname']// string: First name
$_SESSION['admin_lastname'] // string: Last name
$_SESSION['admin_role']     // string: 'admin'
$_SESSION['is_admin']       // bool: true
```

## Components and Interfaces

### SessionManager Class

**Location:** `includes/session-manager.php`

**Purpose:** Centralized session management with static methods for authentication checks, data retrieval, and session control.

#### Public API

```php
class SessionManager {
    
    // Authentication Checks
    public static function isUserLoggedIn(): bool
    public static function isAdminLoggedIn(): bool
    public static function isPreviewMode(): bool
    
    // Data Retrieval
    public static function getUserId(): ?int
    public static function getUserData(): ?array
    public static function getAdminData(): ?array
    public static function getRole(): string
    
    // Session Control
    public static function requireUserLogin(string $redirectUrl = '/frontend/login/user/login-signup.php'): void
    public static function requireAdminLogin(string $redirectUrl = '/backend/login/admin/admin-login.php'): void
    public static function destroySession(): void
    
    // Internal Helpers
    private static function ensureSessionStarted(): void
}
```

#### Method Specifications

**isUserLoggedIn()**
- Returns `true` if valid user session exists
- Validation: `$_SESSION['user_id']` is set AND `$_SESSION['user_role']` === 'user'
- Returns `false` otherwise

**isAdminLoggedIn()**
- Returns `true` if valid admin session exists
- Validation: `$_SESSION['is_admin']` === true AND `$_SESSION['admin_role']` === 'admin'
- Returns `false` otherwise
- Note: Does NOT check `$_SESSION['admin_id']` for authentication

**isPreviewMode()**
- Returns `true` if neither user nor admin is logged in
- Equivalent to: `!isUserLoggedIn() && !isAdminLoggedIn()`

**getUserId()**
- Returns user ID as integer if user is logged in
- Returns `null` if not logged in or invalid session
- Type-casts to int for safety

**getUserData()**
- Returns associative array with keys: `id`, `username`, `firstname`, `lastname`, `role`
- Returns `null` if not logged in
- Maps `$_SESSION['user_*']` variables to clean array structure

**getAdminData()**
- Returns associative array with keys: `id`, `username`, `firstname`, `lastname`, `role`
- Returns `null` if not logged in
- Maps `$_SESSION['admin_*']` variables to clean array structure

**getRole()**
- Returns `'user'` if user is logged in
- Returns `'admin'` if admin is logged in
- Returns `'guest'` if neither is logged in

**requireUserLogin($redirectUrl)**
- Checks if user is logged in
- If not, performs `header("Location: $redirectUrl")` and exits
- Default redirect: `/frontend/login/user/login-signup.php`

**requireAdminLogin($redirectUrl)**
- Checks if admin is logged in
- If not, performs `header("Location: $redirectUrl")` and exits
- Default redirect: `/backend/login/admin/admin-login.php`

**destroySession()**
- Clears all session variables
- Destroys the session
- Useful for logout functionality

**ensureSessionStarted()** (private)
- Checks if session is already started using `session_status()`
- Calls `session_start()` if needed
- Called automatically by all public methods

## Data Models

No database changes required. The SessionManager works with existing session structure.

### Session Data Transfer Objects

**UserData Array:**
```php
[
    'id' => int,
    'username' => string,
    'firstname' => string,
    'lastname' => string,
    'role' => 'user'
]
```

**AdminData Array:**
```php
[
    'id' => int,
    'username' => string,
    'firstname' => string,
    'lastname' => string,
    'role' => 'admin'
]
```

## Error Handling

### Principles
- **Fail Gracefully**: Methods return `false` or `null` instead of throwing exceptions
- **No Exceptions**: Avoids breaking existing code that doesn't expect exceptions
- **Silent Failures**: Invalid session states are treated as "not logged in"

### Scenarios

**Session Not Started:**
- `ensureSessionStarted()` automatically starts session
- No error thrown

**Missing Session Variables:**
- Authentication methods return `false`
- Data retrieval methods return `null`
- No warnings or errors logged

**Invalid Session Data:**
- Type mismatches (e.g., non-integer user_id) treated as invalid
- Returns `false` or `null` appropriately

**Redirect Failures:**
- `requireUserLogin()` and `requireAdminLogin()` call `exit()` after redirect
- No error handling needed as script terminates

## Migration Strategy

### Phase 1: Create SessionManager
1. Create `includes/session-manager.php`
2. Implement all methods with full PHPDoc comments
3. Add usage examples in comments

### Phase 2: Refactor Common Files
Priority files that affect multiple pages:
1. `frontend/user-includes/user-header.php`
2. `frontend/user-includes/preview-mode.php`
3. `frontend/user-includes/navbar/customer-navigation.php`

### Phase 3: Refactor Feature Directories
Refactor by feature area:
1. Profile pages (`frontend/pages/profile/*.php`)
2. Cart pages (`frontend/pages/cart/*.php`)
3. Notification pages (`frontend/pages/notifications/*.php`)
4. Product pages (`frontend/pages/products/*.php`)
5. Home/search pages

### Phase 4: Refactor Remaining Files
1. Blog pages
2. API endpoints
3. Utility scripts

### Migration Patterns

**Before:**
```php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../../login/user/login-signup.php");
    exit();
}
```

**After:**
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
SessionManager::requireUserLogin();
```

**Before:**
```php
$is_preview_mode = !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
$is_user_logged_in = isset($_SESSION['user_id']);
$is_admin_logged_in = isset($_SESSION['admin_id']);
```

**After:**
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
$is_preview_mode = SessionManager::isPreviewMode();
$is_user_logged_in = SessionManager::isUserLoggedIn();
$is_admin_logged_in = SessionManager::isAdminLoggedIn();
```

**Before:**
```php
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id > 0) {
    // Fetch user data
}
```

**After:**
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
$user_id = SessionManager::getUserId();
if ($user_id !== null) {
    // Fetch user data
}
```

## Testing Strategy

### Unit Testing Approach

Since this is a PHP project without a testing framework, testing will be manual and integration-focused.

### Test Scenarios

**1. User Authentication**
- Login as user → `isUserLoggedIn()` returns true
- Logout → `isUserLoggedIn()` returns false
- Access protected page → redirects to login

**2. Admin Authentication**
- Login as admin → `isAdminLoggedIn()` returns true
- Logout → `isAdminLoggedIn()` returns false
- Access admin page → redirects to admin login

**3. Preview Mode**
- No login → `isPreviewMode()` returns true
- User login → `isPreviewMode()` returns false
- Admin login → `isPreviewMode()` returns false

**4. Data Retrieval**
- User logged in → `getUserData()` returns correct array
- Admin logged in → `getAdminData()` returns correct array
- Not logged in → both return null

**5. Role Detection**
- User logged in → `getRole()` returns 'user'
- Admin logged in → `getRole()` returns 'admin'
- Not logged in → `getRole()` returns 'guest'

**6. Session Protection**
- Access cart without login → redirects to login
- Access profile without login → redirects to login
- Access admin page without admin login → redirects to admin login

### Integration Testing

**Test Files to Verify:**
1. `frontend/pages/profile/profile.php` - User data display
2. `frontend/pages/cart/checkout.php` - Protected checkout
3. `frontend/user-includes/navbar/customer-navigation.php` - Navbar state
4. `frontend/pages/products/product-dashboard.php` - Preview mode
5. `backend/pages/orders/order-list.php` - Admin access

### Validation Checklist

- [ ] User can login and stay logged in
- [ ] Admin can login and stay logged in
- [ ] Navbar shows correct state for user/admin/guest
- [ ] Protected pages redirect when not logged in
- [ ] Cart functionality works for logged-in users
- [ ] Profile page displays correct user data
- [ ] Preview mode works for non-logged-in visitors
- [ ] Logout clears session properly
- [ ] No PHP errors or warnings in logs

## Performance Considerations

- **Static Methods**: No object instantiation overhead
- **Lazy Session Start**: Session only started when needed
- **No Database Calls**: Pure session-based, no DB queries
- **Minimal Logic**: Simple boolean checks, no complex operations
- **No Caching Needed**: Session data already in memory

## Security Considerations

### Authentication Validation
- **Dual Checks**: Both role and ID/flag must be present
- **Strict Comparison**: Uses `===` for type-safe comparisons
- **No Fallbacks**: Missing variables treated as not authenticated

### Session Fixation Prevention
- SessionManager doesn't handle login/logout directly
- Existing `session_regenerate_id()` calls in login files remain
- No changes to session ID management

### XSS Prevention
- SessionManager doesn't output data directly
- Calling code responsible for escaping output
- No HTML generation in SessionManager

### CSRF Protection
- SessionManager doesn't handle CSRF tokens
- Existing CSRF protection mechanisms unchanged
- Session validation is separate concern

## Documentation

### PHPDoc Example

```php
/**
 * Check if a user (customer) is currently logged in
 * 
 * Validates that the session contains a valid user_id and user_role='user'.
 * This is the correct way to check user authentication in frontend pages.
 * 
 * @return bool True if user is logged in, false otherwise
 * 
 * @example
 * if (SessionManager::isUserLoggedIn()) {
 *     echo "Welcome back!";
 * }
 */
public static function isUserLoggedIn(): bool
```

### Migration Guide Structure

1. **Overview**: Why we're migrating
2. **Quick Reference**: Common patterns before/after
3. **Step-by-Step**: How to refactor a file
4. **Troubleshooting**: Common issues and solutions
5. **Examples**: Real file refactoring examples

## Implementation Notes

### File Paths
- SessionManager location: `includes/session-manager.php`
- Accessible from frontend: `../../../includes/session-manager.php`
- Accessible from backend: `../../includes/session-manager.php`
- Adjust relative paths based on file location

### Backward Compatibility
- Existing session variables unchanged
- Login/logout logic unchanged
- Can be adopted incrementally
- Old and new patterns can coexist during migration

### Future Enhancements
- Add session timeout checking
- Add "remember me" functionality
- Add session activity logging
- Add multi-device session management
- Add session data encryption

These enhancements are out of scope for initial implementation but the design supports future extension.
