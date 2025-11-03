<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Generate Password Reset Token</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .link-box { background: #e3f2fd; padding: 20px; border: 2px solid #2196f3; border-radius: 5px; margin: 15px 0; }
    .link-box a { color: #1976d2; font-size: 14px; word-break: break-all; }
    input { padding: 8px; margin: 5px; width: 100px; }
    button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
</style>";

if (!$conn) {
    die("<p class='error'>Database connection failed!</p>");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // Get user info
    $sql = "SELECT id, username, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo "<p class='error'>❌ User ID $user_id not found!</p>";
    } else {
        echo "<div class='section'>";
        echo "<h3>User Information</h3>";
        echo "<p><strong>ID:</strong> " . $user['id'] . "</p>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
        echo "</div>";
        
        // Generate token
        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 minutes
        
        error_log("=== GENERATING RESET TOKEN ===");
        error_log("User ID: " . $user['id']);
        error_log("Username: " . $user['username']);
        error_log("Token: $token");
        error_log("Token Hash: $token_hash");
        error_log("Expiry: $expiry");
        
        // Update database
        $update_sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $token_hash, $expiry, $user_id);
        
        if ($update_stmt->execute()) {
            echo "<div class='section'>";
            echo "<h3 class='success'>✓ Reset Token Generated Successfully!</h3>";
            echo "<p><strong>Token:</strong> <code>$token</code></p>";
            echo "<p><strong>Token Hash:</strong> <code>$token_hash</code></p>";
            echo "<p><strong>Expires At:</strong> $expiry</p>";
            echo "</div>";
            
            // Generate reset link
            $reset_link = "https://www.neocafe.shop/frontend/login/user/forgot-pw-reset.php?token=$token";
            
            echo "<div class='link-box'>";
            echo "<h3>🔗 Password Reset Link</h3>";
            echo "<p>Click the link below to reset the password:</p>";
            echo "<p><a href='$reset_link' target='_blank'>$reset_link</a></p>";
            echo "<p style='margin-top: 15px;'><strong>Or copy this link:</strong></p>";
            echo "<input type='text' value='$reset_link' style='width: 100%; padding: 10px; font-family: monospace;' onclick='this.select()' readonly>";
            echo "</div>";
            
            echo "<div class='section'>";
            echo "<h3>Instructions:</h3>";
            echo "<ol>";
            echo "<li>Click the reset link above (or copy and paste it in a new tab)</li>";
            echo "<li>Enter your new password (whatever you want)</li>";
            echo "<li>Confirm the password</li>";
            echo "<li>Submit the form</li>";
            echo "<li>Check <code>view-debug-logs.php</code> and filter by 'PASSWORD RESET DEBUG'</li>";
            echo "<li>Try logging in with your new password</li>";
            echo "<li>If login fails, check logs again and filter by 'LOGIN ATTEMPT DEBUG'</li>";
            echo "</ol>";
            echo "</div>";
            
            error_log("Reset link generated: $reset_link");
            error_log("=== RESET TOKEN GENERATION COMPLETE ===");
        } else {
            echo "<p class='error'>❌ Failed to update database: " . $update_stmt->error . "</p>";
            error_log("Failed to generate reset token: " . $update_stmt->error);
        }
    }
}

// Show form
echo "<div class='section'>";
echo "<h3>Generate Reset Token for User</h3>";
echo "<form method='post'>";
echo "<label>User ID:</label>";
echo "<input type='number' name='user_id' value='5' required>";
echo "<button type='submit'>Generate Reset Token</button>";
echo "</form>";
echo "</div>";

// Show current users
echo "<div class='section'>";
echo "<h3>Available Users</h3>";
$users_sql = "SELECT id, username, email, reset_token_hash, reset_token_expires_at FROM users WHERE is_admin = 0 ORDER BY id";
$users_result = $conn->query($users_sql);

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Has Token</th><th>Token Expires</th></tr>";

while ($row = $users_result->fetch_assoc()) {
    $has_token = $row['reset_token_hash'] ? 'Yes' : 'No';
    $expires = $row['reset_token_expires_at'] ?? 'N/A';
    
    // Check if token is expired
    $is_expired = false;
    if ($row['reset_token_expires_at']) {
        $is_expired = strtotime($row['reset_token_expires_at']) <= time();
    }
    
    $token_status = $has_token === 'Yes' ? ($is_expired ? 'Expired' : 'Valid') : 'None';
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . $token_status . "</td>";
    echo "<td>" . $expires . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

$conn->close();
?>
