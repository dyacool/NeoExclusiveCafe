<?php
header('Content-Type: application/json');

// Include database connection FIRST - it handles session configuration
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
    $verify_sql = "SELECT is_primary FROM saved_customer_info WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Entry not found or access denied']);
        $verify_stmt->close();
        exit();
    }
    
    $entry = $verify_result->fetch_assoc();
    $was_primary = (int)$entry['is_primary'];
    $verify_stmt->close();
    
    // Check total count before deletion
    $count_sql = "SELECT COUNT(*) as total FROM saved_customer_info WHERE user_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_before_delete = intval($count_row['total']);
    $count_stmt->close();
    
    error_log("Deleting entry ID $id for user $user_id. Was primary: $was_primary, Total entries: $total_before_delete");
    
    $delete_sql = "DELETE FROM saved_customer_info WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $id, $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    $new_primary_id = null;
    
    // If we deleted the primary entry and there are still entries left, promote the oldest one
    if ($was_primary === 1 && $total_before_delete > 1) {
        error_log("Deleted primary entry. Promoting oldest remaining entry to primary for user $user_id");
        
        $find_oldest = "SELECT id FROM saved_customer_info WHERE user_id = ? ORDER BY created_at ASC LIMIT 1";
        $find_stmt = $conn->prepare($find_oldest);
        $find_stmt->bind_param("i", $user_id);
        $find_stmt->execute();
        $find_result = $find_stmt->get_result();
        
        if ($find_result->num_rows > 0) {
            $oldest = $find_result->fetch_assoc();
            $new_primary_id = (int)$oldest['id'];
            
            $set_primary = $conn->prepare("UPDATE saved_customer_info SET is_primary = 1 WHERE id = ?");
            $set_primary->bind_param("i", $new_primary_id);
            $set_primary->execute();
            $set_primary->close();
            
            error_log("✓ Set entry ID $new_primary_id as new primary for user $user_id");
        }
        
        $find_stmt->close();
    } elseif ($was_primary === 1 && $total_before_delete === 1) {
        error_log("Deleted last remaining entry for user $user_id. No primary to set.");
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Entry deleted successfully',
        'was_primary' => $was_primary,
        'new_primary_id' => $new_primary_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    error_log("Delete saved info error: " . $e->getMessage());
}
