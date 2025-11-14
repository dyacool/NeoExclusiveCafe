<?php
// Start output buffering to catch any unwanted output
ob_start();

// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';
require_once __DIR__ . '/../admin-includes/activity-logger.php';

// Clear any buffered output and set JSON header
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !is_numeric($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid QR ID']);
    exit;
}

$qrId = intval($input['id']);

try {
    // Soft delete the specific QR code
    $stmt = $conn->prepare("UPDATE bulk_payment SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $qrId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'QR code not found or already deleted']);
        } else {
            // Log activity
            logAdminActivity($conn, 'DELETE', "Deleted bulk payment QR code ID $qrId", 'bulk_payment', $qrId);
            
            echo json_encode(['success' => true]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>