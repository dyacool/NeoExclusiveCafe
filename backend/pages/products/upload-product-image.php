<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check admin authentication
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

// Set JSON response header
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        throw new Exception('Invalid product ID');
    }

    $product_id = (int)$_POST['product_id'];
    $is_primary = false;
    $uploaded_file = null;

    // Check if it's a primary image or additional image
    if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['primary_image'];
        $is_primary = true;
    } elseif (isset($_FILES['additional_image']) && $_FILES['additional_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['additional_image'];
        $is_primary = false;
    } else {
        throw new Exception('No valid image uploaded');
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/jfif'];
    if (!in_array($uploaded_file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, WebP, and JFIF are allowed.');
    }

    // Get product name for folder creation
    $product_sql = "SELECT name FROM products WHERE id = ?";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $product_data = $product_result->fetch_assoc();

    if (!$product_data) {
        throw new Exception('Product not found');
    }

    // Create product folder
    $timestamp = time();
    $cleanProductName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $product_data['name']);
    $folderName = $cleanProductName . '_' . $timestamp;
    $productFolder = __DIR__ . "/../../../assets/product-images/" . $folderName . "/";
    
    if (!file_exists($productFolder)) {
        mkdir($productFolder, 0777, true);
    }

    // Generate filename
    $fileExt = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
    $prefix = $is_primary ? 'primary' : 'additional';
    $cleanFileName = $prefix . '_' . $timestamp . '.' . $fileExt;
    $filePath = $productFolder . $cleanFileName;

    // Move uploaded file
    if (!move_uploaded_file($uploaded_file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save uploaded file');
    }

    // Upload to Cloudinary
    require_once __DIR__ . '/../../../backend/includes/cloudinary-helper.php';
    $image_type = $is_primary ? 'primary' : 'additional';
    $cloudinaryResult = uploadToCloudinary($filePath, 'neocafe/products', 'product_' . $product_id . '_' . $image_type . '_' . $timestamp);
    
    // Store in database
    $dbImagePath = "product-images/" . $folderName . "/" . $cleanFileName;
    
    if ($cloudinaryResult['success']) {
        // Store both local path and Cloudinary URL
        $insert_sql = "INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, ?, ?, ?, 'cloudinary', ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssi", $product_id, $dbImagePath, $cloudinaryResult['url'], $cloudinaryResult['public_id'], $is_primary);
        
        if (!$insert_stmt->execute()) {
            // If database insert fails, delete the uploaded file
            unlink($filePath);
            throw new Exception('Failed to save image to database');
        }
        
        // Delete local file after successful Cloudinary upload
        @unlink($filePath);
        
        $image_id = $insert_stmt->insert_id;
        
        echo json_encode([
            'success' => true,
            'image' => [
                'id' => $image_id,
                'image_url' => $cloudinaryResult['url'], // Return Cloudinary URL for display
                'is_primary' => $is_primary
            ]
        ]);
    } else {
        // Fallback: Store only local path if Cloudinary upload fails
        $insert_sql = "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isi", $product_id, $dbImagePath, $is_primary);
        
        if (!$insert_stmt->execute()) {
            // If database insert fails, delete the uploaded file
            unlink($filePath);
            throw new Exception('Failed to save image to database');
        }
        
        $image_id = $insert_stmt->insert_id;
        
        error_log("Cloudinary upload failed: " . $cloudinaryResult['error']);
        
        echo json_encode([
            'success' => true,
            'image' => [
                'id' => $image_id,
                'image_url' => $dbImagePath,
                'is_primary' => $is_primary
            ],
            'warning' => 'Image saved locally but Cloudinary upload failed'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
