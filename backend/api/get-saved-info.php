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

try {
    // Check if user has saved info but no primary set
    $check_primary_sql = "SELECT COUNT(*) as total, SUM(is_primary) as primary_count 
                          FROM saved_customer_info 
                          WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_primary_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    $total_entries = intval($check_row['total']);
    $primary_count = intval($check_row['primary_count']);
    $check_stmt->close();
    
    // If user has saved info but no primary, set the first one as primary
    if ($total_entries > 0 && $primary_count === 0) {
        error_log("API get-saved-info: User $user_id has $total_entries saved entries but no primary. Setting first entry as primary.");
        $set_first_primary_sql = "UPDATE saved_customer_info 
                                  SET is_primary = 1 
                                  WHERE user_id = ? 
                                  ORDER BY created_at ASC 
                                  LIMIT 1";
        $set_stmt = $conn->prepare($set_first_primary_sql);
        if ($set_stmt) {
            $set_stmt->bind_param("i", $user_id);
            $set_stmt->execute();
            error_log("API get-saved-info: ✓ Automatically set first entry as primary for user $user_id");
            $set_stmt->close();
        }
    }
    
    $sql = "SELECT 
                sci.id,
                sci.label,
                sci.first_name,
                sci.last_name,
                sci.email,
                sci.phone,
                sci.delivery_location_id,
                sci.complete_address,
                sci.is_primary,
                sci.created_at,
                sci.updated_at,
                CONCAT(dl.municipality, ', ', dl.city, ' ', dl.postal_code) as delivery_location,
                dl.delivery_fee
            FROM saved_customer_info sci
            JOIN delivery_locations dl ON sci.delivery_location_id = dl.delivery_id
            WHERE sci.user_id = ?
            ORDER BY sci.is_primary DESC, sci.created_at ASC
            LIMIT 3";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = [
            'id' => (int)$row['id'],
            'label' => $row['label'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'delivery_location_id' => (int)$row['delivery_location_id'],
            'delivery_location' => $row['delivery_location'],
            'delivery_fee' => (float)$row['delivery_fee'],
            'complete_address' => $row['complete_address'],
            'is_primary' => (int)$row['is_primary']
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'entries' => $entries,
        'count' => count($entries)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    error_log("Get saved info error: " . $e->getMessage());
}
