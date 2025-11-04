# Implementation Plan

- [x] 1. Create shared authentication helper file



  - Create new file `backend/pages/admin-includes/auth-helpers.php`
  - Implement `hashPassword($password)` function that trims input, generates bcrypt hash with cost 10, and verifies the hash immediately
  - Implement `verifyPassword($password, $hash)` function that trims input and uses password_verify()
  - Add minimal error logging (only for hash generation failures)



  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2_

- [ ] 2. Update login-signup.php to use shared password functions
  - Add `require_once` for auth-helpers.php at the top of the file
  - In signup flow: Replace `password_hash($password, PASSWORD_BCRYPT, ['cost' => 10])` with `hashPassword($password)`



  - In signup flow: Handle the return value from hashPassword() (check success, use hash, handle errors)
  - In login flow: Replace `password_verify($password, $user["password"])` with `verifyPassword($password, $user["password"])`
  - Keep all existing validation, transaction, email, and session logic unchanged
  - _Requirements: 2.3, 3.1, 6.1, 6.3, 6.4_

- [ ] 3. Remove excessive debugging from login-signup.php
  - Remove all "=== RAW POST DATA ===" logging sections from login handler



  - Remove all "=== PROCESSED DATA ===" logging sections from login handler
  - Remove all "=== LOGIN ATTEMPT DEBUG START ===" sections from login handler
  - Remove hex dump logging (bin2hex calls on passwords and usernames)
  - Remove character-by-character password analysis



  - Remove detailed password_verify() debugging logs
  - Keep only simple error_log() for critical failures (database errors)
  - _Requirements: 5.3, 5.4, 6.2, 7.2_

- [ ] 4. Update forgot-pw-reset.php to use shared password functions
  - Add `require_once` for auth-helpers.php at the top of the file
  - Replace `password_hash($password, PASSWORD_BCRYPT, ['cost' => 10])` with `hashPassword($password)`



  - Handle the return value from hashPassword() (check success, use hash, handle errors)
  - Keep all existing token validation, form display, and database update logic unchanged
  - _Requirements: 1.1, 1.2, 1.3, 2.3, 3.1, 3.2_

- [ ] 5. Remove excessive debugging from forgot-pw-reset.php
  - Remove all "=== PASSWORD RESET DEBUG START ===" sections
  - Remove all "RAW password" and "TRIMMED password" logging with hex dumps
  - Remove character-by-character password analysis (implode/array_map/ord/str_split)
  - Remove "BEFORE bind_param" and "AFTER bind_param" logging
  - Remove hash comparison verification loops after database update
  - Remove "CRITICAL" warnings and regenerated hash testing
  - Keep only simple error_log() for critical failures (hash generation errors, database errors)
  - _Requirements: 1.3, 2.5, 4.3_

- [ ] 6. Test authentication flows end-to-end
  - Test signup with new password → verify hash is stored correctly → login with that password
  - Test password reset with valid token → verify new password works for login
  - Test login with correct password → verify session is created
  - Test login with incorrect password → verify appropriate error message
  - Verify no sensitive data appears in error logs
  - Verify hash format is consistent (bcrypt $2y$10$...) across all flows
  - _Requirements: 1.5, 3.5, 5.1, 5.2, 5.5, 6.5, 7.4_
