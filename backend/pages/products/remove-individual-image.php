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

// Validate that parameters are numeric
if (!is_numeric($imageId) || !is_numeric($productId)) {
    echo json_encode(['success' => false, 'error' => 'Image ID and Product ID must be numeric']);
    exit;
}

// Get the image details from database
$stmt = $conn->prepare("SELECT id, image_url, is_primary FROM product_images WHERE id = ? AND product_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $imageId, $productId);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Database execute failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Image not found in database']);
    exit;
}

$image = $result->fetch_assoc();

// Construct paths - Fix the path construction issue
$basePath = dirname(dirname(dirname(__DIR__)));
$originalFilePath = $basePath . "/assets/" . $image['image_url'];
$tempDir = $basePath . "/assets/product-images/1_TEMP_IMAGES";

// Log the path information for debugging
error_log("Image removal attempt - Image ID: $imageId, Product ID: $productId");
error_log("Database image_url: " . $image['image_url']);
error_log("Base path: " . $basePath);
error_log("Constructed file path: " . $originalFilePath);
error_log("Temp directory: " . $tempDir);

// Create temp directory if it doesn't exist
if (!is_dir($tempDir)) {
    if (!mkdir($tempDir, 0755, true)) {
        error_log("Failed to create temporary directory: " . $tempDir);
        echo json_encode(['success' => false, 'error' => 'Failed to create temporary directory']);
        exit;
    }
    error_log("Created temporary directory: " . $tempDir);
}

// Check if temp directory is writable
if (!is_writable($tempDir)) {
    error_log("Temporary directory is not writable: " . $tempDir);
    echo json_encode(['success' => false, 'error' => 'Temporary directory is not writable']);
    exit;
}

// Generate temporary filename
$originalFilename = basename($image['image_url']);
$tempFilename = "tmp_img_" . $imageId . "_" . time() . "_" . $originalFilename;
$tempFilePath = $tempDir . "/" . $tempFilename;

// Check if original file exists - Add better error logging and debugging
if (!file_exists($originalFilePath)) {
    // Log the path information for debugging
    error_log("Image removal failed - File not found. Image ID: $imageId, Product ID: $productId");
    error_log("Database image_url: " . $image['image_url']);
    error_log("Constructed file path: " . $originalFilePath);
    error_log("Base path: " . $basePath);
    
    // Check if the directory exists
    $imageDir = dirname($originalFilePath);
    if (!is_dir($imageDir)) {
        error_log("Image directory does not exist: " . $imageDir);
        echo json_encode([
            'success' => false, 
            'error' => 'Image directory not found',
            'debug_info' => [
                'image_directory' => $imageDir,
                'database_url' => $image['image_url'],
                'constructed_path' => $originalFilePath
            ]
        ]);
        exit;
    }
    
    // Check if the file exists with different case (Windows is case-insensitive, but let's be thorough)
    $directory = dirname($originalFilePath);
    $filename = basename($originalFilePath);
    $files = scandir($directory);
    $fileExists = false;
    $actualFilename = '';
    
    foreach ($files as $file) {
        if (strcasecmp($file, $filename) === 0) {
            $fileExists = true;
            $actualFilename = $file;
            break;
        }
    }
    
    if ($fileExists) {
        // File exists but with different case, use the actual filename
        $originalFilePath = $directory . "/" . $actualFilename;
        error_log("Found file with different case: " . $actualFilename);
    } else {
        // File truly doesn't exist
        error_log("File not found in directory. Directory contents: " . implode(", ", array_values(array_diff($files, ['.', '..']))));
        echo json_encode([
            'success' => false, 
            'error' => 'Original image file not found',
            'debug_info' => [
                'database_url' => $image['image_url'],
                'constructed_path' => $originalFilePath,
                'image_directory' => $imageDir,
                'directory_contents' => array_values(array_diff($files, ['.', '..']))
            ]
        ]);
        exit;
    }
}

// Check if the file is readable
if (!is_readable($originalFilePath)) {
    error_log("File exists but is not readable: " . $originalFilePath);
    echo json_encode(['success' => false, 'error' => 'Image file is not readable']);
    exit;
}

// Move file to temp location
if (rename($originalFilePath, $tempFilePath)) {
    error_log("Successfully moved file to temp location: " . $tempFilePath);
    
    // Mark image as removed in database (don't delete, just mark)
    $updateStmt = $conn->prepare("UPDATE product_images SET is_removed = 1, temp_filename = ? WHERE id = ?");
    if (!$updateStmt) {
        // If database update fails, move file back
        rename($tempFilePath, $originalFilePath);
        error_log("Database prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }
    
    $updateStmt->bind_param("si", $tempFilename, $imageId);
    
    if ($updateStmt->execute()) {
        error_log("Successfully updated database for image ID: " . $imageId);
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
        error_log("Database update failed: " . $updateStmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to update database: ' . $updateStmt->error]);
    }
} else {
    error_log("Failed to move file to temp location. Error: " . error_get_last()['message'] ?? 'Unknown error');
    echo json_encode(['success' => false, 'error' => 'Failed to move image to temporary storage']);
}
?>
