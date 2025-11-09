<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

header('Content-Type: application/json');

if (!SessionManager::isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || empty(trim($data['name']))) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit();
}

$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : null;
$display_order = isset($data['display_order']) ? intval($data['display_order']) : 0;
$is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

// Check if category name already exists
$check_sql = "SELECT id FROM categories WHERE name = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "s", $name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['success' => false, 'message' => 'A category with this name already exists']);
    exit();
}

// Insert new category
$sql = "INSERT INTO categories (name, description, display_order, is_active) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssii", $name, $description, $display_order, $is_active);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Category added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add category: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
