<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../pages/admin-includes/database.php";
require_once __DIR__ . "/../../pages/admin-includes/config.php";

// Session is already started by database.php

// If already logged in as admin, redirect to admin dashboard
if (isset($_SESSION["admin_id"]) && isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
    header("Location: /backend/pages/dashboard/dashboard.php");
    exit();
}

// Handle Admin Login
if (isset($_POST["admin-login-submit"])) {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $error = [];

    // Basic validation
    if (empty($username) || empty($password)) {
        $error[] = "All fields are required.";
    } else {
        // Check if the user exists and is an admin
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? AND is_admin = 1 AND is_verified = 1");
        if (!$stmt) {
            $error[] = "Database error occurred.";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);

            if ($admin && password_verify($password, $admin["password"])) {
                // Set session variables with admin keys
                session_regenerate_id(true); // Prevent session fixation
                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] = $admin["username"];
                $_SESSION["is_admin"] = true;
                $_SESSION["admin_firstname"] = $admin["firstname"];
                $_SESSION["admin_lastname"] = $admin["lastname"];
                $_SESSION["admin_role"] = "admin";

                // Redirect to admin homepage
                header("Location: /backend/pages/dashboard/dashboard.php");
                exit();
            } else {
                $error[] = "Invalid credentials.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (!empty($error)) {
        $errorMessage = implode("<br>", $error);
    }
}

// Handle error parameter
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'unauthorized':
            $errorMessage = "Please log in with admin credentials to access the admin area.";
            break;
        // Add other error cases as needed
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Neo Exclusive Cafe</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecef 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 440px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            width: auto;
            height: 60px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            padding: 60px 50px;
            width: 100%;
            max-width: 440px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #16a34a 0%, #42e07cff 100%);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .admin-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #16a34a 0%, #20b456ff 20%, #42e07cff 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
        }

        .admin-icon svg {
            width: 36px;
            height: 36px;
            fill: white;
        }

        .login-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 400;
            color: #374151;
            background: #f9fafb;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .form-group input:focus {
            border-color: #16a34a;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            transform: translateY(-1px);
        }

        .back-link {
            text-align: center;
            margin-bottom: 30px;
        }

        .back-link a {
            color: #6b7280;
            text-decoration: underline;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
            color: #8b5cf6;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #16a34a 0%, #20b456ff 20%, #42e07cff 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(22, 163, 74, 0.4);
            background: linear-gradient(135deg, #16a34a 0%, #20b456ff 20%, #42e07cff 100%);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
            border: 1px solid #fca5a5;
            color: #dc2626;
            font-size: 14px;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .logo-container {
                margin-bottom: 20px;
            }

            .logo-container img {
                height: 50px;
            }

            .login-container {
                padding: 40px 30px;
                border-radius: 16px;
            }

            .admin-icon {
                width: 70px;
                height: 70px;
            }

            .admin-icon svg {
                width: 32px;
                height: 32px;
            }

            .login-title {
                font-size: 16px;
            }

            .form-group input {
                padding: 14px 16px;
                font-size: 15px;
            }

            .submit-btn {
                padding: 14px;
                font-size: 15px;
            }
        }

        @media (max-width: 360px) {
            .login-container {
                padding: 30px 20px;
            }

            .logo-container img {
                height: 45px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="logo-container">
            <img src="https://res.cloudinary.com/dvdccumbs/image/upload/v1761594932/user-logo_zer35f.png" alt="Neo Cafe Logo">
        </div>
        
        <div class="login-container">
            <div class="logo-section">
                <div class="admin-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <div class="login-title">Admin Login</div>
            </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <input type="text" id="username" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            
            <div class="back-link">
                <a href="/frontend/pages/home/user-dashboard.php">Go to user website</a>
            </div>
            
            <button type="submit" name="admin-login-submit" class="submit-btn">Login</button>
        </form>
    </div>
    </div>
</body>
</html> 