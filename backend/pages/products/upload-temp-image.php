<?php
/**
 * DEPRECATED: This endpoint is deprecated
 * Use /backend/api/upload-product-image.php instead
 */
session_start();
header('Content-Type: application/json');

// Return deprecation message
echo json_encode([
    'success' => false,
    'error' => 'This endpoint is deprecated. Images are now managed through Cloudinary.',
    'deprecated' => true,
    'new_endpoint' => '/backend/api/upload-product-image.php'
]);
exit;

// OLD CODE BELOW - KEPT FOR REFERENCE
/*
require_once '../admin-includes/database.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['image'];
$productId = $_POST['product_id'] ?? '';
$imageType = $_POST['image_type'] ?? 'additional'; // 'primary' or 'additional'

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
    exit;
}

// Validate file size (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File size too large. Maximum 5MB allowed.']);
    exit;
}

// Create temp directory path using absolute path
$tempDir = dirname(dirname(dirname(__DIR__))) . '/assets/product-images/1_TEMP_IMAGES';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}



// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$tempFilename = 'temp_' . $productId . '_' . time() . '_' . uniqid() . '.' . $extension;
$tempPath = $tempDir . '/' . $tempFilename;

// Move uploaded file to temp directory
if (move_uploaded_file($file['tmp_name'], $tempPath)) {
    // Return success with temp file info
    echo json_encode([
        'success' => true,
        'temp_filename' => $tempFilename,
        'temp_path' => 'assets/product-images/1_TEMP_IMAGES/' . $tempFilename,
        'image_type' => $imageType
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save temporary file']);
}
*/
?>
