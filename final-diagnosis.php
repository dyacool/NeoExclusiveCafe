<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Final Diagnosis - Complete Password Flow Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; border: 2px solid #ddd; }
    .critical { background: #ffebee; border-color: #f44336; }
    pre { background: #fff; padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #007bff; color: white; }
</style>";

$user_id = 5;
$test_password = "Bugoy2766!";

// Get current user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "<div class='section'>";
echo "<h3>Current User State</h3>";
echo "<table>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>ID</td><td>" . $user['id'] . "</td></tr>";
echo "<tr><td>Username</td><td>" . htmlspecialchars($user['username']) . "</td></tr>";
echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
echo "<tr><td>Current Hash</td><td style='font-family: monospace; font-size: 11px;'>" . htmlspecialchars($user['password']) . "</td></tr>";
echo "<tr><td>Hash Length</td><td>" . strlen($user['password']) . "</td></tr>";
echo "</table>";
echo "</div>";

// Test 1: Does current hash work with test password?
echo "<div class='section'>";
echo "<h3>Test 1: Current Hash Verification</h3>";
echo "<p><strong>Testing password:</strong> '$test_password'</p>";
echo "<p><strong>Password length:</strong> " . strlen($test_password) . "</p>";
echo "<p><strong>Password hex:</strong> " . bin2hex($test_password) . "</p>";

$current_verify = password_verify($test_password, $user['password']);
echo "<p class='" . ($current_verify ? "success" : "error") . "' style='font-size: 16px;'>";
echo $current_verify ? "✓ Current hash WORKS with '$test_password'" : "❌ Current hash DOES NOT work with '$test_password'";
echo "</p>";
echo "</div>";

// Test 2: Simulate password reset process
echo "<div class='section'>";
echo "<h3>Test 2: Simulate Password Reset</h3>";

$new_hash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 10]);
echo "<p><strong>Generated new hash:</strong> <code>" . htmlspecialchars($new_hash) . "</code></p>";
echo "<p><strong>Hash length:</strong> " . strlen($new_hash) . "</p>";

// Immediate verification
$immediate_verify = password_verify($test_password, $new_hash);
echo "<p class='" . ($immediate_verify ? "success" : "error") . "'>";
echo "Immediate verification: " . ($immediate_verify ? "✓ PASS" : "❌ FAIL");
echo "</p>";

// Update database
$update_sql = "UPDATE users SET password = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $new_hash, $user_id);

if ($update_stmt->execute()) {
    echo "<p class='success'>✓ Database updated</p>";
    
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
    echo "Stored hash verification: " . ($stored_verify ? "✓ PASS" : "❌ FAIL");
    echo "</p>";
} else {
    echo "<p class='error'>❌ Database update failed: " . $update_stmt->error . "</p>";
}
echo "</div>";

// Test 3: Simulate login process
echo "<div class='section'>";
echo "<h3>Test 3: Simulate Login Process</h3>";

$username = $user['username'];
$login_sql = "SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND is_admin = 0";
$login_stmt = mysqli_prepare($conn, $login_sql);
mysqli_stmt_bind_param($login_stmt, "s", $username);
mysqli_stmt_execute($login_stmt);
$login_result = mysqli_stmt_get_result($login_stmt);
$login_user = mysqli_fetch_assoc($login_result);

if ($login_user) {
    echo "<p class='success'>✓ User found via login query</p>";
    echo "<p><strong>Hash from login query:</strong> <code>" . htmlspecialchars($login_user['password']) . "</code></p>";
    
    $login_verify = password_verify($test_password, $login_user['password']);
    echo "<p class='" . ($login_verify ? "success" : "error") . "' style='font-size: 18px; padding: 15px;'>";
    echo $login_verify ? "✓✓✓ LOGIN WOULD SUCCEED ✓✓✓" : "❌❌❌ LOGIN WOULD FAIL ❌❌❌";
    echo "</p>";
    
    if (!$login_verify) {
        echo "<div class='section critical'>";
        echo "<h4>🔍 Failure Analysis</h4>";
        
        // Compare hashes
        if ($new_hash !== $login_user['password']) {
            echo "<p class='error'>❌ Hash mismatch between what we set and what login retrieved!</p>";
            echo "<pre>";
            echo "Set:      $new_hash\n";
            echo "Retrieved: " . $login_user['password'] . "\n";
            echo "</pre>";
        } else {
            echo "<p class='warning'>⚠ Hashes match but verification still fails!</p>";
            echo "<p>This suggests a PHP configuration issue or corrupted password_verify function.</p>";
        }
        
        // PHP info
        echo "<h4>PHP Configuration</h4>";
        echo "<pre>";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "password_verify exists: " . (function_exists('password_verify') ? "YES" : "NO") . "\n";
        echo "Hash info: " . print_r(password_get_info($login_user['password']), true);
        echo "</pre>";
        echo "</div>";
    }
} else {
    echo "<p class='error'>❌ User not found via login query</p>";
}

mysqli_stmt_close($login_stmt);
echo "</div>";

// Summary
echo "<div class='section' style='background: #e3f2fd; border-color: #2196f3;'>";
echo "<h3>📋 Summary</h3>";
echo "<p><strong>Password being tested:</strong> '$test_password'</p>";
echo "<p><strong>User:</strong> " . htmlspecialchars($user['username']) . " (ID: " . $user['id'] . ")</p>";

if ($stored_verify && $login_verify) {
    echo "<p class='success' style='font-size: 18px;'>✓✓✓ ALL TESTS PASSED - You can now login with '$test_password' ✓✓✓</p>";
} else {
    echo "<p class='error' style='font-size: 18px;'>❌ TESTS FAILED - There is still an issue</p>";
    
    if ($stored_verify && !$login_verify) {
        echo "<p class='warning'>The hash works when retrieved directly but fails in login query. This suggests a query or connection issue.</p>";
    } elseif (!$stored_verify) {
        echo "<p class='error'>The hash doesn't work even after storage. This suggests database corruption or PHP issue.</p>";
    }
}
echo "</div>";

$conn->close();
?>
