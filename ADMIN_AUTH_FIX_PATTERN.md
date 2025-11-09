# Admin Auth Fix Pattern - Apply to All Backend Admin Pages

## The Problem
All backend admin pages were migrated with WRONG SessionManager paths:
```php
require_once __DIR__ . '/../../includes/session-manager.php';  // WRONG - only 2 levels up
```

This causes: `Failed to open stream: No such file or directory`

## The Solution
Use `admin-auth.php` which handles everything correctly:

### Pattern 1: Simple Admin Pages
**BEFORE:**
```php
<?php
require_once __DIR__ . '/../../includes/session-manager.php';

if (!SessionManager::isAdminLoggedIn()) {
    header("Location: ../../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";
```

**AFTER:**
```php
<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';
```

### Pattern 2: API Endpoints (JSON responses)
**BEFORE:**
```php
<?php
require_once __DIR__ . '/../../includes/session-manager.php';

if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../admin-includes/database.php';
header('Content-Type: application/json');
```

**AFTER:**
```php
<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

// Additional check for API endpoint
if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');
```

## Files Already Fixed ✅
1. backend/pages/orders/order-list.php
2. backend/pages/orders/update-status.php
3. backend/pages/orders/toggle-auto-status.php
4. backend/pages/orders/view-orders.php
5. backend/pages/products/add-product.php
6. backend/pages/products/product-list.php
7. backend/login/admin/admin-auth.php (fixed load order)

## Files Still Need Fixing (45 remaining)

Apply the pattern above to these files when you encounter errors:

### Products (12 files)
- backend/pages/products/apply-global-days.php
- backend/pages/products/delete-product.php
- backend/pages/products/get-global-available-days.php
- backend/pages/products/get-product-images-edit.php
- backend/pages/products/get-product-images.php
- backend/pages/products/get-sdo-quantities.php
- backend/pages/products/manage-additional-images.php
- backend/pages/products/remove-product-image.php
- backend/pages/products/replace-product-image.php
- backend/pages/products/update-global-available-days.php
- backend/pages/products/update-product.php
- backend/pages/products/update-sdo-quantities.php
- backend/pages/products/upload-product-image.php

### Refund (2 files)
- backend/pages/refund/refund-details.php
- backend/pages/refund/refund-request-lists.php

### Transactions (4 files)
- backend/pages/transactions/export-transactions.php
- backend/pages/transactions/get-chart-data.php
- backend/pages/transactions/get-transactions.php
- backend/pages/transactions/transactions.php

### User Page Content (18 files)
- backend/pages/user-page-content/about-settings.php
- backend/pages/user-page-content/add-category.php
- backend/pages/user-page-content/add-coupon-simple.php
- backend/pages/user-page-content/cb-knowledge-settings.php
- backend/pages/user-page-content/chatbot-knowledge.php
- backend/pages/user-page-content/delete-category.php
- backend/pages/user-page-content/delete-coupon.php
- backend/pages/user-page-content/delivery-locations-handler.php
- backend/pages/user-page-content/delivery-locations.php
- backend/pages/user-page-content/footer-settings.php
- backend/pages/user-page-content/get-coupon.php
- backend/pages/user-page-content/manage-categories.php
- backend/pages/user-page-content/privacy-policy-management.php
- backend/pages/user-page-content/promotions_api.php
- backend/pages/user-page-content/promotions-settings.php
- backend/pages/user-page-content/terms-and-condition-management.php
- backend/pages/user-page-content/update-category.php
- backend/pages/user-page-content/user-content-settings.php
- backend/pages/user-page-content/validate-coupon.php

### Account (3 files)
- backend/pages/account/admin-account.php
- backend/pages/account/admin-profile.php
- backend/pages/account/upload-profile-picture.php

### Cart (1 file)
- backend/pages/cart/submit-refund.php

## How to Apply the Fix

1. Open the file that's giving you an error
2. Find the lines at the top that load SessionManager
3. Replace with the pattern above (Pattern 1 or Pattern 2)
4. Save and test

## Why This Works

`admin-auth.php` does everything correctly:
1. ✅ Loads database.php FIRST (which starts session)
2. ✅ Loads SessionManager SECOND (uses existing session)
3. ✅ Checks admin authentication
4. ✅ Handles redirects
5. ✅ Verifies admin from database

This eliminates all the issues we've been encountering!
