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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['image_id']) || !isset($input['product_id'])) {
        throw new Exception('Missing required fields');
    }

    $image_id = (int)$input['image_id'];
    $product_id = (int)$input['product_id'];

    // Get image info before deletion
    $get_image_sql = "SELECT image_url, is_primary FROM product_images WHERE id = ? AND product_id = ?";
    $get_image_stmt = $conn->prepare($get_image_sql);
    $get_image_stmt->bind_param("ii", $image_id, $product_id);
    $get_image_stmt->execute();
    $image_result = $get_image_stmt->get_result();
    $image_data = $image_result->fetch_assoc();

    if (!$image_data) {
        throw new Exception('Image not found');
    }

    // Delete from database
    $delete_sql = "DELETE FROM product_images WHERE id = ? AND product_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $image_id, $product_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to delete image from database');
    }

    // Delete physical file
    $file_path = __DIR__ . "/../../../assets/" . $image_data['image_url'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // If this was a primary image, we might want to promote the first additional image
    if ($image_data['is_primary']) {
        $promote_sql = "UPDATE product_images SET is_primary = 1 WHERE product_id = ? AND is_primary = 0 ORDER BY id ASC LIMIT 1";
        $promote_stmt = $conn->prepare($promote_sql);
        $promote_stmt->bind_param("i", $product_id);
        $promote_stmt->execute();
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
