<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include SessionManager and check admin authentication
require_once __DIR__ . '/../../../includes/session-manager.php';

if (!SessionManager::isAdminLoggedIn()) {
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
    if (!isset($_POST['id'])) {
        throw new Exception('Product ID is required');
}

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id === false) {
        throw new Exception('Invalid product ID');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get product images before deletion
        $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['image_url'])) {
                $images[] = $row['image_url'];
            }
        }

        // Delete product images from database
        $stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Delete product from database
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Delete image files
        foreach ($images as $image) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/' . $image;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
