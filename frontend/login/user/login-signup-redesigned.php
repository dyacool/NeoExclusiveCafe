<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../../backend/pages/admin-includes/config.php";

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Check if the user is already logged in and avoid redirect loop
$currentPage = basename($_SERVER['PHP_SELF']);

$_SESSION['last_activity'] = time(); // Update last activity time

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

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if (!$passwordHash) {
        $error[] = "Error hashing password.";
    }

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
            $verificationLink = "http://neocafe.cafe:8080/frontend/login/user/verify-email.php?token=" . $token;
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
                        
                        // Switch to login form
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

// Handle User Login
if (isset($_POST["signin-submit"])) {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $error = [];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? AND is_admin = 0");
    if ($stmt === false) {
        $error[] = "Database error occurred: " . mysqli_error($conn);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $username);
        
        if (!mysqli_stmt_execute($stmt)) {
            $error[] = "Database error occurred: " . mysqli_stmt_error($stmt);
        } else {
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            
            if ($user && password_verify($password, $user["password"])) {
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
            } else {
                $error[] = "Invalid Username or Password!";
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    if (!empty($error)) {
        $errorMessage = implode("<br>", $error);
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
    <script src="/frontend/login/user/login-signup-redesigned.js" defer></script>
</head>
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
                <!-- Login Form -->
                <div id="login-form" class="auth-form">
                    <form action="login-signup-redesigned.php" method="post">
                        <h1 class="form-title">Log In</h1>
                        <p class="form-subtitle">Welcome back! Please enter your details.</p>
                        
                        <div class="input-group">
                            <input type="text" name="username" class="input-field" placeholder="Username" required>
                        </div>
                        
                        <div class="input-group">
                            <input type="password" name="password" class="input-field" placeholder="Password" required>
                        </div>
                        
                        <div class="utility-row">
                            <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                        </div>
                        
                        <input type="submit" name="signin-submit" value="Login" class="submit-btn">
                    </form>
                    
                    <div class="toggle-section">
                        <div class="toggle-text">Don't have an account yet?</div>
                        <a href="#" id="show-signup" class="toggle-link">Create Account</a>
                    </div>
                </div>

                <!-- Signup Form -->
                <div id="signup-form" class="auth-form hidden">
                    <form action="login-signup-redesigned.php" method="post">
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
                        
                        <div class="input-group">
                            <input type="password" name="password" class="input-field" placeholder="Password" required>
                        </div>
                        
                        <div class="input-group">
                            <input type="password" name="confirm-password" class="input-field" placeholder="Confirm Password" required>
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

    <script>
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
