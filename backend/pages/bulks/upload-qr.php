<?php
// Start output buffering to catch any unwanted output
ob_start();

// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';
require_once __DIR__ . '/../admin-includes/activity-logger.php';
require_once __DIR__ . '/../../../config/cloudinary-config.php';

// Clear any buffered output and set JSON header
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['qr_image']) || $_FILES['qr_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['qr_image'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
    exit;
}

// Validate file size (10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File size must be less than 10MB']);
    exit;
}

try {
    // Initialize Cloudinary
    $cloudinaryConfig = CloudinaryConfig::getInstance();
    $cloudinary = $cloudinaryConfig->getCloudinary();
    
    // Upload to Cloudinary
    $uploadResult = $cloudinary->uploadApi()->upload($file['tmp_name'], [
        'folder' => 'bulk_payment_qr',
        'resource_type' => 'image',
        'transformation' => [
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ]
    ]);
    
    $cloudinaryUrl = $uploadResult['secure_url'];
    
    // Get QR name from POST data
    $qrName = isset($_POST['qr_name']) && !empty(trim($_POST['qr_name'])) 
        ? trim($_POST['qr_name']) 
        : 'QR Code - ' . date('M j, Y g:i A');
    
    // Start transaction
    $conn->begin_transaction();
    
    // Set all existing QR codes to inactive
    $stmt = $conn->prepare("UPDATE bulk_payment SET is_active = 0 WHERE deleted_at IS NULL");
    $stmt->execute();
    
    // Insert new QR code as active
    $stmt = $conn->prepare("INSERT INTO bulk_payment (qr_image, qr_name, is_active, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())");
    $stmt->bind_param("ss", $cloudinaryUrl, $qrName);
    
    if ($stmt->execute()) {
        $qrId = $stmt->insert_id;
        
        // Log activity
        logAdminActivity($conn, 'CREATE', "Uploaded new bulk payment QR code: $qrName", 'bulk_payment', $qrId);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'url' => $cloudinaryUrl]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    }
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()]);
}
?>