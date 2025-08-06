<?php
session_start();
require_once '../admin-includes/database.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get parameters
$imageId = $_POST['image_id'] ?? '';
$productId = $_POST['product_id'] ?? '';

if (empty($imageId) || empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Image ID and Product ID are required']);
    exit;
}

// Get the image details from database
$stmt = $conn->prepare("SELECT id, image_url, is_primary FROM product_images WHERE id = ? AND product_id = ?");
$stmt->bind_param("ii", $imageId, $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Image not found']);
    exit;
}

$image = $result->fetch_assoc();

// Construct paths
$originalFilePath = dirname(dirname(dirname(__DIR__))) . "/assets/" . $image['image_url'];
$tempDir = dirname(dirname(dirname(__DIR__))) . "/assets/product-images/1_TEMP_IMAGES";

// Create temp directory if it doesn't exist
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Generate temporary filename
$originalFilename = basename($image['image_url']);
$tempFilename = "tmp_img_" . $imageId . "_" . time() . "_" . $originalFilename;
$tempFilePath = $tempDir . "/" . $tempFilename;

// Check if original file exists
if (!file_exists($originalFilePath)) {
    echo json_encode(['success' => false, 'error' => 'Original image file not found']);
    exit;
}

// Move file to temp location
if (rename($originalFilePath, $tempFilePath)) {
    // Mark image as removed in database (don't delete, just mark)
    $updateStmt = $conn->prepare("UPDATE product_images SET is_removed = 1, temp_filename = ? WHERE id = ?");
    $updateStmt->bind_param("si", $tempFilename, $imageId);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Image moved to temporary storage',
            'temp_filename' => $tempFilename,
            'image_id' => $imageId,
            'is_primary' => $image['is_primary']
        ]);
    } else {
        // If database update fails, move file back
        rename($tempFilePath, $originalFilePath);
        echo json_encode(['success' => false, 'error' => 'Failed to update database']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move image to temporary storage']);
}
?>
