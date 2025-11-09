# Bulk Fix: Replace SessionManager with admin-auth.php

## Files to Fix (52 total)

All these files have the WRONG pattern:
```php
require_once __DIR__ . '/../../includes/session-manager.php';
if (!SessionManager::isAdminLoggedIn()) {
    header("Location: ...");
    exit();
}
require_once __DIR__ . "/../admin-includes/database.php";
```

Should be replaced with:
```php
require_once __DIR__ . '/../../login/admin/admin-auth.php';
```

## Files List:

### Orders (3 files)
- backend/pages/orders/view-orders.php ✅ (just fixed)
- backend/pages/orders/update-status.php
- backend/pages/orders/toggle-auto-status.php

### Products (15 files)
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

### Account (2 files)
- backend/pages/account/admin-account.php
- backend/pages/account/admin-profile.php
- backend/pages/account/upload-profile-picture.php

### Cart (1 file)
- backend/pages/cart/submit-refund.php

## Why admin-auth.php?

admin-auth.php already:
1. ✅ Loads database.php FIRST (starts session)
2. ✅ Loads SessionManager SECOND (uses existing session)
3. ✅ Checks admin authentication
4. ✅ Handles redirects
5. ✅ Verifies admin status from database

This eliminates:
- ❌ Wrong paths
- ❌ Load order issues
- ❌ Duplicate includes
- ❌ Session conflicts
- ❌ Manual authentication checks
