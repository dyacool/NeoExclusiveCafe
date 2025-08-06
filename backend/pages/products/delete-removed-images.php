<?php
session_start();
require_once '../admin-includes/database.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get parameters
$productId = $_POST['product_id'] ?? '';

if (empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

// Get all removed images for this product
$stmt = $conn->prepare("SELECT id, temp_filename FROM product_images WHERE product_id = ? AND is_removed = 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

$deletedImages = [];
$errors = [];

while ($image = $result->fetch_assoc()) {
    // Delete temp file if it exists
    if ($image['temp_filename']) {
        $tempFilePath = dirname(dirname(dirname(__DIR__))) . "/assets/product-images/1_TEMP_IMAGES/" . $image['temp_filename'];
        if (file_exists($tempFilePath)) {
            if (!unlink($tempFilePath)) {
                $errors[] = "Failed to delete temp file for image ID: " . $image['id'];
            }
        }
    }
    
    // Delete from database
    $deleteStmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
    $deleteStmt->bind_param("i", $image['id']);
    
    if ($deleteStmt->execute()) {
        $deletedImages[] = $image['id'];
    } else {
        $errors[] = "Failed to delete from database for image ID: " . $image['id'];
    }
}

if (empty($errors)) {
    echo json_encode([
        'success' => true,
        'message' => 'All removed images deleted permanently',
        'deleted_images' => $deletedImages
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Some images could not be deleted',
        'errors' => $errors,
        'deleted_images' => $deletedImages
    ]);
}
?>
