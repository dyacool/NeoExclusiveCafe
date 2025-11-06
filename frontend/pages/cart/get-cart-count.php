<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include database connection first - it handles session configuration
require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

// Check if user is logged in with proper role
if (!SessionManager::isUserLoggedIn()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not logged in',
        'count' => 0
    ]);
    exit();
}

try {
    $user_id = SessionManager::getUserId();
    
    // Get cart count from database
    $query = "SELECT SUM(quantity) as total_count FROM cart WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception("Failed to execute query: " . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    $cart_count = (int)($row['total_count'] ?? 0);
    
    mysqli_stmt_close($stmt);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'count' => $cart_count,
        'message' => 'Cart count retrieved successfully'
    ]);
    
} catch (Exception $e) {
    // Log error and return error response
    error_log("Cart count error: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve cart count',
        'count' => 0
    ]);
}
?>