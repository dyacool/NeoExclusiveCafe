<?php
session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['id'])) {
        // Single delete
        $id = intval($input['id']);
        
        $sql = "DELETE FROM promotions WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Coupon deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Coupon not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting coupon: ' . $conn->error]);
        }
        
        $stmt->close();
        
    } elseif (isset($input['ids']) && is_array($input['ids'])) {
        // Bulk delete
        $ids = array_map('intval', $input['ids']);
        
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No coupon IDs provided']);
            exit();
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "DELETE FROM promotions WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        
        if ($stmt->execute()) {
            $deleted_count = $stmt->affected_rows;
            echo json_encode(['success' => true, 'message' => "$deleted_count coupon(s) deleted successfully"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting coupons: ' . $conn->error]);
        }
        
        $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
