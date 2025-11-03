<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Password Reset Issue Diagnostic</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    pre { background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style>";

if (!$conn) {
    die("<p class='error'>❌ Database connection failed!</p>");
}

echo "<p class='success'>✓ Database connected</p>";

// Get user ID 16 (Allysa who reported the issue)
$user_id = 16;
$sql = "SELECT id, username, email, password, is_verified FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("<p class='error'>❌ User ID $user_id not found!</p>");
}

echo "<div class='section'>";
echo "<h3>Current User Info (ID: $user_id)</h3>";
echo "<ul>";
echo "<li><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</li>";
echo "<li><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</li>";
echo "<li><strong>Is Verified:</strong> " . ($user['is_verified'] ? 'Yes' : 'No') . "</li>";
echo "<li><strong>Current Password Hash:</strong> " . htmlspecialchars(substr($user['password'], 0, 40)) . "...</li>";
echo "<li><strong>Hash Length:</strong> " . strlen($user['password']) . " characters</li>";

// Detect hash algorithm
if (preg_match('/^\$2y\$(\d+)\$/', $user['password'], $matches)) {
    $cost = $matches[1];
    echo "<li><strong>Hash Algorithm:</strong> bcrypt with cost $cost</li>";
} else {
    echo "<li class='error'><strong>Hash Algorithm:</strong> Unknown or invalid</li>";
}
echo "</ul>";
echo "</div>";

// Test 1: Create a new password hash and test it
echo "<div class='section'>";
echo "<h3>Test 1: Simulate Password Reset</h3>";
$new_password = "TestNewPassword123";

error_log("=== TEST PASSWORD ISSUE - TEST 1 START ===");
error_log("Test password: $new_password");
error_log("Password length: " . strlen($new_password));

$new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);

error_log("Generated hash: $new_hash");
error_log("Hash length: " . strlen($new_hash));
error_log("Hash algorithm: " . password_get_info($new_hash)['algoName']);

echo "<p><strong>New Test Password:</strong> $new_password</p>";
echo "<p><strong>Password Length:</strong> " . strlen($new_password) . " characters</p>";
echo "<p><strong>Generated Hash:</strong> " . htmlspecialchars($new_hash) . "</p>";
echo "<p><strong>Hash Length:</strong> " . strlen($new_hash) . " characters</p>";
echo "<p><strong>Hash Algorithm:</strong> " . password_get_info($new_hash)['algoName'] . "</p>";

// Immediately verify it works
$verify_immediate = password_verify($new_password, $new_hash);
error_log("Immediate verification: " . ($verify_immediate ? "PASS" : "FAIL"));

echo "<p class='" . ($verify_immediate ? "success" : "error") . "'>";
echo $verify_immediate ? "✓ Immediate verification: PASSED" : "❌ Immediate verification: FAILED";
echo "</p>";

if (!$verify_immediate) {
    error_log("CRITICAL: Immediate verification failed!");
    echo "<p class='error'><strong>CRITICAL ERROR:</strong> Hash verification failed immediately after generation!</p>";
}

// Now simulate storing it in database and retrieving it
$test_sql = "UPDATE users SET password = ? WHERE id = ?";
$test_stmt = $conn->prepare($test_sql);
$test_stmt->bind_param("si", $new_hash, $user_id);

echo "<p><strong>Attempting to update password in database...</strong></p>";
error_log("Updating password in database for user ID: $user_id");

if ($test_stmt->execute()) {
    if ($test_stmt->affected_rows > 0) {
        error_log("Password updated, rows affected: " . $test_stmt->affected_rows);
        echo "<p class='success'>✓ Password updated in database</p>";
        
        // Now retrieve it back
        $verify_sql = "SELECT password FROM users WHERE id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("i", $user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $stored_data = $verify_result->fetch_assoc();
        
        error_log("Retrieved hash from DB: " . $stored_data['password']);
        error_log("Retrieved hash length: " . strlen($stored_data['password']));
        
        echo "<p><strong>Retrieved Hash from DB:</strong> " . htmlspecialchars($stored_data['password']) . "</p>";
        echo "<p><strong>Retrieved Hash Length:</strong> " . strlen($stored_data['password']) . " characters</p>";
        
        // Check if they match
        $hash_match = ($new_hash === $stored_data['password']);
        error_log("Hash match: " . ($hash_match ? "YES" : "NO"));
        
        if ($hash_match) {
            echo "<p class='success'>✓ Hash stored correctly (exact match)</p>";
        } else {
            echo "<p class='error'>❌ Hash mismatch!</p>";
            echo "<pre>Original:  " . htmlspecialchars($new_hash) . "</pre>";
            echo "<pre>Retrieved: " . htmlspecialchars($stored_data['password']) . "</pre>";
            error_log("CRITICAL: Hash mismatch!");
            error_log("Original:  $new_hash");
            error_log("Retrieved: " . $stored_data['password']);
        }
        
        // Test verification with stored hash
        $verify_stored = password_verify($new_password, $stored_data['password']);
        error_log("Verification with stored hash: " . ($verify_stored ? "PASS" : "FAIL"));
        
        echo "<p class='" . ($verify_stored ? "success" : "error") . "'>";
        echo $verify_stored ? "✓ Verification with stored hash: PASSED" : "❌ Verification with stored hash: FAILED";
        echo "</p>";
        
        if (!$verify_stored) {
            error_log("CRITICAL: Stored hash verification failed!");
        }
        
        // Test with wrong password
        $verify_wrong = password_verify("WrongPassword123", $stored_data['password']);
        error_log("Wrong password test: " . ($verify_wrong ? "FAIL (accepted wrong password)" : "PASS (rejected wrong password)"));
        
        echo "<p class='" . (!$verify_wrong ? "success" : "error") . "'>";
        echo !$verify_wrong ? "✓ Wrong password correctly rejected" : "❌ Wrong password incorrectly accepted";
        echo "</p>";
        
    } else {
        error_log("No rows affected");
        echo "<p class='warning'>⚠ No rows affected (password might be the same)</p>";
    }
} else {
    error_log("Update failed: " . $test_stmt->error);
    echo "<p class='error'>❌ Failed to update: " . $test_stmt->error . "</p>";
}

