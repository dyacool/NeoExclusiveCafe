<?php
// Include SessionManager for proper session handling
require_once __DIR__ . "/../../../includes/session-manager.php";
require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (!isset($_GET["token"]) || empty($_GET["token"])) {
        die("<script>alert('No verification token provided.'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }

    $token = $_GET["token"];
    $token_hash = hash("sha256", $token);
    
    if (!$conn || !($conn instanceof mysqli)) {
        die("<script>alert('Database connection failed.'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }
    
    // Set timezone to match your server
    date_default_timezone_set('Asia/Manila');
    
    // Debug logging
    error_log("Verifying email with token: " . substr($token, 0, 10) . "...");
    error_log("Token hash: " . substr($token_hash, 0, 20) . "...");
    
    // First check if token exists and get expiration time
    $sql = "SELECT id, email, UNIX_TIMESTAMP(verification_token_expires_at) as expires_timestamp, verification_token FROM users WHERE verification_token = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        die("<script>alert('Database error occurred.'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }
    
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Debug logging
    if ($user) {
        error_log("User found with ID: " . $user['id'] . ", email: " . $user['email']);
        error_log("Stored token hash: " . substr($user['verification_token'], 0, 20) . "...");
        error_log("Provided token hash: " . substr($token_hash, 0, 20) . "...");
        error_log("Token match: " . ($user['verification_token'] === $token_hash ? 'YES' : 'NO'));
    } else {
        error_log("No user found with token hash: " . substr($token_hash, 0, 20) . "...");
    }
    
    if (!$user) {
        die("<script>alert('Invalid verification token.'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }

    // Check if token is expired
    $current_time = time();
    $expiry_time = $user['expires_timestamp'];
    
    if ($current_time > $expiry_time) {
        // Token is expired - redirect to verification page with email
        // Use proper session handling
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['unverified_email'] = $user['email'];
        die("<script>alert('Verification token has expired. Please request a new verification link.'); window.location.href='/frontend/login/user/verification-page.php';</script>");
    }

    // Update user as verified
    $sql = "UPDATE users SET is_verified = TRUE, verification_token = NULL, verification_token_expires_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user["id"]);
    
    if ($stmt->execute()) {
        // Trigger welcome notification (original in-app flow)
        require_once __DIR__ . "/../../../frontend/pages/notifications/class-notif.php";
        $notification = new Notification($conn);
        $notification->createWelcomeNotification($user['id']);

        echo "<script>alert('Email verified successfully! You can now log in.'); window.location.href='/frontend/login/user/login-signup.php';</script>";
    } else {
        echo "<script>alert('Error verifying email. Please try again.'); window.location.href='/frontend/login/user/login-signup.php';</script>";
    }
} else {
    header("Location: /frontend/login/user/login-signup.php");
    exit();
}
?>
