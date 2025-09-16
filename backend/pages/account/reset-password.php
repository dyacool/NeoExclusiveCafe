<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Include database and navbar
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";

$errorMessage = "";
$successMessage = "";

if (isset($_POST["reset_password"])) {
    $admin_id = $_SESSION['admin_id'];
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        $errorMessage = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $errorMessage = "Password must be at least 8 characters.";
    } else {
        // Fetch the current password from the database
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($current_password, $user["password"])) {
            // Hash new password and update it
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $admin_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                mysqli_stmt_close($update_stmt);
                $successMessage = "Password updated successfully!";
            } else {
                $errorMessage = "Error updating password: " . mysqli_error($conn);
            }
        } else {
            $errorMessage = "Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="/backend/pages/account/reset-password.css">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
</head>
<body>
    <div class="wrapper">
        <nav class="breadcrumb">
            <a href="/backend/pages/account/admin-profile.php">Account</a>
            <span class="separator">></span>
            <span class="current">Reset Password</span>
        </nav>
        
        <div class="main-container">
            <div class="container">
                <div class="form-header">
                    <div class="lock-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h1>Reset Password</h1>
                    <p>Enter your new password below</p>
                </div>
                
                <?php if (!empty($successMessage)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($successMessage); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($errorMessage); ?></span>
                    </div>
                <?php endif; ?>

                <form action="" method="post" class="password-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="input-group">
                            <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>

                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-group">
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter"></div>
                            <span class="strength-text">Password strength</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="reset-button" name="reset_password">
                        <span>Update Password</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
    
    <script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthMeter = document.querySelector('.strength-meter');
        const strengthText = document.querySelector('.strength-text');
        
        if (newPassword) {
            newPassword.addEventListener('input', updatePasswordStrength);
        }
        
        if (confirmPassword) {
            confirmPassword.addEventListener('input', validatePasswordMatch);
        }
        
        function updatePasswordStrength() {
            const password = newPassword.value;
            let strength = 0;
            let messages = [];
            
            // Check password length
            if (password.length >= 8) strength++;
            
            // Check for lowercase letters
            if (/[a-z]/.test(password)) strength++;
            
            // Check for uppercase letters
            if (/[A-Z]/.test(password)) strength++;
            
            // Check for numbers
            if (/[0-9]/.test(password)) strength++;
            
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update the strength meter
            const strengthPercentage = (strength / 5) * 100;
            strengthMeter.style.width = strengthPercentage + '%';
            
            // Update the strength text and color
            let strengthLabel = '';
            let strengthColor = '';
            
            if (password.length === 0) {
                strengthLabel = 'Password strength';
                strengthColor = '#e5e7eb';
            } else if (strength <= 2) {
                strengthLabel = 'Weak';
                strengthColor = '#ef4444'; // Red
            } else if (strength === 3) {
                strengthLabel = 'Moderate';
                strengthColor = '#f59e0b'; // Orange
            } else if (strength === 4) {
                strengthLabel = 'Strong';
                strengthColor = '#3b82f6'; // Blue
            } else {
                strengthLabel = 'Very Strong';
                strengthColor = '#10b981'; // Green
            }
            
            strengthMeter.style.backgroundColor = strengthColor;
            strengthText.textContent = strengthLabel;
            strengthText.style.color = strengthColor;
        }
        
        function validatePasswordMatch() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
    });
    </script>
</body>
</html>
