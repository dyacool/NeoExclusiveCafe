<?php
// Start output buffering before anything else
ob_start();

// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Include authentication and database (this starts the session)
require_once __DIR__ . '/../../../admin-includes/config.php';
require_once __DIR__ . '/../../../admin-includes/database.php';

// Clear any buffered output AFTER includes
ob_clean();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check admin authentication
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || 
    !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['otp']) || empty($input['otp'])) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'OTP code is required']);
        exit;
    }
    
    $otp = trim($input['otp']);
    
    // Get admin ID from session
    $adminId = $_SESSION['admin_id'] ?? null;
    
    if (!$adminId) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Admin ID not found']);
        exit;
    }
    
    // Verify OTP from database using admin_id
    $stmt = $conn->prepare("
        SELECT id, expires_at 
        FROM chatbot_otp 
        WHERE admin_id = ? AND otp_code = ? AND is_used = 0
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("is", $adminId, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Invalid OTP code'
        ]);
        exit;
    }
    
    $otpData = $result->fetch_assoc();
    
    // Check if OTP is expired
    if (strtotime($otpData['expires_at']) < time()) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'OTP has expired'
        ]);
        exit;
    }
    
    // Mark OTP as used
    $updateStmt = $conn->prepare("UPDATE chatbot_otp SET is_used = 1, used_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $otpData['id']);
    $updateStmt->execute();
    
    // Create access token
    $token = bin2hex(random_bytes(32));
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    
    // Store access token
    $tokenStmt = $conn->prepare("
        INSERT INTO chatbot_access_tokens (admin_id, token, expires_at, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $tokenStmt->bind_param("iss", $adminId, $token, $tokenExpires);
    $tokenStmt->execute();
    
    // Set session variable for access
    $_SESSION['chatbot_db_access_token'] = $token;
    $_SESSION['chatbot_db_access_expires'] = $tokenExpires;
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully',
        'token' => $token
    ]);
    
} catch (Exception $e) {
    error_log('Verify OTP error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Verification failed: ' . $e->getMessage()
    ]);
}
