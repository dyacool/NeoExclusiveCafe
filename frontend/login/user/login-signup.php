<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../../backend/pages/admin-includes/config.php";
require_once __DIR__ . "/../../../backend/pages/admin-includes/auth-helpers.php";

// Start session safely and set cookie params only if no session is active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'httponly' => true,
        'samesite' => 'Strict',
        'domain' => 'neocafe.shop'
    ]);
    session_start();
} else {
    // Ensure session is active
    @session_start();
}

// Check if the user is already logged in and avoid redirect loop
$currentPage = basename($_SERVER['PHP_SELF']);

$_SESSION['last_activity'] = time(); // Update last activity time

// Get carousel image with order value 1 for background
$carousel_bg_image = '';
$carousel_query = "SELECT COALESCE(cloud_url, image_url) as image_url FROM carousel_images WHERE display_order = 1 AND is_active = 1 LIMIT 1";
$carousel_result = mysqli_query($conn, $carousel_query);
if ($carousel_result && $carousel_row = mysqli_fetch_assoc($carousel_result)) {
    $carousel_bg_image = $carousel_row['image_url'];
}

// Handle security error messages
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'session_expired':
            $errorMessage = "Your session has expired. Please log in again.";
            break;
    }
}

// Handle User Signup
if (isset($_POST['signup-submit'])) {
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm-password"];
    $error = [];

    if (empty($firstname) || empty($lastname) || empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error[] = "All fields are required.";
    }
    if (!preg_match("/^[a-zA-Z-' ]*$/", $firstname) || !preg_match("/^[a-zA-Z-' ]*$/", $lastname)) {
        $error[] = "First and Last Name can only contain letters and spaces.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error[] = "Invalid email format.";
    }
    if (strlen($password) < 8) {
        $error[] = "Password must be at least 8 characters.";
    }
    if ($password !== $confirmPassword) {
        $error[] = "Passwords do not match.";
    }

    // Check if email already exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error[] = "Email already exists!";
    }
    mysqli_stmt_close($stmt);

    // Hash password using shared function
    $hashResult = hashPassword($password);
    if (!$hashResult['success']) {
        $error[] = "Error hashing password.";
    }
    $passwordHash = $hashResult['hash'];

    if (empty($error)) {
        // Set timezone
        date_default_timezone_set('Asia/Manila');
        
        // Generate verification token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes")); // Token expires in 30 minutes

        // Begin transaction
        mysqli_begin_transaction($conn);

        try {
            // Insert into database with verification token
            $stmt = mysqli_prepare($conn, "INSERT INTO users (firstname, lastname, username, email, password, verification_token, verification_token_expires_at, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, FALSE)");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, "sssssss", $firstname, $lastname, $username, $email, $passwordHash, $token_hash, $expiry);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
            }

            $userId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Verify the token was stored correctly
            $verify_stmt = mysqli_prepare($conn, "SELECT verification_token, verification_token_expires_at FROM users WHERE id = ?");
            mysqli_stmt_bind_param($verify_stmt, "i", $userId);
            mysqli_stmt_execute($verify_stmt);
            $result = mysqli_stmt_get_result($verify_stmt);
            $stored_data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($verify_stmt);

            if ($stored_data['verification_token'] !== $token_hash) {
                throw new Exception("Token verification failed");
            }

            // Send verification email
            $mail = require __DIR__ . "/../../../backend/config/mailer/mailer.php";
            $mail->setFrom("noreplyneoexclusive@gmail.com", "NeoExclusive");
            $mail->addAddress($email);
            $mail->Subject = "Email Verification";
            $verificationLink = "https://www.neocafe.shop/frontend/login/user/verify-email.php?token=" . $token;
            $mail->Body = <<<END
            <p>Hello $firstname,</p>
            <p>Thank you for registering! Please click the link below to verify your email address:</p>
            <p><a href="$verificationLink">Verify Email</a></p>
            <p>This link will expire in 30 minutes (at {$expiry}).</p>
            <p>If you can't click the link, copy and paste this URL into your browser:</p>
            <p>$verificationLink</p>
            END;

            if (!$mail->send()) {
                throw new Exception("Email could not be sent: " . $mail->ErrorInfo);
            }

            mysqli_commit($conn);
            $successMessage = "<strong>Registration Successful!</strong><br>A verification link has been sent to your email ($email)<br>";

            // Switch to login form after successful registration
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Show success message
                    var successAlert = document.getElementById('successAlert');
                    if(successAlert) {
                        successAlert.classList.add('show');
                        successAlert.style.display = 'block';
                        
                        // Switch to login form after showing success message
                        setTimeout(function() {
                            var showLoginLink = document.getElementById('show-login');
                            if (showLoginLink) {
                                showLoginLink.click();
                            }
                        }, 2000);
                    }
                });
            </script>";

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error[] = "Registration failed: " . $e->getMessage();
            $errorMessage = implode("<br>", $error);
        }
    } else {
        $errorMessage = implode("<br>", $error);
    }
}



