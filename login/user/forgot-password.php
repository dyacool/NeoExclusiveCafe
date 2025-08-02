<?php
$message = "";
$messageType = ""; // "success" or "error"

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST["email"]) || empty($_POST["email"])) {
        $message = "Please enter your email.";
        $messageType = "error";
    } else {
        $email = $_POST["email"];
        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30);

        // Changed this line:
        // $mysqli = require __DIR__ . "/../../php/includes/database.php";
        require_once __DIR__ . "/../../php/includes/database.php";
        $mysqli = $conn; // use $conn from database.php

        if (!$mysqli instanceof mysqli) {
            die("Database connection failed.");
        }
        
        $sql = "UPDATE users
                SET reset_token_hash = ?,
                    reset_token_expires_at = ?
                WHERE email = ?";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sss", $token_hash, $expiry, $email);
        $stmt->execute();

        if ($stmt->affected_rows) {
            $mail = require __DIR__ . "/../../php/auth/mailer.php";

            // Set sender email (must match Gmail account used in mailer.php)
            $mail->setFrom("noreplyneoexclusive@gmail.com", "NeoExclusive");

            // Recipient email
            $mail->addAddress($email);
            $mail->Subject = "Password Reset Request";

            // Email body with reset link
            $mail->Body = <<<END
            <p>Hello,</p>
            <p>Click <a href="http://localhost/NeoExclusiveCafe/php/auth/forgot-pw-reset.php?token=$token">here</a>
            to reset your password.</p>
            <p>This link will expire in 30 minutes.</p>
            END;

            try {
                $mail->send();
                $message = "Password reset email sent. Check your inbox.";
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Message could not be sent. Error: {$mail->ErrorInfo}";
                $messageType = "error";
            }
        } else {
            $message = "No account found with this email.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/NeoExclusiveCafe/css/auth/forgot-pw-reset.css">
    <style>
        /* Alert box styles */
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
        }

        .alert-success { 
            background-color: #28a745; 
        }

        .alert-error { 
            background-color: #dc3545; 
        }

        .show { 
            display: block; 
        
        }
        
        .fade-out { 
            opacity: 0; 
        }
    </style>
</head>

<body>
    <?php if (!empty($message)) : ?>
        <div id="alertBox" class="alert-box alert-<?= $messageType; ?> show">
            <?= htmlspecialchars($message); ?>
        </div>
        <script>
            setTimeout(function() {
                let alertBox = document.getElementById('alertBox');
                alertBox.classList.add('fade-out');
                setTimeout(() => alertBox.style.display = 'none', 2000);
            }, 2000);
        </script>
    <?php endif; ?>

    <div class="center">

        <div class="input-field">  
            <form method="post" action="forgot-password.php">
                <div class="text">
                    <h1 title>Forgot Password</h1>
                    <h4>We will send you an email to reset your password</h4>
                </div>

                <div class="text-field">
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                <button class="send" type="submit">Send</button>
                <a href="login-signup.php"> Back to Login →</a>
            </form>
        </div>
    </div>
</body>
</html>
