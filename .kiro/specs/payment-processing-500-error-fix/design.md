# Design Document

## Overview

This design addresses the critical 500 Internal Server Error in `process-payment.php` that prevents customers from initiating online payments. The solution involves correcting file paths, enhancing error handling, validating dependencies, and ensuring clean JSON responses. The design focuses on fail-fast validation, comprehensive logging, and graceful error recovery to provide a reliable payment initiation experience.

## Architecture

### Payment Processing Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Payment Initiation Flow                   │
├─────────────────────────────────────────────────────────────┤
│  Checkout Page → process-payment.php → Validation           │
│                          ↓                                   │
│                  Database Connection Check                   │
│                          ↓                                   │
│                  PayMongo Config Check                       │
│                          ↓                                   │
│                  Create Payment Source/Intent                │
│                          ↓                                   │
│                  Store in Session                            │
│                          ↓                                   │
│                  Return JSON Response                        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    Error Handling Flow                       │
├─────────────────────────────────────────────────────────────┤
│  Error Occurs → Log to Error File → Clean Output Buffer     │
│                          ↓                                   │
│                  Return JSON Error (HTTP 400)                │
│                          ↓                                   │
│                  Client Displays Error Message               │
└─────────────────────────────────────────────────────────────┘
```

### System Components

1. **Payment Processing Handler** (`frontend/pages/cart/process-payment.php`)
   - Entry point for payment initiation
   - Validates dependencies and input
   - Creates PayMongo payment sources/intents
   - Stores session data
   - Returns JSON responses

2. **Database Connection** (`backend/pages/admin-includes/database.php`)
   - Provides mysqli connection object
   - Must be validated before use

3. **PayMongo Configuration** (`frontend/pages/cart/paymongo-config.php`)
   - Defines PayMongoAPI class
   - Provides API credentials and methods

4. **Error Log** (`logs/php_errors.log`)
   - Centralized error logging
   - Must use correct relative path

5. **Session Storage** (PHP $_SESSION)
   - Stores pending payment data
   - Persists between payment initiation and completion

## Components and Interfaces

### 1. Error Logging Path Correction

**Current Issue:**
```php
// INCORRECT - Wrong relative path from frontend/pages/cart/
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');
```

**Design Solution:**
```php
// CORRECT - Proper relative path from frontend/pages/cart/
ini_set('error_log', __DIR__ . '/../../../logs/php_errors.log');

// Verify log directory exists and is writable
$log_dir = __DIR__ . '/../../../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Test write permissions
$test_log = $log_dir . '/php_errors.log';
if (!is_writable(dirname($test_log))) {
    error_log("WARNING: Log directory not writable: $log_dir");
}
```

**Path Verification:**
- From: `frontend/pages/cart/process-payment.php`
- To: `logs/php_errors.log`
- Correct path: `../../../logs/php_errors.log`
  - `../` → pages
  - `../../` → frontend
  - `../../../` → root (where logs/ is located)

### 2. Dependency Validation

**Design Pattern: Fail-Fast Validation**

```php
<?php
/**
 * PayMongo Payment Processing Handler
 * Handles payment creation and processing for both regular and availtoday orders
 */

// Step 1: Configure error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/php_errors.log');

// Step 2: Start output buffering immediately
ob_start();

// Step 3: Set JSON content type early
header('Content-Type: application/json');

// Step 4: Initialize session
try {
    $session_domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    session_set_cookie_params([
        'lifetime' => 0,
        'httponly' => true,
        'samesite' => 'Strict',
        'domain' => $session_domain
    ]);
    
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    error_log("[PROCESS-PAYMENT] Session initialized successfully");
} catch (Exception $e) {
    error_log("[PROCESS-PAYMENT] Session initialization failed: " . $e->getMessage());
    respondWithError("Session initialization failed", 500);
}

// Step 5: Validate database connection
try {
    require_once '../../../backend/pages/admin-includes/database.php';
    
    if (!isset($conn)) {
        throw new Exception("Database connection variable not set");
    }
    
    if (!($conn instanceof mysqli)) {
        throw new Exception("Database connection is not a valid mysqli object");
    }
    
    if (!$conn->ping()) {
        throw new Exception("Database connection is not active");
    }
    
    error_log("[PROCESS-PAYMENT] ✓ Database connection validated");
} catch (Exception $e) {
    error_log("[PROCESS-PAYMENT] ✗ Database validation failed: " . $e->getMessage());
    respondWithError("Database connection failed: " . $e->getMessage(), 500);
}

