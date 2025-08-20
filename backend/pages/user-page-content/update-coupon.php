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
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $code = strtoupper(trim($_POST['code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $min_spend = floatval($_POST['min_spend']);
    $applicable_to = $_POST['applicable_to'];
    $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
    $usage_limit_per_user = !empty($_POST['usage_limit_per_user']) ? intval($_POST['usage_limit_per_user']) : null;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    
    // Validation
    if (empty($title) || empty($code) || empty($discount_type) || empty($start_date) || empty($end_date)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit();
    }
    
    // Validate code format (max 10 characters, alphanumeric only)
    if (strlen($code) > 10 || !preg_match('/^[A-Z0-9]+$/', $code)) {
        echo json_encode(['success' => false, 'message' => 'Code must be 10 characters or less and contain only letters and numbers']);
        exit();
    }
    
    // Check if code already exists (excluding current coupon)
    $check_sql = "SELECT id FROM promotions WHERE code = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $code, $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Coupon code already exists']);
        exit();
    }
    
    // Validate discount value for non-shipping discounts
    if ($discount_type !== 'shipping') {
        if ($discount_value <= 0) {
            echo json_encode(['success' => false, 'message' => 'Discount value must be greater than 0']);
            exit();
        }
        
        if ($discount_type === 'percentage' && $discount_value > 100) {
            echo json_encode(['success' => false, 'message' => 'Percentage discount cannot be more than 100%']);
            exit();
        }
    } else {
        $discount_value = 0; // Free shipping has no value
    }
    
    // Validate dates
    if (strtotime($start_date) >= strtotime($end_date)) {
        echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
        exit();
    }
    
    // Update coupon
    $sql = "UPDATE promotions SET 
            title = ?, 
            code = ?, 
            discount_type = ?, 
            discount_value = ?, 
            min_spend = ?, 
            applicable_to = ?, 
            usage_limit = ?, 
            usage_limit_per_user = ?, 
            start_date = ?, 
            end_date = ?, 
            status = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdsiissssi", $title, $code, $discount_type, $discount_value, $min_spend, $applicable_to, $usage_limit, $usage_limit_per_user, $start_date, $end_date, $status, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Coupon updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or coupon not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating coupon: ' . $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
