<?php
/**
 * AJAX Delete Endpoint for Product Images
 * 
 * This endpoint handles real-time image deletion from Cloudinary via AJAX.
 * It removes images from Cloudinary and cleans up the temp_uploaded_images tracking table.
 */

require_once __DIR__ . '/../../includes/session-manager.php';

header('Content-Type: application/json');

// Verify admin authentication
if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please log in as admin.'
    ]);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid CSRF token. Please refresh the page and try again.'
    ]);
    exit();
}

require_once __DIR__ . '/../includes/cloudinary-helper.php';
require_once __DIR__ . '/../pages/admin-includes/database.php';

/**
 * Remove temporary image from tracking table
 * 
 * @param mysqli $conn Database connection
 * @param string $publicId Cloudinary public ID
 * @return bool Success status
 */
function removeTempImageLog($conn, $publicId) {
    try {
        $stmt = $conn->prepare("DELETE FROM temp_uploaded_images WHERE public_id = ?");
        if (!$stmt) {
            error_log("Failed to prepare statement for temp image removal: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("s", $publicId);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Failed to remove temp image log: " . $stmt->error);
        } else {
            $affectedRows = $stmt->affected_rows;
            if ($affectedRows > 0) {
                error_log("Removed temp image log for public_id: $publicId");
            }
        }
        
        $stmt->close();
        return $success;
    } catch (Exception $e) {
        error_log("Exception removing temp image log: " . $e->getMessage());
        return false;
    }
}

// Validate public_id parameter
$publicId = $_POST['public_id'] ?? '';

if (empty($publicId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No public ID provided'
    ]);
    exit();
}

// Sanitize public_id
$publicId = trim($publicId);

try {
    // Delete from Cloudinary
    $result = deleteFromCloudinary($publicId);
    
    if ($result['success']) {
        // Remove from temp tracking table
        $removed = removeTempImageLog($conn, $publicId);
        
        if (!$removed) {
            error_log("Warning: Failed to remove temp image log for public_id: $publicId");
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'public_id' => $publicId,
            'message' => 'Image deleted successfully'
        ]);
        
        error_log("AJAX delete successful: $publicId");
    } else {
        // Delete failed
        $statusCode = ($result['error_code'] === 'NOT_FOUND') ? 404 : 500;
        http_response_code($statusCode);
        
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Delete failed',
            'error_code' => $result['error_code'] ?? 'UNKNOWN',
            'public_id' => $publicId
        ]);
        
        error_log("AJAX delete failed for $publicId: " . ($result['error'] ?? 'Unknown error'));
    }
} catch (Exception $e) {
    error_log('AJAX delete exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Delete failed: ' . $e->getMessage(),
        'public_id' => $publicId
    ]);
}

$conn->close();
?>
