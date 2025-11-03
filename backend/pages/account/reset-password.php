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
require_once __DIR__ . "/../admin-includes/activity-logger.php";

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
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $admin_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Log the activity
                logAdminActivity($conn, 'UPDATE', "Changed account password", 'users', $admin_id);
                
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
    <title>Reset Password - Admin</title>
    <link rel="stylesheet" href="/backend/pages/account/admin-account.css">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <style>
        /* Breadcrumb Styles */
        .breadcrumb-container {
            padding: 0 1rem;
        }
        
        .breadcrumb-list {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .breadcrumb-link {
            color: var(--green-600);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .breadcrumb-link:hover {
            color: var(--green-700);
            text-decoration: underline;
        }
        
        .breadcrumb-current {
            color: var(--gray-600);
            font-size: 0.875rem;
        }
        
        .breadcrumb-separator {
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .separator-icon {
            width: 16px;
            height: 16px;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-meter-container {
            height: 4px;
            background-color: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .strength-meter {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            background-color: var(--gray-300);
        }

        .strength-text {
            font-size: 0.75rem;
            color: var(--gray-500);
            font-weight: 500;
        }

        /* Password Toggle Button */
        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .password-toggle-btn:hover {
            color: var(--gray-700);
        }

        .password-toggle-btn svg {
            width: 20px;
            height: 20px;
        }

        .form-group {
            position: relative;
        }

        .form-input[type="password"],
        .form-input[type="text"] {
            padding-right: 2.5rem;
        }

        /* Lock Icon Styling */
        .lock-icon-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--green-100), var(--green-200));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .lock-icon-container svg {
            width: 40px;
            height: 40px;
            color: var(--green-700);
        }
    </style>
</head>
<body>
    <div class="admin-profile-container">
            <?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

        <div class="main-container">
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header">
                    <h1 class="profile-title">Reset Password</h1>
                    <div class="lock-icon-container">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <p class="user-username">Update your account password</p>
                </div>
                
                <div class="profile-content">
                    <form action="" method="post" id="password-form">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div style="position: relative;">
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    class="form-input" 
                                    placeholder="Enter your current password" 
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('current_password', this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <div style="position: relative;">
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="form-input" 
                                    placeholder="Enter new password (min. 8 characters)" 
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password', this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-meter-container">
                                    <div class="strength-meter"></div>
                                </div>
                                <span class="strength-text">Password strength</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div style="position: relative;">
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-input" 
                                    placeholder="Re-enter new password" 
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn" name="reset_password">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
            
            <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
        </div>
    </div>

    <script>
    // Toggle password visibility
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const svg = button.querySelector('svg');
        
        if (input.type === 'password') {
            input.type = 'text';
            // Change to "eye-slash" icon
            svg.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
        } else {
            input.type = 'password';
            // Change back to "eye" icon
            svg.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthMeterContainer = document.querySelector('.strength-meter-container');
        const strengthMeter = document.querySelector('.strength-meter');
        const strengthText = document.querySelector('.strength-text');
        const form = document.getElementById('password-form');
        
        if (newPassword) {
            newPassword.addEventListener('input', updatePasswordStrength);
        }
        
        if (confirmPassword) {
            confirmPassword.addEventListener('input', validatePasswordMatch);
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                    showMessage('Passwords do not match', 'error');
                    confirmPassword.focus();
                    return false;
                }
                
                if (newPassword.value.length < 8) {
                    e.preventDefault();
                    showMessage('Password must be at least 8 characters', 'error');
                    newPassword.focus();
                    return false;
                }
            });
        }
        
        function updatePasswordStrength() {
            const password = newPassword.value;
            let strength = 0;
            
            // Check password length
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Check for lowercase letters
            if (/[a-z]/.test(password)) strength++;
            
            // Check for uppercase letters
            if (/[A-Z]/.test(password)) strength++;
            
            // Check for numbers
            if (/[0-9]/.test(password)) strength++;
            
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update the strength meter
            const strengthPercentage = Math.min((strength / 6) * 100, 100);
            strengthMeter.style.width = strengthPercentage + '%';
            
            // Update the strength text and color
            let strengthLabel = '';
            let strengthColor = '';
            
            if (password.length === 0) {
                strengthLabel = 'Password strength';
                strengthColor = '#9ca3af';
                strengthMeter.style.width = '0%';
            } else if (strength <= 2) {
                strengthLabel = 'Weak';
                strengthColor = '#ef4444'; // Red
            } else if (strength === 3 || strength === 4) {
                strengthLabel = 'Moderate';
                strengthColor = '#f59e0b'; // Orange
            } else if (strength === 5) {
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
            const matchIndicator = document.querySelector('.match-indicator');
            
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value === confirmPassword.value) {
                    confirmPassword.style.borderColor = '#10b981';
                } else {
                    confirmPassword.style.borderColor = '#ef4444';
                }
            } else {
                confirmPassword.style.borderColor = '';
            }
        }

        function showMessage(message, type) {
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    ${type === 'success' 
                        ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                        : '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'
                    }
                </svg>
                ${message}
            `;
            
            const container = document.querySelector('.main-container');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.style.animation = 'fadeOut 0.5s ease';
                setTimeout(() => alertDiv.remove(), 500);
            }, 3000);
        }

        // Auto-dismiss success/error messages
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.animation = 'fadeOut 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });
    </script>
</body>
</html>
