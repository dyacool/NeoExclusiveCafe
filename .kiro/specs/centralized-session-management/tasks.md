# Implementation Plan

- [x] 1. Create SessionManager class



  - Create `includes/session-manager.php` with complete SessionManager class implementation
  - Implement all authentication check methods: `isUserLoggedIn()`, `isAdminLoggedIn()`, `isPreviewMode()`
  - Implement all data retrieval methods: `getUserId()`, `getUserData()`, `getAdminData()`, `getRole()`
  - Implement session control methods: `requireUserLogin()`, `requireAdminLogin()`, `destroySession()`
  - Implement private helper method: `ensureSessionStarted()`
  - Add comprehensive PHPDoc comments for all public methods with usage examples
  - Add inline comments explaining validation logic





  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 3.5, 5.1, 5.2, 5.3, 5.4, 5.5, 6.1, 6.3, 6.5_

- [x] 2. Refactor common include files


  - [ ] 2.1 Refactor `frontend/user-includes/user-header.php`
    - Replace direct session checks with SessionManager methods
    - Update `$is_preview_mode`, `$is_user_logged_in`, `$is_admin_logged_in` variables


    - _Requirements: 4.1, 4.2, 4.3_
  





  - [ ] 2.2 Refactor `frontend/user-includes/preview-mode.php`
    - Replace direct session checks with SessionManager methods
    - Update authentication logic to use `isUserLoggedIn()` and `isAdminLoggedIn()`


    - _Requirements: 4.1, 4.2, 4.3_
  
  - [x] 2.3 Refactor `frontend/user-includes/navbar/customer-navigation.php`

    - Replace session checks with SessionManager methods

    - Update user data retrieval to use `getUserData()`
    - Fix incorrect `$_SESSION['admin_id']` check to use `isAdminLoggedIn()`

    - _Requirements: 4.1, 4.2, 4.3, 4.4_


