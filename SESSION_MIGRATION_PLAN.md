# Complete Session Migration Plan

## Overview
Found **97 instances** of old session handling across **95 files** that need migration to SessionManager.

---

## Migration Categories

### 🔴 Priority 1: Backend API Files (Admin Auth)
**Pattern:** `!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true`
**Replace with:** `!SessionManager::isAdminLoggedIn()`

**Files (30):**
- backend/api/delete-carousel-image.php
- backend/api/delete-product-image.php
- backend/api/delete-profile-picture.php
- backend/api/get-dashboard-stats.php
- backend/api/get-order-list.php
- backend/api/upload-carousel-image.php
- backend/api/upload-product-image.php
- backend/api/upload-profile-picture.php
- backend/database/run-migration.php
- backend/pages/account/reset-password.php
- backend/pages/admin-includes/navbar/navbar.php
- backend/pages/admin-includes/navbar/test-order-count.php
- backend/pages/archives/archive.php
- backend/pages/archives/delete-permanently.php
- backend/pages/archives/restore-product.php
- backend/pages/blog/admin-blog-createpost.php
- backend/pages/blog/admin-blog.php
- backend/pages/blog/blog-details.php
- backend/pages/blog/delete-post.php
- backend/pages/blog/get-post-data.php
- backend/pages/blog/test-data.php
- backend/pages/blog/update-post.php
- backend/pages/bulks/bulk-order-lists.php
- backend/pages/bulks/bulk-order.php
- backend/pages/bulks/bulk-orders-test.php
- backend/pages/homepage/get-confirmed-orders.php
- backend/pages/notifications/all-notifications.php
- backend/pages/notifications/test-due-orders.php
- backend/pages/orders/get-orders.php
- backend/pages/orders/order-list.php

### 🟠 Priority 2: Backend Admin Pages (Full Admin Auth)
**Pattern:** `!isset($_SESSION["admin_id"]) || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true || $_SESSION["admin_role"] !== "admin"`
**Replace with:** `!SessionManager::isAdminLoggedIn()`

**Files (3):**
- backend/pages/account/admin-account.php
- backend/pages/account/admin-profile.php
- backend/pages/account/upload-profile-picture.php

### 🟡 Priority 3: Backend Admin Operations
**Pattern:** `!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true`

**Files (37):**
- backend/pages/orders/toggle-auto-status.php
- backend/pages/orders/update-status.php
- backend/pages/orders/view-orders.php
- backend/pages/possible trash/admin-profile.php
- backend/pages/products/add-product.php
- backend/pages/products/apply-global-days.php
- backend/pages/products/delete-product.php
- backend/pages/products/get-global-available-days.php
- backend/pages/products/get-product-images-edit.php
- backend/pages/products/get-product-images.php
- backend/pages/products/get-sdo-quantities.php
- backend/pages/products/manage-additional-images.php
- backend/pages/products/product-list.php
- backend/pages/products/remove-product-image.php
- backend/pages/products/replace-product-image.php
- backend/pages/products/test-sdo-setup.php
- backend/pages/products/update-global-available-days.php
- backend/pages/products/update-product.php
- backend/pages/products/update-sdo-quantities.php
- backend/pages/products/upload-product-image.php
- backend/pages/refund/refund-details.php
- backend/pages/refund/refund-request-lists.php
- backend/pages/transactions/export-transactions.php
- backend/pages/transactions/get-chart-data.php
- backend/pages/transactions/get-transactions.php
- backend/pages/transactions/transactions.php
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

### 🔵 Priority 4: Frontend User Auth (Simple)
**Pattern:** `!isset($_SESSION["user_id"])`
**Replace with:** `!SessionManager::isUserLoggedIn()`

**Files (9):**
- frontend/api/delete-profile-picture.php
- frontend/api/upload-profile-picture.php
- frontend/pages/cart/cart-old.php
- frontend/pages/cart/order-confirmation.php
- frontend/pages/cart/remove-from-cart-sameday.php
- frontend/pages/cart/shopping-cart-preorder.php
- frontend/pages/cart/shopping-cart-sameday.php
- frontend/pages/cart/update-cart-quantity-sameday.php
- frontend/pages/users/email-preferences.php

### 🟢 Priority 5: Frontend User Auth (Full)
**Pattern:** `!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "user"`
**Replace with:** `!SessionManager::isUserLoggedIn()`

**Files (6):**
- frontend/pages/cart/add-to-cart.php
- frontend/pages/cart/get-order-details.php
- frontend/pages/cart/remove-from-cart.php
- frontend/pages/cart/update-cart.php
- frontend/user-includes/user-auth.php

### 🟣 Priority 6: Rider Auth (Special Case)
**Pattern:** `!isset($_SESSION["is_rider"]) && !isset($_SESSION["is_admin"])`
**Note:** May need custom SessionManager method or keep as-is

**Files (2):**
- rider/orders.php
- rider/submit-delivery-proof.php

### 📄 Priority 7: Documentation Files
**Files (2):**
- .kiro/specs/ajax-product-image-management/design.md
- .kiro/specs/automatic-order-status-management/design.md

### ⚠️ Priority 8: Remaining Files
**Files (6):**
- backend/pages/user-page-content/manage-categories.php
- backend/pages/user-page-content/privacy-policy-management.php
- backend/pages/user-page-content/promotions_api.php
- backend/pages/user-page-content/promotions-settings.php
- backend/pages/user-page-content/terms-and-condition-management.php
- backend/pages/user-page-content/update-category.php
- backend/pages/user-page-content/user-content-settings.php

---

## Migration Strategy

### Automated Approach
Create a script to batch-migrate files with the same pattern:

1. **Admin API files** - Simple pattern replacement
2. **User frontend files** - Simple pattern replacement
3. **Complex admin pages** - May need manual review

### Manual Review Required
- Rider authentication files (custom logic)
- Files with multiple session checks
- Files that modify session data

---

## Next Steps

**Option A: Batch Migration**
Migrate all files automatically by category (fastest)

**Option B: Incremental Migration**
Migrate by priority, test each batch (safest)

**Option C: Critical Path Only**
Migrate only actively used files (quickest to production)

---

**Which approach would you like to take?**
