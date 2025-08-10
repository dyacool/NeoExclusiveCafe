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

// Validate that product ID is numeric
if (!is_numeric($productId)) {
    echo json_encode(['success' => false, 'error' => 'Product ID must be numeric']);
    exit;
}

// Get all removed images for this product
$stmt = $conn->prepare("SELECT id, temp_filename FROM product_images WHERE product_id = ? AND is_removed = 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $productId);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Database execute failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();

$deletedImages = [];
$errors = [];

// Log the deletion attempt
error_log("Starting deletion of removed images for product ID: " . $productId);

while ($image = $result->fetch_assoc()) {
    $imageId = $image['id'];
    $tempFilename = $image['temp_filename'];
    
    error_log("Processing image ID: $imageId, temp filename: $tempFilename");
    
    // Delete temp file if it exists
    if ($tempFilename) {
        $tempFilePath = dirname(dirname(dirname(__DIR__))) . "/assets/product-images/1_TEMP_IMAGES/" . $tempFilename;
        error_log("Temp file path: " . $tempFilePath);
        
        if (file_exists($tempFilePath)) {
            if (!unlink($tempFilePath)) {
                $errorMsg = "Failed to delete temp file for image ID: " . $imageId;
                error_log($errorMsg . " - File: " . $tempFilePath);
                $errors[] = $errorMsg;
            } else {
                error_log("Successfully deleted temp file: " . $tempFilePath);
            }
        } else {
            error_log("Temp file not found: " . $tempFilePath);
            // Don't add this as an error since the file might have been cleaned up already
        }
    }
    
    // Delete from database
    $deleteStmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
    if (!$deleteStmt) {
        $errorMsg = "Failed to prepare delete statement for image ID: " . $imageId;
        error_log($errorMsg . " - Database error: " . $conn->error);
        $errors[] = $errorMsg;
        continue;
    }
    
    $deleteStmt->bind_param("i", $imageId);
    
    if ($deleteStmt->execute()) {
        $deletedImages[] = $imageId;
        error_log("Successfully deleted image ID: " . $imageId . " from database");
    } else {
        $errorMsg = "Failed to delete from database for image ID: " . $imageId;
        error_log($errorMsg . " - Database error: " . $deleteStmt->error);
        $errors[] = $errorMsg;
    }
}

error_log("Deletion process completed. Deleted images: " . implode(", ", $deletedImages) . ", Errors: " . count($errors));

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
