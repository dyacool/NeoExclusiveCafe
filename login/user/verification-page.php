<?php
session_start();
require_once "../../php/includes/database.php";

// Set timezone
date_default_timezone_set('Asia/Manila');

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /NeoExclusiveCafe/pages/users/user-dashboard.php");
    exit();
}

// Check if we have an unverified email from login attempt
$email = isset($_SESSION['unverified_email']) ? $_SESSION['unverified_email'] : '';

$errorMessage = '';
$successMessage = '';

// Handle resend verification email
if (isset($_POST['resend-verification'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $errorMessage = "Please enter your email address.";
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);

        try {
            // Check if email exists and is unverified
            $stmt = mysqli_prepare($conn, "SELECT id, firstname, verification_token_expires_at FROM users WHERE email = ? AND is_verified = FALSE");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user) {
                // Check if the last token was generated less than 1 minute ago
                $last_token_time = strtotime($user['verification_token_expires_at']) - 1800; // 30 minutes ago
                $current_time = time();
                
                if ($last_token_time && ($current_time - $last_token_time) < 60) {
                    throw new Exception("Please wait at least 1 minute before requesting another verification link.");
                }

                // Generate new verification token
                $token = bin2hex(random_bytes(32));
                $token_hash = hash("sha256", $token);
                $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

                // Update token in database
                $update_stmt = mysqli_prepare($conn, "UPDATE users SET verification_token = ?, verification_token_expires_at = ? WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, "ssi", $token_hash, $expiry, $user['id']);
                
                if (!mysqli_stmt_execute($update_stmt)) {
                    throw new Exception("Failed to update verification token");
                }
                mysqli_stmt_close($update_stmt);

                // Send verification email
                $mail = require __DIR__ . "/../../php/auth/mailer.php";
                $mail->setFrom("noreplyneoexclusive@gmail.com", "NeoExclusiveCafe");
                $mail->addAddress($email);
                $mail->Subject = "Email Verification";
                $verificationLink = "http://localhost/NeoExclusiveCafe/php/auth/verify-email.php?token=" . $token;
                $mail->Body = <<<END
                <p>Hello {$user['firstname']},</p>
                <p>We've received a request to resend your verification email. Please click the link below to verify your email address:</p>
                <p><a href="$verificationLink">Verify Email</a></p>
                <p>This link will expire in 30 minutes (at {$expiry}).</p>
                <p>If you can't click the link, copy and paste this URL into your browser:</p>
                <p>$verificationLink</p>
                END;

                if (!$mail->send()) {
                    throw new Exception("Failed to send verification email");
                }

                mysqli_commit($conn);
                $successMessage = "A new verification email has been sent to your email address. Please check your inbox and spam folder. The link will expire at " . date('h:i A', strtotime($expiry));
                
            } else {
                throw new Exception("No unverified account found with this email address.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errorMessage = $e->getMessage();
        }
    }
}

// Clear the session email after it's been used
if (isset($_SESSION['unverified_email'])) {
    unset($_SESSION['unverified_email']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Required - NeoExclusiveCafe</title>
    <link rel="stylesheet" href="/NeoExclusiveCafe/css/auth/verification-page.css">
</head>
<body>
    <div class="verification-container">
        <div class="verification-header">
            <h1>Email Verification Required</h1>
            <p class="header-text">Your account needs to be verified before you can log in. Please check your email for the verification link we sent you.</p>
        </div>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert success">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <form class="verification-form" method="post">
            <p>Didn't receive the verification email?</p>
            <p>Enter your email address below to resend it.</p>
            <input type="email" name="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($email); ?>" required>
            <button type="submit" name="resend-verification">Resend Verification Email</button>
        </form>

        <div class="back-to-login">
            <a href="login-signup.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
