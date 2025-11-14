<?php
// Start output buffering to catch any unwanted output
ob_start();

// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';
require_once __DIR__ . '/../admin-includes/activity-logger.php';

// Clear any buffered output and set JSON header
ob_clean();
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !is_numeric($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid QR ID']);
    exit;
}

$qr_id = intval($input['id']);

try {
    // Start transaction
    $conn->begin_transaction();

    // First, set all QR codes to inactive
    $stmt = $conn->prepare("UPDATE bulk_payment SET is_active = 0 WHERE deleted_at IS NULL");
    if (!$stmt->execute()) {
        throw new Exception("Failed to deactivate existing QR codes");
    }

    // Set the selected QR code as active
    $stmt = $conn->prepare("UPDATE bulk_payment SET is_active = 1, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $qr_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to activate QR code");
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("QR code not found or already deleted");
    }

    // Log activity
    logAdminActivity($conn, 'UPDATE', "Set QR code ID $qr_id as active", 'bulk_payment', $qr_id);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'QR code set as active']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
