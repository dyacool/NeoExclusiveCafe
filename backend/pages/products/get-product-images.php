<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check admin authentication
session_start();

// Set JSON response header first
header('Content-Type: application/json');

// For debugging, let's see what's in the session
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode([
        'success' => false, 
        'error' => 'Unauthorized access',
        'debug' => [
            'session_exists' => isset($_SESSION),
            'is_admin_set' => isset($_SESSION["is_admin"]),
            'is_admin_value' => $_SESSION["is_admin"] ?? 'not set'
        ]
    ]);
    exit();
}

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

try {
    // Validate input
    if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
        throw new Exception('Invalid product ID');
    }

    $product_id = (int)$_GET['product_id'];

    // Get primary image (exclude removed images) - use only Cloudinary URLs
    $primary_sql = "SELECT id, cloud_url as image_url FROM product_images WHERE product_id = ? AND is_primary = 1 AND (is_removed = 0 OR is_removed IS NULL) AND cloud_url IS NOT NULL LIMIT 1";
    $primary_stmt = $conn->prepare($primary_sql);
    $primary_stmt->bind_param("i", $product_id);
    $primary_stmt->execute();
    $primary_result = $primary_stmt->get_result();
    $primary_image = $primary_result->fetch_assoc();

    // Get additional images (exclude removed images) - use only Cloudinary URLs
    $additional_sql = "SELECT id, cloud_url as image_url FROM product_images WHERE product_id = ? AND is_primary = 0 AND (is_removed = 0 OR is_removed IS NULL) AND cloud_url IS NOT NULL ORDER BY id ASC";
    $additional_stmt = $conn->prepare($additional_sql);
    $additional_stmt->bind_param("i", $product_id);
    $additional_stmt->execute();
    $additional_result = $additional_stmt->get_result();
    $additional_images = [];
    
    while ($row = $additional_result->fetch_assoc()) {
        $additional_images[] = $row;
    }
    
    // Log if any local image_url references are found (for auditing)
    require_once __DIR__ . '/../../includes/cloudinary-helper.php';
    $check_local_sql = "SELECT COUNT(*) as count FROM product_images WHERE product_id = ? AND image_url IS NOT NULL AND cloud_url IS NULL";
    $check_stmt = $conn->prepare($check_local_sql);
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $local_count = $check_result->fetch_assoc()['count'];
    
    if ($local_count > 0) {
        logLocalFileAccess("product_images table", 'READ_ATTEMPT', "Product $product_id has $local_count images with local paths but no Cloudinary URLs");
    }

    echo json_encode([
        'success' => true,
        'images' => [
            'primary' => $primary_image,
            'additional' => $additional_images
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
