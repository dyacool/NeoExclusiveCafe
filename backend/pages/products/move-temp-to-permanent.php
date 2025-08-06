<?php
session_start();
require_once '../admin-includes/database.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get parameters
$tempFilename = $_POST['temp_filename'] ?? '';
$productId = $_POST['product_id'] ?? '';
$imageType = $_POST['image_type'] ?? 'additional';
$action = $_POST['action'] ?? 'move'; // 'move' or 'remove'

if (empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

// If action is 'remove', we don't need tempFilename
if ($action === 'move' && empty($tempFilename)) {
    echo json_encode(['success' => false, 'error' => 'Temp filename is required for move action']);
    exit;
}

// Get product name for directory
$stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();
$productName = $product['name'];

// Create product directory name (sanitized)
$productDirName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productName);
$productDir = dirname(dirname(dirname(__DIR__))) . "/assets/product-images/{$productDirName}";

// Create product directory if it doesn't exist
if (!is_dir($productDir)) {
    mkdir($productDir, 0755, true);
}

// Source and destination paths using absolute paths
$tempPath = dirname(dirname(dirname(__DIR__))) . "/assets/product-images/1_TEMP_IMAGES/{$tempFilename}";
$permanentPath = dirname(dirname(dirname(__DIR__))) . "/assets/product-images/{$productDirName}/{$tempFilename}";

if ($action === 'remove') {
    // Handle removal of existing images
    $existingImages = [];
    if ($imageType === 'primary') {
        // For primary images, remove all existing primary images
        $stmt = $conn->prepare("SELECT id, image_url FROM product_images WHERE product_id = ? AND is_primary = 1");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existingImages[] = $row;
        }
    } else {
        // For additional images, remove all existing additional images
        $stmt = $conn->prepare("SELECT id, image_url FROM product_images WHERE product_id = ? AND is_primary = 0");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existingImages[] = $row;
        }
    }

    // Remove existing images from database and file system
    foreach ($existingImages as $existingImage) {
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
        $deleteStmt->bind_param("i", $existingImage['id']);
        $deleteStmt->execute();
        
        // Delete file from file system
        $existingFilePath = dirname(dirname(dirname(__DIR__))) . "/assets/" . $existingImage['image_url'];
        if (file_exists($existingFilePath)) {
            unlink($existingFilePath);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Images removed successfully',
        'removed_images' => count($existingImages)
    ]);
} else {
    // Handle moving temporary images to permanent location
    // Check if temp file exists
    if (!file_exists($tempPath)) {
        echo json_encode(['success' => false, 'error' => 'Temporary file not found']);
        exit;
    }

    if ($imageType === 'primary') {
        // For primary images, remove all existing primary images first
        $existingImages = [];
        $stmt = $conn->prepare("SELECT id, image_url FROM product_images WHERE product_id = ? AND is_primary = 1");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existingImages[] = $row;
        }

        // Remove existing primary images from database and file system
        foreach ($existingImages as $existingImage) {
            // Delete from database
            $deleteStmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
            $deleteStmt->bind_param("i", $existingImage['id']);
            $deleteStmt->execute();
            
            // Delete file from file system
            $existingFilePath = dirname(dirname(dirname(__DIR__))) . "/assets/" . $existingImage['image_url'];
            if (file_exists($existingFilePath)) {
                unlink($existingFilePath);
            }
        }
    }
    // For additional images, we don't remove existing ones - we just add the new one

    // Move file from temp to permanent location
    if (rename($tempPath, $permanentPath)) {
        // Insert into database
        $imageUrl = "product-images/{$productDirName}/{$tempFilename}";
        $isPrimary = ($imageType === 'primary') ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $productId, $imageUrl, $isPrimary);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Image moved successfully',
                'image_url' => $imageUrl,
                'image_id' => $conn->insert_id,
                'replaced_images' => ($imageType === 'primary') ? count($existingImages) : 0
            ]);
        } else {
            // If database insert fails, move file back to temp
            rename($permanentPath, $tempPath);
            echo json_encode(['success' => false, 'error' => 'Failed to save image to database']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to move file']);
    }
}
?>
