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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeoCafe - Reset Password</title>
    <link rel="stylesheet" href="/frontend/login/user/login-signup-redesigned.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Alert styling for better error/success messages */
        .alert, .salert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            padding: 20px;
            border-radius: 8px;
            display: none;
            width: 90%;
            max-width: 500px;
            text-align: left;
            line-height: 1.5;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .alert {
            background: rgba(239, 68, 68, 0.95);
            color: white;
            border-left: 4px solid #dc2626;
        }
        .salert {
            background: rgba(34, 197, 94, 0.95);
            color: white;
            border-left: 4px solid #16a34a;
        }
        .alert.show, .salert.show {
            display: block;
            animation: slideInDown 0.5s ease-out;
        }
        @keyframes slideInDown {
            from { 
                opacity: 0; 
                transform: translate(-50%, -30px); 
            }
            to { 
                opacity: 1; 
                transform: translate(-50%, 0); 
            }
        }
        .closebtn {
            margin-left: 15px;
            color: white;
            font-weight: bold;
            float: right;
            font-size: 20px;
            line-height: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .closebtn:hover {
            opacity: 0.7;
            transform: scale(1.1);
        }
    </style>
</head>
<?php include __DIR__ . "/../../../frontend/user-includes/navbar/customer-navigation.php"; ?>
<body>
    <!-- Error Alert for validation messages -->
    <div id="errorAlert" class="alert">
        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
        <span id="errorMessage"></span>
    </div>

    <!-- Success Alert -->
    <div id="successAlert" class="salert">
        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
        <span id="successMessage"></span>
    </div>

    <!-- Back to Home Link -->
    <div class="back-home">
        <a href="/frontend/pages/home/user-dashboard.php">
            ← Back to Home
        </a>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Left Side - Background Image with Welcome Content -->
        <div class="left-side">
            <div class="welcome-content">
                <img src="/assets/images/user-logo.png" alt="NeoCafe Logo" class="logo" onerror="this.style.display='none'">
                <div class="welcome-text">
                    Welcome to Neo Cafe<br>
                </div>
            </div>
        </div>

        <!-- Right Side - Form Panel -->
        <div class="right-side">
            <div class="form-wrapper">
                <!-- Reset Password Form -->
                <div id="reset-form" class="auth-form">
                    <form method="post" action="">
                        <h1 class="form-title">Reset Password</h1>
                        <p class="form-subtitle">Enter your new password below.</p>
                        
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="input-field" placeholder="New Password" required>
                        </div>
                        
                        <div class="input-group">
                            <input type="password" name="confirm-password" id="confirm-password" class="input-field" placeholder="Confirm Password" required>
                        </div>
                        
                        <input type="submit" value="Update Password" class="submit-btn">
                    </form>
                    
                    <div class="toggle-section">
                        <div class="toggle-text">Remember your password?</div>
                        <a href="login-signup.php" class="toggle-link">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');

            function showAlert(message, type) {
                const alertElement = type === 'error' ? errorAlert : document.getElementById('successAlert');
                const messageElement = type === 'error' ? errorMessage : document.getElementById('successMessage');
                
                messageElement.textContent = message;
                alertElement.classList.add('show');
                alertElement.style.display = 'block';
                
                // Auto-fade after 5 seconds
                setTimeout(() => {
                    alertElement.style.opacity = '0';
                    setTimeout(() => {
                        alertElement.style.display = 'none';
                        alertElement.classList.remove('show');
                        alertElement.style.opacity = '1';
                    }, 300);
                }, 5000);
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                // Client-side validation
                if (password.length < 8) {
                    showAlert('Password must be at least 8 characters long.', 'error');
                    return;
                }
                
                if (password !== confirmPassword) {
                    showAlert('Passwords do not match.', 'error');
                    return;
                }
                
                // If validation passes, submit the form
                this.submit();
            });
        });
    </script>
</body>
</html>