// Step 6: Validate PayMongo configuration
try {
    require_once 'paymongo-config.php';
    
    if (!class_exists('PayMongoAPI')) {
        throw new Exception("PayMongoAPI class not found");
    }
    
    error_log("[PROCESS-PAYMENT] ✓ PayMongo configuration validated");
} catch (Exception $e) {
    error_log("[PROCESS-PAYMENT] ✗ PayMongo configuration failed: " . $e->getMessage());
    respondWithError("PayMongo configuration error: " . $e->getMessage(), 500);
}

/**
 * Helper function to send error response and exit
 */
function respondWithError($message, $httpCode = 400) {
    // Clean any output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'message' => $message
    ]);
    
    // Flush and end output buffering
    ob_end_flush();
    exit();
}

/**
 * Helper function to send success response and exit
 */
function respondWithSuccess($data) {
    // Clean any output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code(200);
    echo json_encode(array_merge(['success' => true], $data));
    
    // Flush and end output buffering
    ob_end_flush();
    exit();
}
```

### 3. Request Validation

**Design Pattern: Input Validation with Descriptive Errors**

```php
// Validate HTTP method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("[PROCESS-PAYMENT] Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    respondWithError("Method not allowed. Use POST.", 405);
}

// Parse JSON input
$raw_input = file_get_contents('php://input');
error_log("[PROCESS-PAYMENT] Raw input received: " . substr($raw_input, 0, 200));

$input = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("[PROCESS-PAYMENT] JSON decode error: " . json_last_error_msg());
    respondWithError("Invalid JSON input: " . json_last_error_msg(), 400);
}

// Validate required fields
$required_fields = ['payment_method', 'amount', 'order_data'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $error_msg = "Missing required fields: " . implode(', ', $missing_fields);
    error_log("[PROCESS-PAYMENT] Validation failed: $error_msg");
    respondWithError($error_msg, 400);
}

// Extract and validate data
$payment_method = $input['payment_method'];
$order_type = $input['order_type'] ?? 'regular';
$order_data = $input['order_data'];
$amount = floatval($input['amount']);

// Validate payment method
$valid_methods = ['gcash', 'paymaya', 'card'];
if (!in_array($payment_method, $valid_methods)) {
    error_log("[PROCESS-PAYMENT] Invalid payment method: $payment_method");
    respondWithError("Invalid payment method. Must be one of: " . implode(', ', $valid_methods), 400);
}

// Validate amount
if ($amount <= 0) {
    error_log("[PROCESS-PAYMENT] Invalid amount: $amount");
    respondWithError("Invalid amount. Must be greater than 0.", 400);
}

// Validate order data structure
$required_order_fields = ['first_name', 'last_name', 'phone', 'shipping_method'];
$missing_order_fields = [];

foreach ($required_order_fields as $field) {
    if (!isset($order_data[$field]) || empty($order_data[$field])) {
        $missing_order_fields[] = $field;
    }
}

if (!empty($missing_order_fields)) {
    $error_msg = "Missing required order fields: " . implode(', ', $missing_order_fields);
    error_log("[PROCESS-PAYMENT] Order data validation failed: $error_msg");
    respondWithError($error_msg, 400);
}

