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
        <button class="cta" onclick="window.location.href='/backend/pages/account/admin-profile.php'">
            <svg
                id="arrow-horizontal"
                xmlns="http://www.w3.org/2000/svg"
                width="30"
                height="10"
                viewBox="0 0 46 16"
            >
                <path
                id="Path_10"
                data-name="Path 10"
                d="M38,0,39.455,1.455,33.949,6.961H76V9.039H33.949l5.506,5.506L38,16l-8-8Z"
                transform="translate(-25)"
                ></path>
            </svg>
            <span class="hover-underline-animation"> Go Back </span>
        </button>
        
        <div class="main-container">
            <div class="container">
                <div class="title">
                    <h3>Reset Password</h3>
                </div>
                <div class="content">
                    <form action="" method="post">
                        <div class="text-field">
                            <input type="password" name="current_password" placeholder="Current Password" required>
                        </div>
                        <div class="text-field">
                            <input type="password" name="new_password" placeholder="New Password" required>
                        </div>
                        <div class="text-field">
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                        </div>
                        <button type="submit" class="reset-pw" name="reset_password">Reset Password</button>
                    </form>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($successMessage)): ?>
                        <div class="alert"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
</body>
</html>
