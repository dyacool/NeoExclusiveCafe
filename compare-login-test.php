<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Login Comparison Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    pre { background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #007bff; color: white; }
</style>";

if (!$conn) {
    die("<p class='error'>Database connection failed!</p>");
}

// Get user 5 (Aine)
$user_id = 5;
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("<p class='error'>User ID $user_id not found!</p>");
}

echo "<div class='section'>";
echo "<h3>User Information</h3>";
echo "<table>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>ID</td><td>" . $user['id'] . "</td></tr>";
echo "<tr><td>Username</td><td>" . htmlspecialchars($user['username']) . "</td></tr>";
echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
echo "<tr><td>Is Verified</td><td>" . ($user['is_verified'] ? 'Yes' : 'No') . "</td></tr>";
echo "<tr><td>Password Hash</td><td style='font-family: monospace; font-size: 11px;'>" . htmlspecialchars($user['password']) . "</td></tr>";
echo "<tr><td>Hash Length</td><td>" . strlen($user['password']) . "</td></tr>";
echo "</table>";
echo "</div>";

// Test with the password you set
$test_password = "TestPassword123";

echo "<div class='section'>";
echo "<h3>Test 1: Direct Password Verification (Like Test Page)</h3>";
echo "<p><strong>Testing password:</strong> $test_password</p>";

$direct_verify = password_verify($test_password, $user['password']);
echo "<p class='" . ($direct_verify ? "success" : "error") . "'>";
echo $direct_verify ? "✓ Direct verification: PASS" : "❌ Direct verification: FAIL";
echo "</p>";
echo "</div>";

// Test 2: Simulate the exact login query
echo "<div class='section'>";
echo "<h3>Test 2: Simulate Actual Login Query</h3>";

$username = $user['username'];
$password = $test_password;

echo "<p><strong>Username:</strong> $username</p>";
echo "<p><strong>Password:</strong> $password</p>";

// Use the EXACT same query as login-signup.php
$login_sql = "SELECT * FROM users WHERE username = ? AND is_admin = 0";
$login_stmt = mysqli_prepare($conn, $login_sql);

if ($login_stmt === false) {
    echo "<p class='error'>❌ Failed to prepare statement: " . mysqli_error($conn) . "</p>";
} else {
    mysqli_stmt_bind_param($login_stmt, "s", $username);
    
    if (!mysqli_stmt_execute($login_stmt)) {
        echo "<p class='error'>❌ Failed to execute: " . mysqli_stmt_error($login_stmt) . "</p>";
    } else {
        $login_result = mysqli_stmt_get_result($login_stmt);
        $login_user = mysqli_fetch_assoc($login_result);
        
        if (!$login_user) {
            echo "<p class='error'>❌ User not found with query</p>";
        } else {
            echo "<p class='success'>✓ User found</p>";
            echo "<p>Retrieved hash: " . htmlspecialchars($login_user['password']) . "</p>";
            echo "<p>Hash length: " . strlen($login_user['password']) . "</p>";
            
            // Check if hashes match
            if ($user['password'] === $login_user['password']) {
                echo "<p class='success'>✓ Hash matches direct query</p>";
            } else {
                echo "<p class='error'>❌ Hash mismatch!</p>";
            }
            
            // Test password verification
            $login_verify = password_verify($password, $login_user['password']);
            echo "<p class='" . ($login_verify ? "success" : "error") . "'>";
            echo $login_verify ? "✓ Login verification: PASS" : "❌ Login verification: FAIL";
            echo "</p>";
        }
    }
    mysqli_stmt_close($login_stmt);
}
echo "</div>";

// Test 3: Check for whitespace or special characters
echo "<div class='section'>";
echo "<h3>Test 3: Check for Hidden Issues</h3>";

echo "<h4>Password Analysis:</h4>";
echo "<pre>";
echo "Password: '$test_password'\n";
echo "Length: " . strlen($test_password) . "\n";
echo "Trimmed length: " . strlen(trim($test_password)) . "\n";
echo "Hex: " . bin2hex($test_password) . "\n";
echo "Characters: " . implode(', ', array_map('ord', str_split($test_password))) . "\n";
echo "</pre>";

echo "<h4>Username Analysis:</h4>";
echo "<pre>";
echo "Username: '$username'\n";
echo "Length: " . strlen($username) . "\n";
echo "Trimmed length: " . strlen(trim($username)) . "\n";
echo "Hex: " . bin2hex($username) . "\n";
echo "</pre>";

echo "<h4>Hash Analysis:</h4>";
echo "<pre>";
echo "Hash: " . $user['password'] . "\n";
echo "Length: " . strlen($user['password']) . "\n";
echo "First 10 chars: " . substr($user['password'], 0, 10) . "\n";
echo "Last 10 chars: " . substr($user['password'], -10) . "\n";
echo "Contains null: " . (strpos($user['password'], "\0") !== false ? "YES" : "NO") . "\n";
echo "Valid UTF-8: " . (mb_check_encoding($user['password'], 'UTF-8') ? "YES" : "NO") . "\n";
echo "Hash info: " . print_r(password_get_info($user['password']), true);
echo "</pre>";
echo "</div>";

// Test 4: Check session and form data
echo "<div class='section'>";
echo "<h3>Test 4: Form Submission Simulation</h3>";
echo "<p>This simulates what happens when you submit the login form:</p>";

// Simulate POST data
$_POST['username'] = $username;
$_POST['password'] = $password;
$_POST['signin-submit'] = true;

echo "<pre>";
echo "POST data:\n";
echo "username: '" . $_POST['username'] . "'\n";
echo "password: '" . $_POST['password'] . "'\n";
echo "username length: " . strlen($_POST['username']) . "\n";
echo "password length: " . strlen($_POST['password']) . "\n";
echo "</pre>";

// Test with trimmed values (like the actual login does)
$trimmed_username = trim($_POST['username']);
$trimmed_password = $_POST['password']; // Note: password is NOT trimmed in login

echo "<p><strong>After processing (like actual login):</strong></p>";
echo "<pre>";
echo "username (trimmed): '$trimmed_username'\n";
echo "password (not trimmed): '$trimmed_password'\n";
echo "</pre>";

// Test verification with processed values
$final_verify = password_verify($trimmed_password, $user['password']);
echo "<p class='" . ($final_verify ? "success" : "error") . "'>";
echo $final_verify ? "✓ Final verification: PASS" : "❌ Final verification: FAIL";
echo "</p>";

echo "</div>";

// Test 5: Check if there's a JavaScript issue
echo "<div class='section'>";
echo "<h3>Test 5: JavaScript Form Handling Check</h3>";
echo "<p>The login form might be modifying the password before submission.</p>";
echo "<p>Check the browser console for any JavaScript errors when you submit the login form.</p>";
echo "<p>Also check if there's any password field manipulation in the JavaScript.</p>";
echo "</div>";

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p>If all tests above pass but actual login fails, the issue is likely:</p>";
echo "<ul>";
echo "<li>JavaScript modifying the form data before submission</li>";
echo "<li>A different login endpoint being used</li>";
echo "<li>Session or cookie issues</li>";
echo "<li>Browser autofill corrupting the password</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Check <code>view-debug-logs.php</code> and filter by 'LOGIN ATTEMPT'</li>";
echo "<li>Try typing the password manually (don't use autofill)</li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Try in incognito/private mode</li>";
echo "</ol>";

$conn->close();
?>
