# Password Reset Debugging Instructions

## Current Status
✅ Test pages work correctly (manual-password-test.php)
❌ Actual login fails after password reset

## Step-by-Step Debugging Process

### Step 1: Run Comparison Test
1. Visit `compare-login-test.php`
2. This will test if the password works using the exact same code as the login page
3. Check all tests - they should all PASS

### Step 2: Set a Known Password
1. Visit `manual-password-test.php`
2. Use Tool 1 to set password "TestPassword123" for user ID 16 (Allysa123)
3. Verify all checks pass (should see green checkmarks)

### Step 3: Test Login Manually
1. Go to the actual login page: `/frontend/login/user/login-signup.php`
2. Enter:
   - Username: `Allysa123`
   - Password: `TestPassword123`
3. Try to login

### Step 4: Check Debug Logs
1. Visit `view-debug-logs.php`
2. Filter by "RAW POST DATA" to see what the login form is sending
3. Filter by "LOGIN ATTEMPT DEBUG" to see the verification process
4. Compare the password in the logs with what you typed

### Step 5: Look for Differences

Check the logs for:
- **Password length mismatch**: Raw POST vs what you typed
- **Extra characters**: Check the hex dump of the password
- **Hash mismatch**: Compare hash in login attempt vs hash in database
- **Encoding issues**: Look for unusual characters in hex dump

## Common Issues to Check

### Issue 1: Browser Autofill
- **Symptom**: Password works in test but not in actual login
- **Solution**: Try typing password manually, don't use autofill
- **Test**: Use incognito/private mode

### Issue 2: JavaScript Interference
- **Symptom**: Password is modified before submission
- **Solution**: Check browser console for errors
- **Test**: Disable JavaScript and try again

### Issue 3: Hidden Characters
- **Symptom**: Password length in logs doesn't match what you typed
- **Solution**: Check hex dump in logs for extra bytes
- **Test**: Copy password from a text file instead of typing

### Issue 4: Form Encoding
- **Symptom**: Special characters in password are corrupted
- **Solution**: Check form accept-charset attribute
- **Test**: Use only alphanumeric password

### Issue 5: Session Issues
- **Symptom**: Login redirects but doesn't set session
- **Solution**: Check session_start() is called
- **Test**: Check if other users can login

## Debug Log Filters

Use these filters in `view-debug-logs.php`:

- `RAW POST DATA` - See what the form sends
- `PROCESSED DATA` - See what PHP receives after trim()
- `LOGIN ATTEMPT DEBUG START` - See full login process
- `PASSWORD RESET DEBUG START` - See password reset process
- `CRITICAL` - See only critical errors
- `FAIL` - See all failures

## Files with Enhanced Debugging

1. **frontend/login/user/login-signup.php**
   - Logs raw POST data
   - Logs processed data
   - Logs full verification process

2. **frontend/login/user/forgot-pw-reset.php**
   - Logs password reset process
   - Verifies hash immediately
   - Checks storage integrity

3. **manual-password-test.php**
   - Interactive testing tool
   - Set passwords manually
   - Test login without browser

4. **view-debug-logs.php**
   - Real-time log viewer
   - Syntax highlighting
   - Filtering and grouping

## Next Steps After Finding the Issue

Once you identify the problem from the logs, report:
1. What the logs show (copy relevant lines)
2. What you expected vs what you got
3. Any error messages or warnings

## Quick Test Commands

```bash
# View last 50 lines of error log
tail -50 /path/to/error.log

# Search for login attempts
grep "LOGIN ATTEMPT" /path/to/error.log

# Search for password resets
grep "PASSWORD RESET" /path/to/error.log
```

## Emergency Reset

If you need to manually reset a password in the database:

```sql
-- Generate hash in PHP first
-- password_hash("YourPassword", PASSWORD_BCRYPT, ['cost' => 10])

UPDATE users 
SET password = '$2y$10$YOUR_GENERATED_HASH_HERE'
WHERE id = 16;
```

## Contact Points

If issue persists, provide:
1. Screenshot of `compare-login-test.php` results
2. Relevant lines from `view-debug-logs.php`
3. Browser console errors (F12 → Console tab)
4. Network tab showing the login POST request