error_log("=== TEST PASSWORD ISSUE - TEST 1 END ===");
echo "</div>";

// Test 2: Check character encoding
echo "<div class='section'>";
echo "<h3>Test 2: Character Encoding</h3>";
$charset = $conn->character_set_name();
echo "<p><strong>Connection Charset:</strong> $charset</p>";
echo "<p class='" . ($charset === 'utf8mb4' ? "success" : "warning") . "'>";
echo $charset === 'utf8mb4' ? "✓ Correct charset" : "⚠ Should be utf8mb4";
echo "</p>";

$result = $conn->query("SHOW VARIABLES LIKE 'character_set%'");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    echo $row['Variable_name'] . ": " . $row['Value'] . "\n";
}
echo "</pre>";
echo "</div>";

// Test 3: Simulate actual login attempt
echo "<div class='section'>";
echo "<h3>Test 3: Simulate Login Attempt</h3>";

error_log("=== TEST PASSWORD ISSUE - TEST 3 START ===");

// Get the current password from database
$login_sql = "SELECT * FROM users WHERE username = ? AND is_admin = 0";
$login_stmt = $conn->prepare($login_sql);
$username = $user['username'];
$login_stmt->bind_param("s", $username);
$login_stmt->execute();
$login_result = $login_stmt->get_result();
$login_user = $login_result->fetch_assoc();

if ($login_user) {
    error_log("User found for login test: $username (ID: " . $login_user['id'] . ")");
    error_log("Hash from DB: " . $login_user['password']);
    error_log("Hash length: " . strlen($login_user['password']));
    
    echo "<p class='success'>✓ User found in database</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Testing with password:</strong> $new_password</p>";
    echo "<p><strong>Hash from DB:</strong> " . htmlspecialchars($login_user['password']) . "</p>";
    
    error_log("Testing password verification for login...");
    $login_verify = password_verify($new_password, $login_user['password']);
    error_log("Login verification result: " . ($login_verify ? "PASS" : "FAIL"));
    
    echo "<p class='" . ($login_verify ? "success" : "error") . "' style='font-size: 16px; padding: 10px;'>";
    echo $login_verify ? "✓✓✓ Login would SUCCEED ✓✓✓" : "❌❌❌ Login would FAIL ❌❌❌";
    echo "</p>";
    
    if (!$login_verify) {
        error_log("CRITICAL: Login would fail!");
        echo "<p class='error'><strong>This is the problem!</strong> The password verification is failing.</p>";
        echo "<p>Let's check the hash details:</p>";
        echo "<pre>";
        echo "Expected password: $new_password\n";
        echo "Password length: " . strlen($new_password) . "\n";
        echo "Hash from DB: " . $login_user['password'] . "\n";
        echo "Hash length: " . strlen($login_user['password']) . "\n";
        echo "Hash bytes (hex): " . bin2hex($login_user['password']) . "\n";
        echo "Hash algorithm: " . password_get_info($login_user['password'])['algoName'] . "\n";
        echo "</pre>";
        
        error_log("Password bytes: " . bin2hex($new_password));
        error_log("Hash bytes: " . bin2hex($login_user['password']));
    } else {
        error_log("SUCCESS: Login would succeed");
    }
} else {
    error_log("User not found during login simulation: $username");
    echo "<p class='error'>❌ User not found during login simulation</p>";
}

error_log("=== TEST PASSWORD ISSUE - TEST 3 END ===");
echo "</div>";

// Test 4: Check for any special characters or encoding issues
echo "<div class='section'>";
echo "<h3>Test 4: Hash Integrity Check</h3>";
$current_hash = $login_user['password'];
echo "<p><strong>Hash Analysis:</strong></p>";
echo "<ul>";
echo "<li>First 10 chars: " . htmlspecialchars(substr($current_hash, 0, 10)) . "</li>";
echo "<li>Last 10 chars: " . htmlspecialchars(substr($current_hash, -10)) . "</li>";
echo "<li>Contains null bytes: " . (strpos($current_hash, "\0") !== false ? "YES (BAD!)" : "NO (Good)") . "</li>";
echo "<li>Is valid UTF-8: " . (mb_check_encoding($current_hash, 'UTF-8') ? "YES" : "NO") . "</li>";
echo "<li>Byte length: " . strlen($current_hash) . "</li>";
echo "<li>Character count: " . mb_strlen($current_hash) . "</li>";
echo "</ul>";

// Check if hash format is valid
$info = password_get_info($current_hash);
echo "<p><strong>Password Info:</strong></p>";
echo "<pre>" . print_r($info, true) . "</pre>";
echo "</div>";

echo "<hr>";
echo "<h3>Summary & Recommendations</h3>";
echo "<p>If Test 3 shows 'Login would fail', that's your issue. Check the details above to see why.</p>";
echo "<p><strong>Common causes:</strong></p>";
echo "<ul>";
echo "<li>Hash truncation during storage</li>";
echo "<li>Character encoding issues</li>";
echo "<li>Extra whitespace or special characters</li>";
echo "<li>Database field too short (should be VARCHAR(255))</li>";
echo "</ul>";

$conn->close();
?>
