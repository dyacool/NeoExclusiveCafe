<?php
// Test version without session checks
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON response header first
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

try {
    // Validate input
    if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
        throw new Exception('Invalid product ID');
    }

    $product_id = (int)$_GET['product_id'];

    // Get primary image
    $primary_sql = "SELECT id, image_url FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1";
    $primary_stmt = $conn->prepare($primary_sql);
    $primary_stmt->bind_param("i", $product_id);
    $primary_stmt->execute();
    $primary_result = $primary_stmt->get_result();
    $primary_image = $primary_result->fetch_assoc();

    // Get additional images
    $additional_sql = "SELECT id, image_url FROM product_images WHERE product_id = ? AND is_primary = 0 ORDER BY id ASC";
    $additional_stmt = $conn->prepare($additional_sql);
    $additional_stmt->bind_param("i", $product_id);
    $additional_stmt->execute();
    $additional_result = $additional_stmt->get_result();
    $additional_images = [];
    
    while ($row = $additional_result->fetch_assoc()) {
        $additional_images[] = $row;
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
