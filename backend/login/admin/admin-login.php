<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../pages/admin-includes/database.php";
require_once __DIR__ . "/../../pages/admin-includes/config.php";

// Start session
session_start();

// If already logged in as admin, redirect to admin homepage
if (isset($_SESSION["admin_id"]) && isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
    header("Location: /backend/pages/homepage/admin-homepage.php");
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
                // Clear any existing session data to prevent conflicts
                session_unset();
                session_destroy();
                session_start();
                
                // Set session variables with separate admin keys
                session_regenerate_id(true); // Prevent session fixation
                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_username"] = $admin["username"];
                $_SESSION["is_admin"] = true;
                $_SESSION["admin_firstname"] = $admin["firstname"];
                $_SESSION["admin_lastname"] = $admin["lastname"];
                $_SESSION["admin_role"] = "admin";

                // Redirect to admin homepage
                header("Location: /backend/pages/homepage/admin-homepage.php");
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
        @import url("https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap");

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Spectral", serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #256035;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #4caf50;
            outline: none;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: white;
            background-color: #f44336;
        }

        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-site a {
            color: #256035;
            text-decoration: none;
            font-size: 14px;
        }

        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Please login to access the admin dashboard</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="admin-login-submit" class="submit-btn">Login</button>
        </form>

        <div class="back-to-site">
            <a href="/frontend/pages/home/user-dashboard.php">← Back to Website</a>
        </div>
    </div>
</body>
</html> 