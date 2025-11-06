# SessionManager Usage Guide

## Overview

The `SessionManager` class provides a centralized, consistent API for handling authentication and session state across the NeoCafe application. It eliminates scattered session checks and provides a single source of truth for authentication logic.

## Quick Start

```php
// Include the SessionManager
require_once __DIR__ . '/path/to/includes/session-manager.php';

// Check if user is logged in
if (SessionManager::isUserLoggedIn()) {
    $userId = SessionManager::getUserId();
    echo "Welcome back, user #$userId!";
}

// Protect a page (redirect if not logged in)
SessionManager::requireUserLogin();
```

## API Reference

### Authentication Checks

#### `isUserLoggedIn(): bool`
Check if a customer user is currently logged in.

```php
if (SessionManager::isUserLoggedIn()) {
    // User is logged in
}
```

#### `isAdminLoggedIn(): bool`
Check if an admin is currently logged in.

```php
if (SessionManager::isAdminLoggedIn()) {
    // Admin is logged in
}
```

#### `isPreviewMode(): bool`
Check if visitor is in preview mode (not logged in as user or admin).

```php
if (SessionManager::isPreviewMode()) {
    echo "Sign in to access all features";
}
```

### Data Retrieval

#### `getUserId(): ?int`
Get the current user's ID. Returns `null` if not logged in.

```php
$userId = SessionManager::getUserId();
if ($userId !== null) {
    // Fetch user data from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
}
```

#### `getUserData(): ?array`
Get user data as an associative array.

```php
$user = SessionManager::getUserData();
if ($user) {
    echo "Hello, " . htmlspecialchars($user['firstname']);
    // Array keys: id, username, firstname, lastname, role
}
```

#### `getAdminData(): ?array`
Get admin data as an associative array.

```php
$admin = SessionManager::getAdminData();
if ($admin) {
    echo "Admin: " . htmlspecialchars($admin['username']);
    // Array keys: id, username, firstname, lastname, role
}
```

#### `getRole(): string`
Get the current session role: 'user', 'admin', or 'guest'.

```php
$role = SessionManager::getRole();
switch ($role) {
    case 'user':
        // Show user features
        break;
    case 'admin':
        // Show admin features
        break;
    case 'guest':
        // Show limited features
        break;
}
```

### Session Control

#### `requireUserLogin(string $redirectUrl = '/frontend/login/user/login-signup.php'): void`
Require user login. Redirects to login page if not authenticated.

```php
// At the top of a protected page
SessionManager::requireUserLogin();
// Rest of the page code...
```

With custom redirect:
```php
SessionManager::requireUserLogin('/custom/login/page.php');
```

#### `requireAdminLogin(string $redirectUrl = '/backend/login/admin/admin-login.php'): void`
Require admin login. Redirects to admin login if not authenticated.

```php
// At the top of an admin page
SessionManager::requireAdminLogin();
// Rest of the page code...
```

#### `destroySession(): void`
Destroy the current session (for logout).

```php
// In logout.php
SessionManager::destroySession();
header("Location: /");
exit();
```

## Migration Guide

### Pattern 1: Manual Authentication Check

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
SessionManager::requireUserLogin('../../login/user/login-signup.php');
```

### Pattern 2: Preview Mode Check

**Before:**
```php
$is_preview_mode = !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
```

**After:**
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
$is_preview_mode = SessionManager::isPreviewMode();
```

### Pattern 3: User ID Access

**Before:**
```php
$user_id = $_SESSION['user_id'];
```

**After:**
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
$user_id = SessionManager::getUserId();
```

### Pattern 4: Conditional Login Check

**Before:**
```php
if (isset($_SESSION['user_id'])) {
    // User is logged in
}
```

**After:**
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
if (SessionManager::isUserLoggedIn()) {
    // User is logged in
}
```

### Pattern 5: JavaScript Login State

**Before:**
```php
const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
```

