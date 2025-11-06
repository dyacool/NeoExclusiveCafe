# Implementation Plan

- [ ] 1. Fix error logging path and add helper functions
  - Update error_log path in process-payment.php from `../../logs/php_errors.log` to `../../../logs/php_errors.log`
  - Add log directory existence check and creation if needed
  - Create `respondWithError()` helper function that cleans output buffer, sets HTTP status, returns JSON error, and exits
  - Create `respondWithSuccess()` helper function that cleans output buffer, sets HTTP 200, returns JSON success, and exits
  - Move output buffering start to top of file (before any output)
  - Move Content-Type header to top of file (after output buffering)
  - Test by triggering an error and verifying it logs to correct file
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 2. Add database connection validation
  - Wrap database.php require in try-catch block
  - Add validation that $conn variable exists after include
  - Add validation that $conn is instanceof mysqli
  - Add $conn->ping() check to verify connection is active
  - Log success/failure of each validation step
  - Use respondWithError() with HTTP 500 if any validation fails
  - Test by temporarily breaking database connection and verifying error response
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 3. Add PayMongo configuration validation
  - Wrap paymongo-config.php require in try-catch block
  - Add class_exists('PayMongoAPI') check after include
  - Log success/failure of configuration loading
  - Use respondWithError() with HTTP 500 if validation fails
  - Test by temporarily removing PayMongoAPI class and verifying error response
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 4. Enhance input validation with descriptive errors
  - Add HTTP method validation (must be POST) with respondWithError() for non-POST
  - Add JSON parsing with json_last_error() check and descriptive error message
  - Create array of required fields and loop to check each one
  - Build list of missing fields and return single error message with all missing fields
  - Add payment method validation against whitelist ['gcash', 'paymaya', 'card']
  - Add amount validation (must be > 0)
  - Add order_data structure validation for required fields
  - Log validation success after all checks pass
  - Test each validation scenario and verify correct error messages
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 5. Add comprehensive logging to payment creation flow
  - Add log entry at start of payment processing with timestamp
  - Log payment method, amount, and order type after extraction
  - Log PayMongo API initialization success
  - Log order ID generation
  - Log metadata preparation
  - Log return URL generation for GCash/PayMaya
  - Log before calling createSource/createPaymentIntent
  - Log PayMongo response (success or error)
  - Log session data storage success/failure
  - Add session write verification check
  - Log final response before sending
  - _Requirements: 1.4, 1.5, 3.3, 4.4_

- [ ] 6. Enhance PayMongo error handling
  - Add detailed error logging when PayMongo returns error
  - Include full PayMongo error response in logs
  - Return user-friendly error message (not full API response)
  - Add response structure validation (check for required fields)
  - Use respondWithError() with HTTP 400 for PayMongo errors
  - Test with invalid API credentials to verify error handling
  - _Requirements: 2.2, 7.2, 7.3_

- [ ] 7. Add session management validation
  - Add try-catch around session initialization
  - Log session initialization success/failure
  - After storing pending_payment, verify it exists in $_SESSION
  - Use respondWithError() with HTTP 500 if session write fails
  - Add created_at timestamp to session data
  - Test session storage and verify all fields present
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 8. Test all payment methods end-to-end
  - Test GCash payment: submit checkout form, verify JSON response, verify redirect URL, verify session data
  - Test PayMaya payment: submit checkout form, verify JSON response, verify redirect URL, verify session data
  - Test card payment: submit checkout form, verify JSON response, verify client_secret returned, verify session data
  - Verify no console errors during checkout
  - Verify error logs show success checkpoints
  - Monitor network tab to confirm HTTP 200 responses
  - _Requirements: 2.1, 5.3, 6.4, 6.5_

- [ ] 9. Test error scenarios
  - Test missing payment_method field and verify HTTP 400 with descriptive error
  - Test invalid payment_method value and verify HTTP 400 with valid options listed
  - Test amount = 0 and verify HTTP 400 error
  - Test missing order_data and verify HTTP 400 error
  - Test malformed JSON and verify HTTP 400 with JSON parse error
  - Test with database disconnected and verify HTTP 500 with database error
  - Verify all errors logged to logs/php_errors.log
  - Verify all error responses are valid JSON
  - _Requirements: 2.2, 2.3, 2.4, 2.5, 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 10. Verify JSON response integrity
  - Test that all responses have Content-Type: application/json header
  - Verify no HTML or whitespace before JSON in responses
  - Test that error responses are valid JSON (can be parsed)
  - Test that success responses are valid JSON
  - Use browser network tab to inspect raw responses
  - Verify output buffering prevents accidental output
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