error_log("[PROCESS-PAYMENT] ✓ Input validation passed");
error_log("[PROCESS-PAYMENT] Payment method: $payment_method, Amount: $amount, Order type: $order_type");
```

### 4. PayMongo API Integration

**Design Pattern: Try-Catch with Detailed Error Logging**

```php
try {
    // Initialize PayMongo API
    $paymongo = new PayMongoAPI();
    error_log("[PROCESS-PAYMENT] ✓ PayMongo API initialized");
    
    // Generate order ID (temporary - will be replaced with actual DB insert)
    $order_id = 'ORD-' . time() . '-' . rand(1000, 9999);
    error_log("[PROCESS-PAYMENT] Generated order ID: $order_id");
    
    // Prepare metadata (PayMongo requires flat key-value pairs)
    $metadata = [
        'order_id' => (string)$order_id,
        'order_type' => (string)$order_type,
        'customer_name' => (string)($order_data['first_name'] . ' ' . $order_data['last_name']),
        'customer_email' => (string)($order_data['email'] ?? ''),
        'phone' => (string)($order_data['phone'] ?? ''),
        'shipping_method' => (string)($order_data['shipping_method'] ?? '')
    ];
    
    $description = "NeoCafe Order #$order_id - " . ucfirst($order_type);
    
    // Handle different payment methods
    if (in_array($payment_method, ['gcash', 'paymaya'])) {
        error_log("[PROCESS-PAYMENT] Creating payment source for $payment_method");
        
        // Generate return URL
        $return_url = generateReturnURL($order_type);
        error_log("[PROCESS-PAYMENT] Return URL: $return_url");
        
        // Create payment source
        $result = $paymongo->createSource(
            $payment_method,
            $amount,
            'PHP',
            $return_url,
            $metadata
        );
        
        // Check for errors
        if (isset($result['error'])) {
            $error_details = json_encode($result);
            error_log("[PROCESS-PAYMENT] ✗ PayMongo source creation failed: $error_details");
            respondWithError("Payment gateway error: " . ($result['error'] ?? 'Unknown error'), 400);
        }
        
        // Validate response structure
        if (!isset($result['data']['id']) || !isset($result['data']['attributes']['redirect']['checkout_url'])) {
            error_log("[PROCESS-PAYMENT] ✗ Invalid PayMongo response structure: " . json_encode($result));
            respondWithError("Invalid payment gateway response", 500);
        }
        
        $source_id = $result['data']['id'];
        $checkout_url = $result['data']['attributes']['redirect']['checkout_url'];
        
        error_log("[PROCESS-PAYMENT] ✓ Payment source created: $source_id");
        
        // Store in session
        $_SESSION['pending_payment'] = [
            'source_id' => $source_id,
            'order_id' => $order_id,
            'order_type' => $order_type,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'order_data' => $order_data,
            'created_at' => time()
        ];
        
        // Verify session was written
        if (!isset($_SESSION['pending_payment'])) {
            error_log("[PROCESS-PAYMENT] ✗ Failed to write to session");
            respondWithError("Session storage failed", 500);
        }
        
        error_log("[PROCESS-PAYMENT] ✓ Session data stored successfully");
        
        // Return success response
        respondWithSuccess([
            'payment_type' => 'source',
            'payment_url' => $checkout_url,
            'source_id' => $source_id
        ]);
        
    } else if ($payment_method === 'card') {
        error_log("[PROCESS-PAYMENT] Creating payment intent for card");
        
        // Create payment intent
        $result = $paymongo->createPaymentIntent(
            $amount,
            'PHP',
            $description,
            $metadata
        );
        
        // Check for errors
        if (isset($result['error'])) {
            $error_details = json_encode($result);
            error_log("[PROCESS-PAYMENT] ✗ PayMongo intent creation failed: $error_details");
            respondWithError("Payment gateway error: " . ($result['error'] ?? 'Unknown error'), 400);
        }
        
        // Validate response structure
        if (!isset($result['data']['id']) || !isset($result['data']['attributes']['client_key'])) {
            error_log("[PROCESS-PAYMENT] ✗ Invalid PayMongo response structure: " . json_encode($result));
            respondWithError("Invalid payment gateway response", 500);
        }
        
        $payment_intent_id = $result['data']['id'];
        $client_key = $result['data']['attributes']['client_key'];
        
        error_log("[PROCESS-PAYMENT] ✓ Payment intent created: $payment_intent_id");
        
        // Store in session
        $_SESSION['pending_payment'] = [
            'payment_intent_id' => $payment_intent_id,
            'client_secret' => $client_key,
            'order_id' => $order_id,
            'order_type' => $order_type,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'order_data' => $order_data,
            'created_at' => time()
        ];
        
        // Verify session was written
        if (!isset($_SESSION['pending_payment'])) {
            error_log("[PROCESS-PAYMENT] ✗ Failed to write to session");
            respondWithError("Session storage failed", 500);
        }
        
        error_log("[PROCESS-PAYMENT] ✓ Session data stored successfully");
        
        // Return success response
        respondWithSuccess([
            'payment_type' => 'payment_intent',
            'payment_intent_id' => $payment_intent_id,
            'client_secret' => $client_key,
            'public_key' => PAYMONGO_PUBLIC_KEY // From config
        ]);
    }
    
} catch (Exception $e) {
    error_log("[PROCESS-PAYMENT] ✗ Exception: " . $e->getMessage());
    error_log("[PROCESS-PAYMENT] Stack trace: " . $e->getTraceAsString());
    respondWithError("Payment processing failed: " . $e->getMessage(), 500);
}

