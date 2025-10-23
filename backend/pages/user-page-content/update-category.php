<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['name']) || empty(trim($data['name']))) {
    echo json_encode(['success' => false, 'message' => 'Category ID and name are required']);
    exit();
}

$id = intval($data['id']);
$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : null;
$display_order = isset($data['display_order']) ? intval($data['display_order']) : 0;
$is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

// Check if category name already exists (excluding current category)
$check_sql = "SELECT id FROM categories WHERE name = ? AND id != ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "si", $name, $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['success' => false, 'message' => 'A category with this name already exists']);
    exit();
}

// Update category
$sql = "UPDATE categories SET name = ?, description = ?, display_order = ?, is_active = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssiii", $name, $description, $display_order, $is_active, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update category: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
