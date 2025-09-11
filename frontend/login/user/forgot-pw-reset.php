<?php
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $token = $_GET["token"] ?? "";
    $token_hash = hash("sha256", $token);
    require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
    // Use $conn directly instead of $mysqli alias
    $sql = "SELECT * FROM users WHERE reset_token_hash = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        die("<script>alert('Token not found'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }

    if (strtotime($user["reset_token_expires_at"]) <= time()) {
        die("<script>alert('Token has expired'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["token"] ?? "";
    $token_hash = hash("sha256", $token);
    $password = trim($_POST["password"] ?? "");
    $confirm_password = trim($_POST["confirm-password"] ?? "");
    
    // Debug logging with character analysis
    error_log("Password reset form submitted - Token: " . substr($token, 0, 10) . "..., Password length: " . strlen($password));
    error_log("Password characters: " . implode(',', array_map('ord', str_split($password))));
    
    if (strlen($password) < 8) {
        echo "<script>alert('Password must be at least 8 characters long.'); history.back();</script>";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); history.back();</script>";
        exit;
    }

    require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
    
    // Validate database connection
    if (!$conn) {
        error_log("Database connection failed in forgot-pw-reset.php");
        echo "<script>alert('Database connection error. Please try again.'); history.back();</script>";
        exit;
    }
    
    $sql = "SELECT * FROM users WHERE reset_token_hash = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        die("<script>alert('Invalid token'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }

    // Check if token is still valid
    if (strtotime($user["reset_token_expires_at"]) <= time()) {
        echo "<script>alert('Token has expired'); window.location.href='/frontend/login/user/login-signup.php';</script>";
        exit;
    }

    // Generate password hash with additional validation
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    error_log("Generated password hash for user ID " . $user["id"] . ": " . substr($password_hash, 0, 20) . "...");
    
    // Verify the hash was generated correctly
    if (!$password_hash || strlen($password_hash) < 50) {
        error_log("Password hash generation failed for user ID: " . $user["id"]);
        echo "<script>alert('Password hashing error. Please try again.'); history.back();</script>";
        exit;
    }
    
    // Test the hash immediately after generation
    $test_verify = password_verify($password, $password_hash);
    if (!$test_verify) {
        error_log("Password hash verification failed immediately after generation for user ID: " . $user["id"]);
        echo "<script>alert('Password verification error. Please try again.'); history.back();</script>";
        exit;
    }
    
    error_log("Password hash generated and verified successfully for user ID: " . $user["id"]);
    
    // Update password and also set user as verified (in case they weren't)
    $sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL, is_verified = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare password update statement: " . $conn->error);
        echo "<script>alert('Database preparation error. Please try again.'); history.back();</script>";
        exit;
    }
    
    $stmt->bind_param("si", $password_hash, $user["id"]);
    error_log("Executing password update for user ID: " . $user["id"] . " with hash length: " . strlen($password_hash));
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            error_log("Password successfully updated for user ID: " . $user["id"]);
            echo "<script>alert('Password successfully updated! You can now login.'); window.location.href='/frontend/login/user/login-signup.php';</script>";
        } else {
            error_log("No rows affected during password update for user ID: " . $user["id"]);
            echo "<script>alert('Failed to update password. Please try again.'); window.location.href='/frontend/login/user/login-signup.php';</script>";
        }
    } else {
        error_log("Database error during password update: " . $stmt->error);
        echo "<script>alert('Database error occurred. Please try again.'); window.location.href='/frontend/login/user/login-signup.php';</script>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/frontend/login/user/forgot-pw-reset.css">
    <script>
        function validateForm(event) {
            event.preventDefault();
            let password = document.getElementById("password").value;
            let confirmPassword = document.getElementById("confirm-password").value;
            let alertBox = document.getElementById("alertBox");
            
            if (password.length < 8) {
                showError("Password must be at least 8 characters long.");
                return;
            }

            if (password !== confirmPassword) {
                showError("Passwords do not match.");
                return;
            }

            document.getElementById("reset-form").submit();
        }
        
        function showError(message) {
            let alertBox = document.getElementById("alertBox");
            alertBox.innerHTML = message;
            alertBox.classList.add("show");
            setTimeout(() => { alertBox.classList.add("fade-out"); setTimeout(() => alertBox.style.display = 'none', 2000); }, 2000);
        }
    </script>
    <style>
        .alert-box {
            display: none;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px;
            border-radius: 5px;
            color: white;
            text-align: center;
            font-size: 16px;
            width: 80%;
            max-width: 400px;
            z-index: 1000;
            opacity: 1;
            transition: opacity 2s ease-in-out;
            background-color: #dc3545;
        }
        .alert-success { background-color: #28a745; }
        .alert-error { background-color: #dc3545; }
        .show { display: block; }
        .fade-out { opacity: 0; }

        h1{
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div id="alertBox" class="alert-box"></div>
    <div class="center">
        <div class="text">
            <h1 class="title">Reset Password</h1>
        </div>
        <div class="input-field">
            <form id="reset-form" method="post" action="" accept-charset="UTF-8">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="text-field">
                    <input type="password" name="password" id="password" placeholder="New Password" required>
                    <input type="password" name="confirm-password" id="confirm-password" placeholder="Confirm Password" required>
                </div>
                <button class="update-password">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>