- [x] 3. Refactor profile pages

  - [-] 3.1 Refactor `frontend/pages/profile/profile.php`






    - Replace authentication check with `SessionManager::requireUserLogin()`
    - Replace `$_SESSION['user_id']` access with `SessionManager::getUserId()`
    - _Requirements: 4.2, 4.5_


  
  - [x] 3.2 Refactor `frontend/pages/profile/account-settings.php`


    - Replace authentication check with `SessionManager::requireUserLogin()`
    - Replace direct session variable access with `SessionManager::getUserId()`
    - _Requirements: 4.2, 4.5_

  

  - [x] 3.3 Refactor `frontend/pages/profile/my-orders.php`


    - Replace authentication check with `SessionManager::requireUserLogin()`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`







    - _Requirements: 4.2, 4.5_
  
  - [x] 3.4 Refactor `frontend/pages/profile/saved-posts.php`


    - Replace authentication check with `SessionManager::requireUserLogin()`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2, 4.5_

  
  - [ ] 3.5 Refactor `frontend/pages/profile/ajax-pagination.php`
    - Replace authentication check with `SessionManager::isUserLoggedIn()`

    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2, 4.5_


- [ ] 4. Refactor cart and checkout pages
  - [ ] 4.1 Refactor `frontend/pages/cart/checkout.php`
    - Replace manual authentication check with `SessionManager::requireUserLogin()`

    - Update session variable access to use SessionManager methods
    - _Requirements: 4.2, 4.5_
  

  - [ ] 4.2 Refactor `frontend/pages/cart/availtoday-checkout.php`
    - Replace manual authentication check with `SessionManager::requireUserLogin()`
    - _Requirements: 4.2, 4.5_

  
  - [ ] 4.3 Refactor `frontend/pages/cart/process-availtoday-checkout.php`
    - Replace authentication check with `SessionManager::requireUserLogin()`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2, 4.5_
  
  - [ ] 4.4 Refactor `frontend/pages/cart/availtoday-order-confirmation.php`
    - Replace authentication check with `SessionManager::requireUserLogin()`
    - _Requirements: 4.2, 4.5_
  
  - [ ] 4.5 Refactor `frontend/pages/cart/get-cart-count.php`
    - Replace authentication check with `SessionManager::isUserLoggedIn()`
    - _Requirements: 4.2_
  
  - [ ] 4.6 Refactor `frontend/pages/cart/process-payment.php`
    - Update session checks to use SessionManager methods where applicable
    - _Requirements: 4.2_

- [ ] 5. Refactor notification pages
  - [ ] 5.1 Refactor `frontend/pages/notifications/notifications.php`
    - Replace `$_SESSION['user_id']` access with `SessionManager::getUserId()`
    - Add `SessionManager::requireUserLogin()` for protection
    - _Requirements: 4.2, 4.5_
  
  - [ ] 5.2 Refactor `frontend/pages/notifications/messages.php`
    - Replace authentication check with `SessionManager::requireUserLogin()`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2, 4.5_
  
  - [ ] 5.3 Refactor `frontend/pages/notifications/notif.php`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2_
  
  - [ ] 5.4 Refactor `frontend/pages/notifications/fetch-notif.php`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2_
  
  - [ ] 5.5 Refactor `frontend/pages/notifications/mark-notification-read.php`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2_
  
  - [ ] 5.6 Refactor `frontend/pages/notifications/mark-notif.php`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2_
  
  - [ ] 5.7 Refactor `frontend/pages/notifications/mark-all-notifications-read.php`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2_
  
  - [ ] 5.8 Refactor `frontend/pages/notifications/delete-notification.php`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2_

- [x] 6. Refactor product pages



  - [x] 6.1 Refactor `frontend/pages/products/product-dashboard.php`

    - Replace `isset($_SESSION['user_id'])` check with `SessionManager::isUserLoggedIn()`
    - _Requirements: 4.2_
  
  - [x] 6.2 Refactor `frontend/pages/products/weekly-product.php`


    - Replace preview mode check with `SessionManager::isPreviewMode()`
    - _Requirements: 4.3_
  

  - [x] 6.3 Refactor `frontend/pages/products/availtoday-cart-api.php`

    - Replace authentication checks with `SessionManager::isUserLoggedIn()` and `SessionManager::requireUserLogin()`
    - Replace `$_SESSION['user_id']` with `SessionManager::getUserId()`
    - _Requirements: 4.2, 4.5_



- [x] 7. Refactor home and search pages

  - [x] 7.1 Refactor `frontend/pages/home/user-dashboard.php`

    - Replace `isset($_SESSION['user_id'])` check with `SessionManager::isUserLoggedIn()`
    - _Requirements: 4.2_
  
  - [x] 7.2 Refactor `frontend/search/search-results.php`


    - Replace `isset($_SESSION['user_id'])` check with `SessionManager::isUserLoggedIn()`
    - _Requirements: 4.2_



- [x] 8. Refactor blog pages

  - [x] 8.1 Refactor `frontend/pages/blog/blog-dashboard.php`

    - Replace preview mode check with `SessionManager::isPreviewMode()`
    - _Requirements: 4.3_
  

  - [x] 8.2 Refactor `frontend/possible trash/view-blog.php`

    - Replace `isset($_SESSION['user_id'])` checks with `SessionManager::isUserLoggedIn()`
    - Replace `$_SESSION['user_id']` access with `SessionManager::getUserId()`
    - _Requirements: 4.2_

- [x] 9. Create migration documentation


  - Create `includes/SESSION_MANAGER_GUIDE.md` with usage examples and migration patterns
  - Include before/after code examples for common scenarios
  - Document all public methods with parameters and return values
  - Add troubleshooting section for common issues
  - _Requirements: 6.2, 6.4_


- [x] 10. Integration testing and validation

  - Test user login flow and verify SessionManager methods work correctly
  - Test admin login flow and verify admin-specific methods
  - Test preview mode for non-logged-in visitors
  - Verify protected pages redirect correctly when not authenticated
  - Verify navbar displays correct state for user/admin/guest
  - Check cart functionality for logged-in users
  - Verify profile pages display correct user data
  - Check PHP error logs for any warnings or errors
  - _Requirements: All requirements_
