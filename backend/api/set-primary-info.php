<?php
session_start();
header('Content-Type: application/json');

require_once '../pages/admin-includes/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid entry ID']);
    exit();
}

try {
    $verify_sql = "SELECT id FROM saved_customer_info WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Entry not found or access denied']);
        $verify_stmt->close();
        exit();
    }
    $verify_stmt->close();
    
    $update_all = $conn->prepare("UPDATE saved_customer_info SET is_primary = 0 WHERE user_id = ?");
    $update_all->bind_param("i", $user_id);
    $update_all->execute();
    $update_all->close();
    
    $set_primary = $conn->prepare("UPDATE saved_customer_info SET is_primary = 1 WHERE id = ? AND user_id = ?");
    $set_primary->bind_param("ii", $id, $user_id);
    $set_primary->execute();
    $set_primary->close();
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Primary entry updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    error_log("Set primary info error: " . $e->getMessage());
}
