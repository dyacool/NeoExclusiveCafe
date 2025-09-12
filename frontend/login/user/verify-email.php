<?php
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (!isset($_GET["token"]) || empty($_GET["token"])) {
        die("<script>alert('No verification token provided.'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }

    $token = $_GET["token"];
    $token_hash = hash("sha256", $token);
    
    require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
    if (!$conn || !($conn instanceof mysqli)) {
        die("<script>alert('Database connection failed.'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }
    
    // Set timezone to match your server
    date_default_timezone_set('Asia/Manila');
    
    // First check if token exists and get expiration time
    $sql = "SELECT *, UNIX_TIMESTAMP(verification_token_expires_at) as expires_timestamp, email FROM users WHERE verification_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        die("<script>alert('Invalid verification token.'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }

    // Check if token is expired
    $current_time = time();
    $expiry_time = $user['expires_timestamp'];
    
    if ($current_time > $expiry_time) {
        // Token is expired - redirect to verification page with email
        session_start();
        $_SESSION['unverified_email'] = $user['email'];
        die("<script>alert('Verification token has expired. Please request a new verification link.'); window.location.href='/frontend/login/user/verification-page.php';</script>");
    }

    // Update user as verified
    $sql = "UPDATE users SET is_verified = TRUE, verification_token = NULL, verification_token_expires_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user["id"]);
    
    if ($stmt->execute()) {
        // Trigger welcome notification
        require_once __DIR__ . "/../../../frontend/pages/notifications/class-notif.php";
        $notification = new Notification($conn);
        $notification->createWelcomeNotification($user['id']);

        echo "<script>alert('Email verified successfully! You can now log in.'); window.location.href='/frontend/pages/home/user-dashboard.php';</script>";
    } else {
        echo "<script>alert('Error verifying email. Please try again.'); window.location.href='/frontend/login/user/login-signup.php';</script>";
    }
} else {
    header("Location: /frontend/login/user/login-signup.php");
    exit();
}
?>