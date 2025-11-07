# Design Document

## Overview

This design provides a systematic approach to auditing and migrating all PHP files in the NeoCafe application from legacy session handling to the centralized SessionManager. The migration will be performed in phases, prioritizing critical files first, and will include comprehensive testing to ensure no functionality is broken.

## Architecture

### Migration Phases

```
Phase 1: Discovery & Analysis
    ↓
Phase 2: Critical Files Migration
    ↓
Phase 3: High-Priority Files Migration
    ↓
Phase 4: Remaining Files Migration
    ↓
Phase 5: Verification & Testing
```

## Discovery Patterns

### Legacy Patterns to Identify

1. **Direct session_start() calls:**
   ```php
   session_start();
   ```

2. **User authentication checks:**
   ```php
   isset($_SESSION['user_id'])
   isset($_SESSION['user_username'])
   $_SESSION['user_role'] === 'user'
   ```

3. **Admin authentication checks:**
   ```php
   isset($_SESSION['is_admin'])
   $_SESSION['is_admin'] === true
   $_SESSION['admin_role'] === 'admin'
   ```

4. **Direct session data access:**
   ```php
   $_SESSION['user_id']
   $_SESSION['user_firstname']
   $_SESSION['user_lastname']
   ```

## Migration Patterns

### Pattern 1: Session Initialization

**Before:**
```php
<?php
session_start();
require_once 'database.php';
```

**After:**
```php
<?php
require_once __DIR__ . '/path/to/includes/session-manager.php';
require_once 'database.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### Pattern 2: User Authentication Check

**Before:**
```php
if (isset($_SESSION['user_id'])) {
    // User is logged in
}
```

**After:**
```php
if (SessionManager::isUserLoggedIn()) {
    // User is logged in
}
```

### Pattern 3: Admin Authentication Check

**Before:**
```php
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    // Admin is logged in
}
```

**After:**
```php
if (SessionManager::isAdminLoggedIn()) {
    // Admin is logged in
}
```

### Pattern 4: User Data Access

**Before:**
```php
$userId = $_SESSION['user_id'];
$username = $_SESSION['user_username'];
$firstname = $_SESSION['user_firstname'];
```

**After:**
```php
$userId = SessionManager::getUserId();
$username = SessionManager::getUsername();
$firstname = SessionManager::getFirstName();
```

### Pattern 5: Redirect if Not Logged In

**Before:**
```php
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}
```

**After:**
```php
SessionManager::requireUserLogin();
```

### Pattern 6: Admin Page Protection

**Before:**
```php
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: /admin/login.php");
    exit();
}
```

**After:**
```php
SessionManager::requireAdminLogin();
```

## File Prioritization

### Priority 1: Critical Authentication Files
- `frontend/login/user/login-signup.php` ✅ (Already uses proper session handling)
- `frontend/login/user/verify-email.php` ✅ (Fixed)
- `frontend/login/user/verification-page.php` ✅ (Fixed)
- `frontend/login/user/forgot-pw-reset.php`
- `frontend/login/admin/admin-login.php`
- `backend/pages/auth/logout.php`

### Priority 2: Payment & Checkout Files
- `frontend/pages/cart/checkout.php`
- `frontend/pages/cart/process-payment.php`
- `frontend/pages/cart/payment-return.php`
- `frontend/pages/cart/payment-success.php`
- `frontend/pages/cart/process_order.php`
- `frontend/pages/cart/process-availtoday-checkout.php`

### Priority 3: API Endpoints
- `backend/api/*.php` (All API files that check authentication)
- `frontend/api/*.php` (All API files that check authentication)

### Priority 4: Admin Pages
- `backend/pages/dashboard/dashboard.php`
- `backend/pages/orders/order-list.php`
- `backend/pages/products/product-list.php`
- All other backend admin pages

### Priority 5: Frontend User Pages
- `frontend/pages/home/user-dashboard.php`
- `frontend/pages/profile/*.php`
- All other frontend user pages

## Migration Strategy

### Step 1: Scan for Legacy Patterns

Use grep to find all files with legacy session patterns:

```bash
# Find files with session_start()
grep -r "session_start()" --include="*.php" .

# Find files with direct $_SESSION checks
grep -r "\$_SESSION\['user_id'\]" --include="*.php" .
grep -r "\$_SESSION\['is_admin'\]" --include="*.php" .

# Find files with manual authentication
grep -r "isset(\$_SESSION\['user_id'\])" --include="*.php" .
```

### Step 2: Analyze Each File

For each file found:
1. Read the file content
2. Identify all legacy session patterns
3. Determine the appropriate SessionManager methods
4. Plan the migration changes

### Step 3: Migrate File

For each file:
1. Add SessionManager include if not present
2. Replace session_start() with proper check
3. Replace authentication checks with SessionManager methods
4. Replace data access with SessionManager methods
5. Test PHP syntax
6. Document changes

### Step 4: Verify Migration

For each migrated file:
1. Check PHP syntax with `php -l`
2. Verify SessionManager is included
3. Verify no direct session_start() calls remain
4. Verify authentication logic is correct
5. Test the file functionality

## SessionManager Methods Reference

### Authentication Checks
- `SessionManager::isUserLoggedIn()` - Check if user is logged in
- `SessionManager::isAdminLoggedIn()` - Check if admin is logged in
- `SessionManager::isPreviewMode()` - Check if neither user nor admin is logged in

### Page Protection
- `SessionManager::requireUserLogin()` - Redirect to login if not logged in as user
- `SessionManager::requireAdminLogin()` - Redirect to admin login if not logged in as admin

### User Data Access
- `SessionManager::getUserId()` - Get current user ID
- `SessionManager::getUsername()` - Get current username
- `SessionManager::getFirstName()` - Get user's first name
- `SessionManager::getLastName()` - Get user's last name
- `SessionManager::getUserData()` - Get all user data as array

### Admin Data Access
- `SessionManager::getAdminId()` - Get current admin ID
- `SessionManager::getAdminUsername()` - Get admin username
- `SessionManager::getAdminData()` - Get all admin data as array

## Testing Strategy

### Automated Testing
1. PHP syntax validation for all migrated files
2. Grep verification to ensure no legacy patterns remain
3. SessionManager include verification

### Manual Testing
1. Test user login flow
2. Test admin login flow
3. Test protected pages (user and admin)
4. Test logout functionality
5. Test session persistence across pages
6. Test authentication redirects

## Migration Log Format

```
File: path/to/file.php
Status: Migrated | Skipped | Failed
Changes:
  - Added SessionManager include
  - Replaced session_start() with proper check
  - Replaced isset($_SESSION['user_id']) with SessionManager::isUserLoggedIn()
  - Replaced $_SESSION['user_id'] with SessionManager::getUserId()
Notes: Any special considerations or issues
```

## Exclusions

Files that should NOT be migrated:
- `includes/session-manager.php` (The SessionManager itself)
- `vendor/**` (Third-party libraries)
- Test files that specifically test legacy behavior
- Database migration scripts

## Rollback Strategy

If migration causes issues:
1. Keep backup of original files
2. Document all changes in migration log
3. Can revert specific files if needed
4. Git version control provides safety net

## Success Criteria

Migration is complete when:
1. All PHP files use SessionManager for authentication
2. No direct `session_start()` calls without proper checks
3. No direct `$_SESSION` authentication checks
4. All authentication uses SessionManager methods
5. All tests pass
6. No session-related bugs reported
