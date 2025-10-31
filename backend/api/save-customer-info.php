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

$id = isset($input['id']) ? intval($input['id']) : null;
$label = isset($input['label']) ? trim($input['label']) : null;
$first_name = isset($input['first_name']) ? trim($input['first_name']) : '';
$last_name = isset($input['last_name']) ? trim($input['last_name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$delivery_location_id = isset($input['delivery_location_id']) ? intval($input['delivery_location_id']) : 0;
$complete_address = isset($input['complete_address']) ? trim($input['complete_address']) : '';
$set_as_primary = isset($input['set_as_primary']) ? (bool)$input['set_as_primary'] : false;

if (empty($first_name)) {
    echo json_encode(['success' => false, 'error' => 'First name is required', 'field' => 'first_name']);
    exit();
}

if (empty($last_name)) {
    echo json_encode(['success' => false, 'error' => 'Last name is required', 'field' => 'last_name']);
    exit();
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Valid email is required', 'field' => 'email']);
    exit();
}

if (empty($phone) || !preg_match('/^(\+63|0)9\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'error' => 'Invalid phone number format', 'field' => 'phone']);
    exit();
}

if ($delivery_location_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Delivery location is required', 'field' => 'delivery_location_id']);
    exit();
}

if (empty($complete_address)) {
    echo json_encode(['success' => false, 'error' => 'Complete address is required', 'field' => 'complete_address']);
    exit();
}

try {
    $check_location = $conn->prepare("SELECT delivery_id FROM delivery_locations WHERE delivery_id = ?");
    $check_location->bind_param("i", $delivery_location_id);
    $check_location->execute();
    $location_result = $check_location->get_result();
    
    if ($location_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid delivery location', 'field' => 'delivery_location_id']);
        $check_location->close();
        exit();
    }
    $check_location->close();
    
    if ($id === null) {
        $count_sql = "SELECT COUNT(*) as count FROM saved_customer_info WHERE user_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $existing_count = $count_row['count'];
        $count_stmt->close();
        
        if ($existing_count >= 3) {
            echo json_encode(['success' => false, 'error' => 'Maximum 3 saved entries allowed. Please delete an existing entry first.']);
            exit();
        }
        
        // If this is the first entry, automatically set it as primary
        if ($existing_count === 0) {
            $set_as_primary = true;
        }
    }
    
    if ($set_as_primary) {
        $update_primary = $conn->prepare("UPDATE saved_customer_info SET is_primary = 0 WHERE user_id = ?");
        $update_primary->bind_param("i", $user_id);
        $update_primary->execute();
        $update_primary->close();
    }
    
    if ($id === null) {
        $is_primary = $set_as_primary ? 1 : 0;
        
        $insert_sql = "INSERT INTO saved_customer_info 
                      (user_id, label, first_name, last_name, email, phone, delivery_location_id, complete_address, is_primary) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("isssssssi", $user_id, $label, $first_name, $last_name, $email, $phone, $delivery_location_id, $complete_address, $is_primary);
        $stmt->execute();
        $entry_id = $conn->insert_id;
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Information saved successfully',
            'entry_id' => $entry_id,
            'is_primary' => $is_primary
        ]);
    } else {
        $verify_owner = $conn->prepare("SELECT id FROM saved_customer_info WHERE id = ? AND user_id = ?");
        $verify_owner->bind_param("ii", $id, $user_id);
        $verify_owner->execute();
        $verify_result = $verify_owner->get_result();
        
        if ($verify_result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Entry not found or access denied']);
            $verify_owner->close();
            exit();
        }
        $verify_owner->close();
        
        $is_primary = $set_as_primary ? 1 : 0;
        
        $update_sql = "UPDATE saved_customer_info 
                      SET label = ?, first_name = ?, last_name = ?, email = ?, phone = ?, 
                          delivery_location_id = ?, complete_address = ?, is_primary = ?
                      WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssisiii", $label, $first_name, $last_name, $email, $phone, $delivery_location_id, $complete_address, $is_primary, $id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Information updated successfully',
            'entry_id' => $id,
            'is_primary' => $is_primary
        ]);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    error_log("Save customer info error: " . $e->getMessage());
}
