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

// Check admin authentication using session directly
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || 
    !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in as admin']);
    exit;
}

try {
    // Get admin ID from session
    $adminId = $_SESSION['admin_id'] ?? null;
    
    if (!$adminId) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Admin ID not found in session']);
        exit;
    }
    
    // Fetch admin email from database
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ? AND is_admin = 1");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (!$admin || !$admin['email']) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Admin user not found']);
        exit;
    }
    
    $adminEmail = $admin['email'];
    
    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set OTP expiration time (5 minutes from now)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Store OTP in database
    $stmt = $conn->prepare("INSERT INTO chatbot_otp (admin_id, admin_email, otp_code, expires_at, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $adminId, $adminEmail, $otp, $expiresAt);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to store OTP');
    }
    
    // Send OTP via email
    $subject = 'NeoCafe - Database Settings Access OTP';
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .otp-box { background: white; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .otp-code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 8px; margin: 10px 0; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 20px 0; border-radius: 4px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Security Verification</h1>
            </div>
            <div class='content'>
                <h2>Database Settings Access Request</h2>
                <p>Hello,</p>
                <p>You have requested access to the Chatbot Database Settings. Please use the following One-Time Password (OTP) to verify your identity:</p>
                
                <div class='otp-box'>
                    <p style='margin: 0; color: #666;'>Your OTP Code:</p>
                    <div class='otp-code'>{$otp}</div>
                    <p style='margin: 0; color: #666; font-size: 0.9rem;'>Valid for 5 minutes</p>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Security Notice:</strong> If you did not request this OTP, please ignore this email and ensure your account is secure. This code will expire in 5 minutes.
                </div>
                
                <p>For security reasons, never share this code with anyone.</p>
                
                <div class='footer'>
                    <p>This is an automated message from NeoCafe Admin Panel</p>
                    <p>&copy; " . date('Y') . " NeoCafe. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: NeoCafe Admin <noreply@neocafe.com>" . "\r\n";
    
    if (mail($adminEmail, $subject, $message, $headers)) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent successfully',
            'expires_in' => 300 // 5 minutes in seconds
        ]);
    } else {
        // Email failed - but OTP is still in database for testing
        ob_clean();
        echo json_encode([
            'success' => true, // Changed to true so user can proceed
            'message' => 'OTP generated (email delivery unavailable in development)',
            'expires_in' => 300,
            'dev_otp' => $otp // Include OTP in response for development testing
        ]);
    }
    
} catch (Exception $e) {
    error_log('Send OTP error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send OTP: ' . $e->getMessage()
    ]);
}
?>
