<?php
require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../../backend/pages/admin-includes/auth-helpers.php";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $token = $_GET["token"] ?? "";
    $token_hash = hash("sha256", $token);
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
    
    // Get passwords (trimming handled by shared functions)
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm-password"] ?? "";
    
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

    // Generate password hash using shared function
    $hashResult = hashPassword($password);
    
    if (!$hashResult['success']) {
        error_log("Password hashing failed: " . $hashResult['error']);
        echo "<script>alert('Password hashing error. Please contact support.'); history.back();</script>";
        exit;
    }
    
    $password_hash = $hashResult['hash'];
    
    // Update password and also set user as verified (in case they weren't)
    $sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL, is_verified = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare password update statement: " . $conn->error);
        echo "<script>alert('Database preparation error. Please try again.'); history.back();</script>";
        exit;
    }
    
    $stmt->bind_param("si", $password_hash, $user["id"]);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Password successfully updated! You can now login.'); window.location.href='/frontend/login/user/login-signup.php';</script>";
        } else {
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
        
        /* Password toggle icon styling */
        .password-input-group {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            color: #6b7280;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #374151;
        }
        
        .toggle-password .eye-icon {
            width: 20px;
            height: 20px;
        }
        
        .toggle-password .eye-slash {
            display: none;
        }
        
        .toggle-password.active .eye-icon {
            display: none;
        }
        
        .toggle-password.active .eye-slash {
            display: block;
        }
        
        .password-input-group .input-field {
            padding-right: 45px;
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
                        
                        <div class="input-group password-input-group">
                            <input type="password" name="password" id="password" class="input-field" placeholder="New Password" required autocomplete="new-password" maxlength="255">
                            <span class="toggle-password" onclick="togglePassword('password', this)">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                        </div>
                        
                        <div class="input-group password-input-group">
                            <input type="password" name="confirm-password" id="confirm-password" class="input-field" placeholder="Confirm Password" required autocomplete="new-password" maxlength="255">
                            <span class="toggle-password" onclick="togglePassword('confirm-password', this)">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
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
    function togglePassword(inputId, toggleIcon) {
        const passwordInput = document.getElementById(inputId);
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.add('active');
            toggleIcon.innerHTML = `
                <svg class="eye-slash" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
            `;
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('active');
            toggleIcon.innerHTML = `
                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            `;
        }
    }
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
