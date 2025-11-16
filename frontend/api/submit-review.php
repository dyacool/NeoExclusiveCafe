<?php
// Start session first before any output
if (session_status() === PHP_SESSION_NONE) {
    // Fix Windows session path permission issues (same as admin database.php)
    $session_path = sys_get_temp_dir();
    if (is_writable($session_path)) {
        session_save_path($session_path);
    }
    session_start();
}

// Suppress error display and ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any accidental output
ob_start();

try {
    require_once __DIR__ . '/../../includes/session-manager.php';
    require_once __DIR__ . '/../../backend/pages/admin-includes/database.php';
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
    exit;
}

// Clear any output that might have been generated
ob_end_clean();

// Set JSON header after clearing output buffer
header('Content-Type: application/json');

// Wrap everything in try-catch for error handling
try {
    // Debug session state
    error_log("Review API - Session ID: " . session_id());
    error_log("Review API - Session data: " . print_r($_SESSION, true));
    error_log("Review API - User logged in check: " . (SessionManager::isUserLoggedIn() ? 'true' : 'false'));
    
    // Check if user is logged in
    if (!SessionManager::isUserLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Please log in to submit a review',
            'debug' => [
                'session_id' => session_id(),
                'has_user_id' => isset($_SESSION['user_id']),
                'has_user_role' => isset($_SESSION['user_role']),
                'user_role_value' => $_SESSION['user_role'] ?? 'not set'
            ]
        ]);
        exit;
    }

    $user_id = SessionManager::getUserId();
    
    // Get database connection from admin-includes (already loaded)
    global $conn;
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Enable exception mode for mysqli to catch errors
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['product_id']) || !isset($input['rating'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $product_id = intval($input['product_id']);
    $rating = intval($input['rating']);
    $review_text = isset($input['review_text']) ? trim($input['review_text']) : '';
    $order_id = isset($input['order_id']) ? intval($input['order_id']) : null;

    // Validate rating (1-5)
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        exit;
    }

    // Validate review text length (optional, but if provided, should be reasonable)
    if (!empty($review_text) && strlen($review_text) > 2000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Review text is too long (max 2000 characters)']);
        exit;
    }

    // Check if product exists
    $product_check = $conn->prepare("SELECT id FROM products WHERE id = ? AND deleted_at IS NULL");
    if (!$product_check) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $product_check->bind_param("i", $product_id);
    if (!$product_check->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $product_check->error]);
        $product_check->close();
        exit;
    }

    $product_result = $product_check->get_result();

    if ($product_result->num_rows === 0) {
        $product_check->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    $product_check->close();

    $table_check = $conn->query("SHOW TABLES LIKE 'product_reviews'");
    if (!$table_check || $table_check->num_rows === 0) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Reviews table does not exist. Please run the migration script: scripts/create-reviews-table.php'
        ]);
        exit;
    }

    $existing_review = $conn->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
    if (!$existing_review) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $existing_review->bind_param("ii", $user_id, $product_id);
    if (!$existing_review->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $existing_review->error]);
        $existing_review->close();
        exit;
    }

    $existing_result = $existing_review->get_result();

    if ($existing_result->num_rows > 0) {
        $existing_review_data = $existing_result->fetch_assoc();
        $review_id = $existing_review_data['id'];
        
        $update_stmt = $conn->prepare("UPDATE product_reviews SET rating = ?, review_text = ?, order_id = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
        $update_stmt->bind_param("isiii", $rating, $review_text, $order_id, $user_id, $product_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Review updated successfully',
                'review_id' => $review_id
            ]);
        } else {
            http_response_code(500);
            error_log("Review update error: " . $update_stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to update review: ' . $update_stmt->error]);
        }
        $update_stmt->close();
    } else {
        $final_order_id = $order_id;
        if ($order_id !== null) {
            $order_check = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? LIMIT 1");
            if ($order_check) {
                $order_check->bind_param("i", $order_id);
                if ($order_check->execute()) {
                    $order_result = $order_check->get_result();
                    if ($order_result->num_rows === 0) {
                        $final_order_id = null;
                    }
                }
                $order_check->close();
            }
        }
        
        $insert_stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, order_id, rating, review_text, is_approved) VALUES (?, ?, ?, ?, ?, 1)");
        if (!$insert_stmt) {
            http_response_code(500);
            error_log("Review insert prepare error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            $existing_review->close();
            exit;
        }
        
        $insert_stmt->bind_param("iiiis", $product_id, $user_id, $final_order_id, $rating, $review_text);
        
        try {
            if ($insert_stmt->execute()) {
                $review_id = $conn->insert_id;
                echo json_encode([
                    'success' => true,
                    'message' => 'Review submitted successfully',
                    'review_id' => $review_id
                ]);
            } else {
                http_response_code(500);
                $error_msg = $insert_stmt->error;
                $error_code = $conn->errno;
                error_log("Review insert error: " . $error_msg . " (Error code: " . $error_code . ")");
                error_log("Insert params: product_id=$product_id, user_id=$user_id, order_id=$final_order_id, rating=$rating");
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to submit review: ' . $error_msg,
                    'error_code' => $error_code
                ]);
            }
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            $error_msg = $e->getMessage();
            $error_code = $e->getCode();
            error_log("Review insert SQL exception: " . $error_msg . " (Error code: " . $error_code . ")");
            error_log("Insert params: product_id=$product_id, user_id=$user_id, order_id=$final_order_id, rating=$rating");
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $error_msg,
                'error_code' => $error_code
            ]);
        }
        $insert_stmt->close();
    }

    $existing_review->close();
    $conn->close();
    
} catch (Exception $e) {
    // Log the error with full details
    $error_msg = $e->getMessage();
    $error_trace = $e->getTraceAsString();
    error_log("Review submission error: " . $error_msg);
    error_log("Stack trace: " . $error_trace);
    
    // Return JSON error response with more details (for debugging)
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while submitting your review: ' . $error_msg,
        'error_type' => 'Exception'
    ]);
    exit;
} catch (Error $e) {
    // Log fatal errors with full details
    $error_msg = $e->getMessage();
    $error_trace = $e->getTraceAsString();
    error_log("Review submission fatal error: " . $error_msg);
    error_log("Stack trace: " . $error_trace);
    
    // Return JSON error response with more details (for debugging)
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'A system error occurred: ' . $error_msg,
        'error_type' => 'FatalError'
    ]);
    exit;
}

exit;
?>