**After:**
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
const isLoggedIn = <?= SessionManager::isUserLoggedIn() ? 'true' : 'false' ?>;
```

### Pattern 6: Admin Check (IMPORTANT FIX)

**Before (INCORRECT):**
```php
$is_admin_logged_in = isset($_SESSION['admin_id']);
```

**After (CORRECT):**
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
$is_admin_logged_in = SessionManager::isAdminLoggedIn();
```

**Why?** The old pattern checked `admin_id` which could be set even when not logged in. The correct check validates `is_admin === true` AND `admin_role === 'admin'`.

## Common Use Cases

### Protected Page

```php
<?php
require_once __DIR__ . '/../../../includes/session-manager.php';
SessionManager::requireUserLogin();

// Page is now protected - only logged-in users can access
$userId = SessionManager::getUserId();
?>
```

### Conditional Content

```php
<?php require_once __DIR__ . '/../../../includes/session-manager.php'; ?>

<?php if (SessionManager::isUserLoggedIn()): ?>
    <p>Welcome back!</p>
    <a href="/profile">My Profile</a>
<?php else: ?>
    <p>Please log in to continue</p>
    <a href="/login">Login</a>
<?php endif; ?>
```

### API Endpoint

```php
<?php
require_once __DIR__ . '/../../../includes/session-manager.php';
header('Content-Type: application/json');

if (!SessionManager::isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = SessionManager::getUserId();
// Process API request...
```

### Role-Based Access

```php
<?php
require_once __DIR__ . '/../../../includes/session-manager.php';

$role = SessionManager::getRole();

if ($role === 'admin') {
    // Show admin dashboard
} elseif ($role === 'user') {
    // Show user dashboard
} else {
    // Show public homepage
}
?>
```

## Troubleshooting

### Issue: "Call to undefined method SessionManager::..."

**Solution:** Make sure you've included the SessionManager file:
```php
require_once __DIR__ . '/../../../includes/session-manager.php';
```

### Issue: Session not persisting

**Solution:** SessionManager automatically starts the session if needed. Make sure you're not calling `session_start()` after including SessionManager in a way that causes conflicts.

### Issue: Wrong redirect URL

**Solution:** Specify the correct redirect URL for your file structure:
```php
SessionManager::requireUserLogin('../../login/user/login-signup.php');
```

### Issue: getUserId() returns null

**Solution:** This means the user is not logged in. Always check:
```php
$userId = SessionManager::getUserId();
if ($userId === null) {
    // Handle not logged in case
}
```

## Best Practices

1. **Always include SessionManager early** in your PHP files, before any HTML output
2. **Use `requireUserLogin()` for protected pages** instead of manual checks
3. **Check return values** - `getUserId()` and `getUserData()` can return `null`
4. **Use `isUserLoggedIn()` for conditional logic** instead of checking session variables directly
5. **Never check `$_SESSION['admin_id']` directly** - always use `isAdminLoggedIn()`

## File Locations

- **SessionManager class:** `includes/session-manager.php`
- **From frontend pages:** `../../../includes/session-manager.php`
- **From backend pages:** `../../includes/session-manager.php`

## Migration Checklist

When refactoring a file to use SessionManager:

- [ ] Add `require_once` for SessionManager at the top
- [ ] Replace `isset($_SESSION['user_id'])` with `SessionManager::isUserLoggedIn()`
- [ ] Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
- [ ] Replace manual redirect logic with `SessionManager::requireUserLogin()`
- [ ] Replace `isset($_SESSION['admin_id'])` with `SessionManager::isAdminLoggedIn()`
- [ ] Replace preview mode checks with `SessionManager::isPreviewMode()`
- [ ] Test the page to ensure authentication works correctly

## Support

For issues or questions about SessionManager, refer to:
- This guide
- The SessionManager class source code (`includes/session-manager.php`)
- The design document (`.kiro/specs/centralized-session-management/design.md`)
