<?php
/**
 * Get Cart Item Count API
 * Returns the number of items in user's cart
 */

// Load database first (it starts session)
require_once __DIR__ . '/../pages/admin-includes/database.php';

// Then load SessionManager
require_once __DIR__ . '/../../includes/session-manager.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['count' => 0, 'loggedIn' => false, 'hasItems' => false]);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Count items in cart for this user
    $query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $count = (int)$row['count'];
    
    echo json_encode([
        'count' => $count,
        'loggedIn' => true,
        'hasItems' => $count > 0
    ]);
    
} catch (Exception $e) {
    error_log("Cart count error: " . $e->getMessage());
    echo json_encode(['count' => 0, 'error' => 'Failed to get cart count', 'loggedIn' => false, 'hasItems' => false]);
}
?>
