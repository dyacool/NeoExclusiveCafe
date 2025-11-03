<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Password Update History Check</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #007bff; color: white; }
</style>";

$user_id = 5; // Aine

// Get current user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "<div class='section'>";
echo "<h3>Current User Data (ID: $user_id)</h3>";
echo "<table>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>Username</td><td>" . htmlspecialchars($user['username']) . "</td></tr>";
echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
echo "<tr><td>Password Hash</td><td style='font-family: monospace; font-size: 11px;'>" . htmlspecialchars($user['password']) . "</td></tr>";
echo "<tr><td>Hash Length</td><td>" . strlen($user['password']) . "</td></tr>";
echo "<tr><td>Reset Token</td><td>" . ($user['reset_token_hash'] ? "SET" : "NULL") . "</td></tr>";
echo "<tr><td>Reset Token Expires</td><td>" . ($user['reset_token_expires_at'] ?? "NULL") . "</td></tr>";
echo "<tr><td>Is Verified</td><td>" . ($user['is_verified'] ? "Yes" : "No") . "</td></tr>";
echo "</table>";
echo "</div>";

// Check if this hash matches what we expect from manual test
$expected_test_hash = '$2y$10$9tgmCWOtdWmhaxM9vAhYIO5.F33nPUaEtI7qqWPaMbJ3Ko2VHCj6G';
$is_same = ($user['password'] === $expected_test_hash);

echo "<div class='section'>";
echo "<h3>Hash Comparison</h3>";
echo "<p><strong>Current hash in DB:</strong></p>";
echo "<code>" . htmlspecialchars($user['password']) . "</code>";
echo "<p><strong>Expected from test:</strong></p>";
echo "<code>" . htmlspecialchars($expected_test_hash) . "</code>";
echo "<p class='" . ($is_same ? "success" : "warning") . "'>";
echo $is_same ? "✓ Hashes match - this is the hash from the test" : "⚠ Hashes are different - password was changed after the test";
echo "</p>";
echo "</div>";

// Now let's manually set the password to TestPassword123 and verify
echo "<div class='section'>";
echo "<h3>Set Password to TestPassword123</h3>";

if (isset($_POST['set_test_password'])) {
    $test_password = "TestPassword123";
    $new_hash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    echo "<p><strong>Generating new hash for:</strong> $test_password</p>";
    echo "<p><strong>New hash:</strong> <code>" . htmlspecialchars($new_hash) . "</code></p>";
    
    // Verify immediately
    $immediate_verify = password_verify($test_password, $new_hash);
    echo "<p class='" . ($immediate_verify ? "success" : "error") . "'>";
    echo "Immediate verification: " . ($immediate_verify ? "✓ PASS" : "✗ FAIL");
    echo "</p>";
    
    if ($immediate_verify) {
        // Update database
        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_hash, $user_id);
        
        if ($update_stmt->execute()) {
            echo "<p class='success'>✓ Password updated in database</p>";
            
            // Retrieve and verify
            $verify_sql = "SELECT password FROM users WHERE id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("i", $user_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $stored = $verify_result->fetch_assoc();
            
            echo "<p><strong>Retrieved hash:</strong> <code>" . htmlspecialchars($stored['password']) . "</code></p>";
            
            $stored_verify = password_verify($test_password, $stored['password']);
            echo "<p class='" . ($stored_verify ? "success" : "error") . "' style='font-size: 16px;'>";
            echo $stored_verify ? "✓✓✓ VERIFICATION SUCCESSFUL! You can now login with 'TestPassword123' ✓✓✓" : "✗✗✗ VERIFICATION FAILED ✗✗✗";
            echo "</p>";
            
            if ($stored_verify) {
                echo "<p class='success' style='padding: 15px; background: #d4edda; border: 2px solid #28a745;'>";
                echo "<strong>SUCCESS!</strong> Password is now set to: <strong>TestPassword123</strong><br>";
                echo "Username: <strong>" . htmlspecialchars($user['username']) . "</strong><br>";
                echo "You can now login at: <a href='/frontend/login/user/login-signup.php'>/frontend/login/user/login-signup.php</a>";
                echo "</p>";
            }
        } else {
            echo "<p class='error'>✗ Failed to update: " . $update_stmt->error . "</p>";
        }
    }
} else {
    echo "<form method='post'>";
    echo "<button type='submit' name='set_test_password' style='padding: 15px 30px; background: #28a745; color: white; border: none; cursor: pointer; font-size: 16px;'>";
    echo "Set Password to TestPassword123";
    echo "</button>";
    echo "</form>";
}

echo "</div>";

$conn->close();
?>
