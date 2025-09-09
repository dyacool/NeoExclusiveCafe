<?php
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $token = $_GET["token"] ?? "";
    $token_hash = hash("sha256", $token);
    require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
    $mysqli = $conn;
    $sql = "SELECT * FROM users WHERE reset_token_hash = ?";
    $stmt = $mysqli->prepare($sql);
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
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm-password"] ?? "";
    
    if (strlen($password) < 8) {
        echo "<div id='alertBox' class='alert-box alert-error show'>Password must be at least 8 characters long.</div>";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "<div id='alertBox' class='alert-box alert-error show'>Passwords do not match.</div>";
        exit;
    }

    require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
    $mysqli = $conn;
    $sql = "SELECT * FROM users WHERE reset_token_hash = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        die("<script>alert('Invalid token'); window.location.href='/frontend/login/user/login-signup.php';</script>");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $password_hash, $user["id"]);
    $stmt->execute();
    
            echo "<script>alert('Password successfully updated!'); window.location.href='/frontend/login/user/login-signup.php';</script>";
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
            <form id="reset-form" method="post" action="" onsubmit="validateForm(event)">
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
