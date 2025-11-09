<?php
/**
 * AJAX Delete Endpoint for Admin Profile Pictures
 * 
 * This endpoint handles real-time admin profile picture deletion from Cloudinary via AJAX.
 * It removes images from Cloudinary, updates the user's profile, and cleans up tracking tables.
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
require_once __DIR__ . '/../pages/admin-includes/activity-logger.php';

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
                error_log("Removed temp profile picture log for public_id: $publicId");
            }
        }
        
        $stmt->close();
        return $success;
    } catch (Exception $e) {
        error_log("Exception removing temp profile picture log: " . $e->getMessage());
        return false;
    }
}

// Get user ID from session
$adminData = SessionManager::getAdminData();
$userId = $adminData['id'];

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

// Verify user owns this profile picture
try {
    $stmt = $conn->prepare("SELECT cloud_public_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit();
    }
    
    // Verify the public_id matches the user's current profile picture
    if ($user['cloud_public_id'] !== $publicId) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'You can only delete your own profile picture'
        ]);
        error_log("User $userId attempted to delete profile picture with public_id: $publicId, but their current public_id is: " . ($user['cloud_public_id'] ?? 'null'));
        exit();
    }
} catch (Exception $e) {
    error_log('Profile picture ownership verification exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to verify ownership'
    ]);
    exit();
}

try {
    // Delete from Cloudinary
    $result = deleteFromCloudinary($publicId);
    
    // Even if Cloudinary deletion fails, we still update the database
    // This handles cases where the image was already deleted or doesn't exist
    $cloudinarySuccess = $result['success'];
    
    if (!$cloudinarySuccess && $result['error_code'] !== 'NOT_FOUND') {
        error_log("Warning: Cloudinary deletion failed for $publicId: " . ($result['error'] ?? 'Unknown error'));
    }
    
    // Update user's profile to clear cloud fields
    $stmt = $conn->prepare("UPDATE users SET cloud_url = NULL, cloud_public_id = NULL, cloud_provider = NULL WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        error_log("Successfully cleared profile picture from user $userId database record");
        
        // Remove from temp tracking table
        $removed = removeTempImageLog($conn, $publicId);
        
        if (!$removed) {
            error_log("Warning: Failed to remove temp profile picture log for public_id: $publicId");
        }
        
        // Log the activity
        logAdminActivity($conn, 'DELETE', "Deleted profile picture", 'users', $userId);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'public_id' => $publicId,
            'message' => 'Profile picture deleted successfully',
            'cloudinary_deleted' => $cloudinarySuccess
        ]);
        
        error_log("Admin profile picture AJAX delete successful: $publicId");
    } else {
        // Database update failed
        error_log("Failed to update database after deleting profile picture: " . $stmt->error);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update profile in database',
            'error_code' => 'DATABASE_ERROR'
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log('Admin profile picture AJAX delete exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Delete failed: ' . $e->getMessage(),
        'public_id' => $publicId
    ]);
}

$conn->close();
?>