// This should never be reached due to respondWithSuccess/respondWithError exits
error_log("[PROCESS-PAYMENT] WARNING: Reached end of script without response");
respondWithError("Unexpected error: No response generated", 500);
```

### 5. Helper Functions

**Return URL Generator:**
```php
/**
 * Generate return URL based on order type
 */
function generateReturnURL($order_type) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = "$protocol://$host";
    
    if ($order_type === 'availtoday') {
        return "$base_url/frontend/pages/cart/payment-return.php?type=availtoday";
    } else {
        return "$base_url/frontend/pages/cart/payment-return.php?type=regular";
    }
}
```

## Data Models

### Session Data Structure

```php
$_SESSION['pending_payment'] = [
    // For GCash/PayMaya
    'source_id' => 'src_xxxxx',
    
    // For Card
    'payment_intent_id' => 'pi_xxxxx',
    'client_secret' => 'pi_xxxxx_client_xxxxx',
    
    // Common fields
    'order_id' => 'ORD-1730000000-1234',
    'order_type' => 'regular', // or 'availtoday'
    'amount' => 500.00,
    'payment_method' => 'gcash', // or 'paymaya', 'card'
    'order_data' => [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '09123456789',
        'shipping_method' => 'delivery',
        // ... other order fields
    ],
    'created_at' => 1730000000 // Unix timestamp
];
```

### Success Response Structure

```json
{
    "success": true,
    "payment_type": "source",
    "payment_url": "https://checkout.paymongo.com/...",
    "source_id": "src_xxxxx"
}
```

```json
{
    "success": true,
    "payment_type": "payment_intent",
    "payment_intent_id": "pi_xxxxx",
    "client_secret": "pi_xxxxx_client_xxxxx",
    "public_key": "pk_test_xxxxx"
}
```

### Error Response Structure

```json
{
    "success": false,
    "error": "Missing required fields: payment_method, amount",
    "message": "Missing required fields: payment_method, amount"
}
```

## Error Handling

### Error Categories and HTTP Status Codes

1. **Validation Errors (400 Bad Request)**
   - Missing required fields
   - Invalid payment method
   - Invalid amount
   - Invalid JSON input

2. **Configuration Errors (500 Internal Server Error)**
   - Database connection failed
   - PayMongo configuration missing
   - Session initialization failed

3. **Payment Gateway Errors (400 Bad Request)**
   - PayMongo API errors
   - Invalid payment response
   - Payment creation failed

4. **Method Errors (405 Method Not Allowed)**
   - Non-POST requests

### Error Logging Strategy

**Log Levels:**
- `✓` - Success checkpoints
- `✗` - Error conditions
- `WARNING` - Non-critical issues

**Log Format:**
```
[PROCESS-PAYMENT] <status> <message>
```

**Examples:**
```
[PROCESS-PAYMENT] ✓ Database connection validated
[PROCESS-PAYMENT] ✗ PayMongo source creation failed: {"error": "Invalid API key"}
[PROCESS-PAYMENT] WARNING: Reached end of script without response
```

## Testing Strategy

### 1. Path Correction Tests

**Test Cases:**
1. **Error Log Creation**
   - Delete logs/php_errors.log
   - Trigger an error in process-payment.php
   - Verify logs/php_errors.log is created
   - Verify error is logged correctly

2. **Log Directory Creation**
   - Delete logs/ directory
   - Trigger an error in process-payment.php
   - Verify logs/ directory is created
   - Verify error is logged correctly

### 2. Dependency Validation Tests

**Test Cases:**
1. **Database Connection Failure**
   - Temporarily break database.php
   - Send payment request
   - Verify HTTP 500 response
   - Verify error message indicates database failure
   - Verify error logged

2. **PayMongo Config Missing**
   - Temporarily rename paymongo-config.php
   - Send payment request
   - Verify HTTP 500 response
   - Verify error message indicates config missing
   - Verify error logged

3. **PayMongoAPI Class Missing**
   - Temporarily remove class definition
   - Send payment request
   - Verify HTTP 500 response
   - Verify error message indicates class not found

### 3. Input Validation Tests

**Test Cases:**
1. **Missing Payment Method**
   - Send request without payment_method
   - Verify HTTP 400 response
   - Verify error message lists missing field

2. **Invalid Payment Method**
   - Send request with payment_method: 'invalid'
   - Verify HTTP 400 response
   - Verify error message lists valid methods

3. **Invalid Amount**
   - Send request with amount: 0
   - Verify HTTP 400 response
   - Verify error message indicates invalid amount

4. **Missing Order Data**
   - Send request without order_data
   - Verify HTTP 400 response
   - Verify error message indicates missing order data

5. **Invalid JSON**
   - Send malformed JSON
   - Verify HTTP 400 response
   - Verify error message indicates JSON parse error

### 4. Payment Creation Tests

**Test Cases:**
1. **Successful GCash Payment**
   - Send valid GCash payment request
   - Verify HTTP 200 response
   - Verify response contains payment_url and source_id
   - Verify session data stored correctly
   - Verify success logged

2. **Successful PayMaya Payment**
   - Send valid PayMaya payment request
   - Verify HTTP 200 response
   - Verify response contains payment_url and source_id
   - Verify session data stored correctly

3. **Successful Card Payment**
   - Send valid card payment request
   - Verify HTTP 200 response
   - Verify response contains client_secret and payment_intent_id
   - Verify session data stored correctly

4. **PayMongo API Error**
   - Use invalid API credentials
   - Send payment request
   - Verify HTTP 400 response
   - Verify error message includes PayMongo error details
   - Verify error logged

### 5. Session Management Tests

**Test Cases:**
1. **Session Data Persistence**
   - Create payment
   - Verify $_SESSION['pending_payment'] exists
   - Verify all required fields present
   - Verify data types correct

2. **Session Write Failure**
   - Simulate session write failure
   - Verify HTTP 500 response
   - Verify error message indicates session failure

### 6. JSON Response Tests

**Test Cases:**
1. **Clean JSON Output**
   - Send valid request
   - Verify response is valid JSON
   - Verify no HTML or whitespace before JSON
   - Verify Content-Type header is application/json

2. **Error JSON Output**
   - Trigger various errors
   - Verify all error responses are valid JSON
   - Verify no HTML or warnings in response

### 7. Integration Tests

**Test Cases:**
1. **End-to-End Payment Flow**
   - Fill checkout form
   - Submit with GCash payment
   - Verify no console errors
   - Verify redirect to PayMongo
   - Complete payment
   - Verify redirect to payment-return.php
   - Verify order created

2. **Multiple Payment Methods**
   - Test GCash, PayMaya, and card
   - Verify all work correctly
   - Verify correct response structure for each

## Performance Considerations

- **Output Buffering**: Minimal overhead, necessary for clean JSON responses
- **Error Logging**: Asynchronous, does not block request processing
- **Session Storage**: Fast in-memory operation
- **Validation**: Fail-fast approach minimizes wasted processing

## Security Considerations

- **Input Validation**: All user input validated before processing
- **Error Messages**: Generic messages to users, detailed logs for developers
- **Session Security**: HTTPOnly, SameSite=Strict, domain-specific cookies
- **API Credentials**: Stored in config file, never exposed in responses
- **SQL Injection**: Not applicable (no database queries in this file)
- **XSS**: Not applicable (JSON responses only, no HTML output)

## Implementation Notes

### Priority Order
1. **Fix error logging path** (Critical - enables debugging)
2. **Add dependency validation** (Critical - prevents 500 errors)
3. **Enhance input validation** (High - improves error messages)
4. **Add comprehensive logging** (High - aids debugging)
5. **Test all payment methods** (High - ensures functionality)

### Dependencies
- PHP 7.4+ (for typed properties and null coalescing)
- mysqli extension
- JSON extension
- Session support
- PayMongo API credentials

### Backward Compatibility
- Maintains existing API contract (same request/response structure)
- Session data structure unchanged
- Return URLs unchanged
- No breaking changes to client-side code

### Rollback Plan
- Keep backup of original process-payment.php
- If issues arise, restore original file
- Monitor error logs for new issues
- Test thoroughly in development before production deployment
