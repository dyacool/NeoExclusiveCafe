<?php
session_start();
require_once '../admin-includes/database.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get the product ID from POST data
$productId = $_POST['product_id'] ?? '';

if (empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

// Clean up temp directory using absolute path
$tempDir = dirname(dirname(dirname(__DIR__))) . '/assets/product-images/1_TEMP_IMAGES';
$cleanedFiles = [];

if (is_dir($tempDir)) {
    $files = scandir($tempDir);
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && strpos($file, 'temp_' . $productId . '_') === 0) {
            $filePath = $tempDir . '/' . $file;
            if (unlink($filePath)) {
                $cleanedFiles[] = $file;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Temporary images cleaned up successfully',
    'cleaned_files' => $cleanedFiles
]);
?>