// Handle Forgot Password
if (isset($_POST["reset-submit"])) {
    if (!isset($_POST["email"]) || empty($_POST["email"])) {
        $errorMessage = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
        } else {
            $token = bin2hex(random_bytes(16));
            $token_hash = hash("sha256", $token);
            $expiry = date("Y-m-d H:i:s", time() + 60 * 30);
        
            $sql = "UPDATE users
                    SET reset_token_hash = ?,
                        reset_token_expires_at = ?
                    WHERE email = ?";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log("Failed to prepare password reset statement: " . $conn->error);
                $errorMessage = "Database error occurred. Please try again.";
            } else {
                $stmt->bind_param("sss", $token_hash, $expiry, $email);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $mail = require __DIR__ . "/../../../backend/config/mailer/mailer.php";
                        $mail->setFrom("noreplyneoexclusive@gmail.com", "NeoExclusive");
                        $mail->addAddress($email);
                        $mail->Subject = "Password Reset Request";
                        $mail->Body = <<<END
                        <p>Hello,</p>
                        <p>Click <a href="https://www.neocafe.shop/frontend/login/user/forgot-pw-reset.php?token=$token">here</a>
                        to reset your password.</p>
                        <p>This link will expire in 30 minutes.</p>
                        END;
                        
                        try {
                            $mail->send();
                            $successMessage = "Password reset email sent. Please check your inbox.";
                            
                            // Add JavaScript to switch back to login form after showing message
                            echo "<script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    setTimeout(function() {
                                        var backToLoginLink = document.getElementById('back-to-login');
                                        if (backToLoginLink) {
                                            backToLoginLink.click();
                                        }
                                    }, 3000);
                                });
                            </script>";
                        } catch (Exception $e) {
                            $errorMessage = "Message could not be sent. Please try again later.";
                            error_log("Email sending failed: " . $mail->ErrorInfo);
                        }
                    } else {
                        $errorMessage = "No account found with this email.";
                    }
                } else {
                    error_log("Database error: " . $stmt->error);
                    $errorMessage = "Database error occurred. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

// Handle User Login
if (isset($_POST["signin-submit"])) {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $error = [];

    if (empty($username) || empty($password)) {
        $error[] = "Username and password are required.";
    } else {
        // Use LOWER() for case-insensitive username comparison
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND is_admin = 0");
        if ($stmt === false) {
            $error[] = "Database error occurred: " . mysqli_error($conn);
            error_log("Database prepare error: " . mysqli_error($conn));
        } else {
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if (!mysqli_stmt_execute($stmt)) {
                $error[] = "Database error occurred: " . mysqli_stmt_error($stmt);
                error_log("Database execute error: " . mysqli_stmt_error($stmt));
            } else {
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                
                if (!$user) {
                    $error[] = "User not found. Please check your username.";
                } else {
                    // Verify password using shared function
                    $verify_result = verifyPassword($password, $user["password"]);
                    
                    if (!$verify_result) {
                        $error[] = "Invalid password. Please check your password.";
                    } else {
                        // Password is correct
                        if (!$user["is_verified"]) {
                            $_SESSION['unverified_email'] = $user['email'];
                            header("Location: verification-page.php");
                            exit();
                        } else {
                            // Clear any existing session data to prevent conflicts
                            session_unset();
                            session_destroy();
                            session_start();
                            
                            // Set session variables with separate user keys
                            $_SESSION["user_id"] = $user["id"];
                            $_SESSION["user_username"] = $user["username"];
                            $_SESSION["is_verified"] = true;
                            $_SESSION["user_firstname"] = $user["firstname"];
                            $_SESSION["user_lastname"] = $user["lastname"];
                            $_SESSION["user_role"] = "user";

                            // Set flag for navbar animation on next page load
                            echo "<script>
                                sessionStorage.setItem('justLoggedIn', 'true');
                            </script>";

                            // If there was a redirect URL stored, use it
                            if (isset($_SESSION["user_redirect_url"])) {
                                $redirect = $_SESSION["user_redirect_url"];
                                unset($_SESSION["user_redirect_url"]);
                                header("Location: " . $redirect);
                            } else {
                                header("Location: /frontend/pages/home/user-dashboard.php");
                            }
                            exit();
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    if (!empty($error)) {
        $errorMessage = implode("<br>", $error);
        error_log("Login errors: " . implode(", ", $error));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeoCafe - Login & Sign Up</title>
    <link rel="stylesheet" href="/frontend/login/user/login-signup-redesigned.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Temporarily disable complex JavaScript for debugging -->
    <!-- <script src="/frontend/login/user/login-signup-redesigned.js" defer></script> -->
    
    <!-- Dynamic background image from carousel -->
    <?php if (!empty($carousel_bg_image)): ?>
    <style>
        .left-side {
            background-image: url("<?php echo htmlspecialchars($carousel_bg_image); ?>") !important;
        }
    </style>
    <?php endif; ?>
    
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
    <!-- Error Alert -->
    <?php if (!empty($errorMessage)): ?>
        <div id="errorAlert" class="alert show">
            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            <span><?php echo $errorMessage; ?></span>
        </div>
    <?php endif; ?>

    <!-- Success Alert -->
    <?php if (!empty($successMessage)): ?>
        <div id="successAlert" class="salert show">
            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            <span><?php echo $successMessage; ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Left Side - Background Image with Welcome Content -->
        <div class="left-side">
            <div class="welcome-content">
                <img src="https://res.cloudinary.com/dvdccumbs/image/upload/v1761594932/user-logo_zer35f.png" alt="NeoCafe Logo" class="logo" onerror="this.style.display='none'">
                <div class="welcome-text">
                    Welcome to Neo Cafe<br>
                </div>
            </div>
        </div>

        <!-- Right Side - Form Panel -->
        <div class="right-side">
            <div class="form-wrapper">
                <!-- Login Form -->
                <div id="login-form" class="auth-form">
                    <form action="login-signup.php" method="post">
                        <h1 class="form-title">Log In</h1>
                        <p class="form-subtitle">Welcome back! Please enter your details.</p>
                        
                        <div class="input-group">
                            <input type="text" name="username" class="input-field" placeholder="Username" required>
                        </div>
                        
                        <div class="input-group password-input-group">
                            <input type="password" name="password" id="login-password" class="input-field" placeholder="Password" required>

                        </div>
                        
                        <div class="utility-row">
                            <a href="#" class="forgot-link" id="show-forgot">Forgot password?</a>
                        </div>
                        
                        <input type="submit" name="signin-submit" value="Login" class="submit-btn">
                    </form>
                    
                    <div class="toggle-section">
                        <div class="toggle-text">Don't have an account yet?</div>
                        <a href="#" id="show-signup" class="toggle-link">Create Account</a>
                    </div>
                </div>

                <!-- Forgot Password Form -->
                <div id="forgot-form" class="auth-form hidden">
                    <form action="login-signup.php" method="post">
                        <h1 class="form-title">Forgot Password</h1>
                        <p class="form-subtitle">Enter your email to reset your password.</p>
                        
                        <div class="input-group">
                            <input type="email" name="email" class="input-field" placeholder="Email" required>
                        </div>
                        
                        <input type="submit" name="reset-submit" value="Reset Password" class="submit-btn">
                    </form>
                    
                    <div class="toggle-section">
                        <div class="toggle-text">Remember your password?</div>
                        <a href="#" id="back-to-login" class="toggle-link">Back to Login</a>
                    </div>
                </div>

                <!-- Signup Form -->
                <div id="signup-form" class="auth-form hidden">
                    <form action="login-signup.php" method="post">
                        <h1 class="form-title">Sign Up</h1>
                        <p class="form-subtitle">Create your account to get started.</p>
                        
                        <div class="input-group">
                            <input type="text" name="firstname" class="input-field" placeholder="First Name" required>
                        </div>
                        
                        <div class="input-group">
                            <input type="text" name="lastname" class="input-field" placeholder="Last Name" required>
                        </div>
                        
                        <div class="input-group">
                            <input type="text" name="username" class="input-field" placeholder="Username" required>
                        </div>
                        
                        <div class="input-group">
                            <input type="email" name="email" class="input-field" placeholder="Email" required>
                        </div>
                        
                        <div class="input-group password-input-group">
                            <input type="password" name="password" id="signup-password" class="input-field" placeholder="Password" required>
                            <span class="toggle-password" onclick="togglePassword('signup-password', this)">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                        </div>
                        
                        <div class="input-group password-input-group">
                            <input type="password" name="confirm-password" id="confirm-password" class="input-field" placeholder="Confirm Password" required>
                            <span class="toggle-password" onclick="togglePassword('confirm-password', this)">
                                <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                        </div>
                        
                        <input type="submit" name="signup-submit" value="Create Account" class="submit-btn">
                    </form>
                    
                    <div class="toggle-section">
                        <div class="toggle-text">Already have an account?</div>
                        <a href="#" id="show-login" class="toggle-link">Log In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hide Original Structure -->
    <style>
        .container, .back {
            display: none !important;
        }
    </style>

    <style>
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

        /* Confirmation Popup - Success/Error Notification */
        .confirmation-popup {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: white;
            color: #333;
            padding: 16px 24px;
            border-radius: 12px;
            z-index: 10000;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            font-weight: 600;
            min-width: 300px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: 2px solid transparent;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Success State - Green Theme */
        .confirmation-popup.success {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #2e7d32;
            border-color: #4caf50;
            box-shadow: 0 10px 40px rgba(76, 175, 80, 0.3);
        }

        /* Error State - Red Theme */
        .confirmation-popup.error {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            border-color: #f44336;
            box-shadow: 0 10px 40px rgba(244, 67, 54, 0.3);
        }

        /* Show Animation */
        .confirmation-popup.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Hide Animation */
        .confirmation-popup.hide {
            opacity: 0;
            transform: translateX(-50%) translateY(-100px);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .confirmation-popup {
                top: 70px;
                min-width: 280px;
                max-width: 90%;
                padding: 14px 20px;
                font-size: 14px;
            }
            
            .confirmation-popup.show {
                transform: translateX(-50%) translateY(0);
            }
            
            .confirmation-popup.hide {
                transform: translateX(-50%) translateY(-100px);
            }
        }
    </style>

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
        // Simple form switching without complex validation
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const forgotForm = document.getElementById('forgot-form');
            const showSignupLink = document.getElementById('show-signup');
            const showLoginLink = document.getElementById('show-login');
            const showForgotLink = document.getElementById('show-forgot');
            const backToLoginLink = document.getElementById('back-to-login');

            function showForm(formToShow) {
                // Hide all forms
                [loginForm, signupForm, forgotForm].forEach(form => {
                    if (form) form.classList.add('hidden');
                });
                // Show the requested form
                if (formToShow) formToShow.classList.remove('hidden');
            }

            // Form switching functionality
            if (showSignupLink) {
                showSignupLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    showForm(signupForm);
                    // Add animation to signup form
                    signupForm.classList.add('fade-in');
                });
            }

            if (showLoginLink) {
                showLoginLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    showForm(loginForm);
                });
            }

            if (showForgotLink) {
                showForgotLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    showForm(forgotForm);
                    // Add animation to forgot password form
                    forgotForm.classList.add('fade-in');
                });
            }

            if (backToLoginLink) {
                backToLoginLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    showForm(loginForm);
                });
            }


        });

        // Auto-fade alerts
        window.onload = function() {
            let errorBox = document.getElementById('errorAlert');
            let successBox = document.getElementById('successAlert');

            function fadeOut(element) {
                setTimeout(() => {
                    if(element && element.style) {
                        element.style.opacity = '0';
                        setTimeout(() => {
                            element.style.display = 'none';
                            element.classList.remove('show');
                        }, 300);
                    }
                }, 10000); // Show for 10 seconds
            }

            if (errorBox && errorBox.innerHTML.trim() !== '') {
                fadeOut(errorBox);
            }

            if (successBox && successBox.innerHTML.trim() !== '') {
                fadeOut(successBox);
            }
        };
    </script>
</body>
</html>