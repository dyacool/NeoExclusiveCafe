<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Manual Password Test Tool</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .form-group { margin: 15px 0; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input[type='text'], input[type='password'], input[type='number'] {
        width: 300px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    button { 
        padding: 10px 20px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 10px;
    }
    button:hover { background: #0056b3; }
    .result { 
        margin: 20px 0;
        padding: 15px;
        border-radius: 5px;
        background: #f5f5f5;
    }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
</style>";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='result'>";
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'set_password') {
            $user_id = intval($_POST['user_id']);
            $new_password = $_POST['new_password'];
            
            echo "<h3>Setting New Password</h3>";
            error_log("=== MANUAL PASSWORD SET TEST START ===");
            error_log("User ID: $user_id");
            error_log("New Password: $new_password");
            error_log("Password Length: " . strlen($new_password));
            
            echo "<p>User ID: $user_id</p>";
            echo "<p>New Password: " . htmlspecialchars($new_password) . "</p>";
            echo "<p>Password Length: " . strlen($new_password) . "</p>";
            
            // Generate hash
            $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);
            error_log("Generated Hash: $hash");
            error_log("Hash Length: " . strlen($hash));
            error_log("Hash Algorithm: " . password_get_info($hash)['algoName']);
            
            echo "<p>Generated Hash: " . htmlspecialchars($hash) . "</p>";
            echo "<p>Hash Length: " . strlen($hash) . "</p>";
            echo "<p>Hash Algorithm: " . password_get_info($hash)['algoName'] . "</p>";
            
            // Test immediate verification
            $immediate_test = password_verify($new_password, $hash);
            error_log("Immediate verification: " . ($immediate_test ? "PASS" : "FAIL"));
            echo "<p class='" . ($immediate_test ? "success" : "error") . "'>";
            echo "Immediate verification: " . ($immediate_test ? "✓ PASS" : "❌ FAIL");
            echo "</p>";
            
            if (!$immediate_test) {
                error_log("CRITICAL: Immediate verification failed!");
                echo "<p class='error'><strong>CRITICAL:</strong> Hash verification failed immediately after generation!</p>";
                error_log("=== MANUAL PASSWORD SET TEST END ===");
                echo "</div>";
                return;
            }
            
            // Update database
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hash, $user_id);
            
            error_log("Executing database update...");
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    error_log("Update successful, rows affected: " . $stmt->affected_rows);
                    echo "<p class='success'>✓ Password updated successfully!</p>";
                    
                    // Verify it was stored correctly
                    $verify_sql = "SELECT password FROM users WHERE id = ?";
                    $verify_stmt = $conn->prepare($verify_sql);
                    $verify_stmt->bind_param("i", $user_id);
                    $verify_stmt->execute();
                    $result = $verify_stmt->get_result();
                    $stored = $result->fetch_assoc();
                    
                    error_log("Retrieved hash from DB: " . $stored['password']);
                    error_log("Retrieved hash length: " . strlen($stored['password']));
                    error_log("Hash match: " . ($hash === $stored['password'] ? "YES" : "NO"));
                    
                    echo "<p>Retrieved Hash: " . htmlspecialchars($stored['password']) . "</p>";
                    echo "<p>Retrieved Hash Length: " . strlen($stored['password']) . "</p>";
                    
                    if ($hash === $stored['password']) {
                        echo "<p class='success'>✓ Hash stored correctly (exact match)</p>";
                    } else {
                        echo "<p class='error'>❌ Hash mismatch after storage!</p>";
                        echo "<pre>Original:  " . htmlspecialchars($hash) . "</pre>";
                        echo "<pre>Retrieved: " . htmlspecialchars($stored['password']) . "</pre>";
                        error_log("CRITICAL: Hash mismatch!");
                        error_log("Original:  $hash");
                        error_log("Retrieved: " . $stored['password']);
                    }
                    
                    // Test verification with stored hash
                    $stored_test = password_verify($new_password, $stored['password']);
                    error_log("Verification with stored hash: " . ($stored_test ? "PASS" : "FAIL"));
                    echo "<p class='" . ($stored_test ? "success" : "error") . "'>";
                    echo "Verification with stored hash: " . ($stored_test ? "✓ PASS" : "❌ FAIL");
                    echo "</p>";
                    
                    if (!$stored_test) {
                        error_log("CRITICAL: Stored hash verification failed!");
                    }
                } else {
                    error_log("No rows updated");
                    echo "<p class='error'>❌ No rows updated</p>";
                }
            } else {
                error_log("Update failed: " . $stmt->error);
                echo "<p class='error'>❌ Update failed: " . $stmt->error . "</p>";
            }
            
            error_log("=== MANUAL PASSWORD SET TEST END ===");
            
        } elseif ($action === 'test_login') {
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            echo "<h3>Testing Login</h3>";
            error_log("=== MANUAL LOGIN TEST START ===");
            error_log("Username: $username");
            error_log("Password: $password");
            error_log("Password Length: " . strlen($password));
            
            echo "<p>Username: " . htmlspecialchars($username) . "</p>";
            echo "<p>Password: " . htmlspecialchars($password) . "</p>";
            echo "<p>Password Length: " . strlen($password) . "</p>";
            
            // Get user
            $sql = "SELECT * FROM users WHERE username = ? AND is_admin = 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                error_log("User not found: $username");
                echo "<p class='error'>❌ User not found</p>";
            } else {
                error_log("User found - ID: " . $user['id']);
                error_log("Stored hash: " . $user['password']);
                error_log("Stored hash length: " . strlen($user['password']));
                error_log("Hash algorithm: " . password_get_info($user['password'])['algoName']);
                
                echo "<p class='success'>✓ User found (ID: " . $user['id'] . ")</p>";
                echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
                echo "<p>Is Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "</p>";
                echo "<p>Stored Hash: " . htmlspecialchars(substr($user['password'], 0, 40)) . "...</p>";
                echo "<p>Full Hash: " . htmlspecialchars($user['password']) . "</p>";
                echo "<p>Hash Length: " . strlen($user['password']) . "</p>";
                echo "<p>Hash Algorithm: " . password_get_info($user['password'])['algoName'] . "</p>";
                
                // Test password
                error_log("Testing password verification...");
                $verify = password_verify($password, $user['password']);
                error_log("Verification result: " . ($verify ? "PASS" : "FAIL"));
                
                if ($verify) {
                    echo "<p class='success' style='font-size: 18px; padding: 15px;'>✓✓✓ PASSWORD CORRECT - LOGIN WOULD SUCCEED ✓✓✓</p>";
                    error_log("SUCCESS: Login would succeed");
                } else {
                    echo "<p class='error' style='font-size: 18px; padding: 15px;'>❌❌❌ PASSWORD INCORRECT - LOGIN WOULD FAIL ❌❌❌</p>";
                    error_log("FAILURE: Login would fail");
                    
                    // Additional debugging
                    echo "<h4>Debug Info:</h4>";
                    echo "<pre>";
                    echo "Password length: " . strlen($password) . "\n";
                    echo "Password bytes (hex): " . bin2hex($password) . "\n";
                    echo "Password chars: " . implode(',', array_map('ord', str_split($password))) . "\n";
                    echo "\nHash info:\n" . print_r(password_get_info($user['password']), true);
                    echo "\nHash format check: " . (preg_match('/^\$2y\$\d+\$/', $user['password']) ? "Valid bcrypt" : "Invalid format") . "\n";
                    echo "</pre>";
                    
                    error_log("Password bytes: " . bin2hex($password));
                    error_log("Hash format: " . (preg_match('/^\$2y\$\d+\$/', $user['password']) ? "Valid bcrypt" : "Invalid"));
                    
                    // Try to identify the issue
                    echo "<h4>Possible Issues:</h4>";
                    echo "<ul>";
                    if (strlen($user['password']) !== 60) {
                        echo "<li class='error'>Hash length is " . strlen($user['password']) . " (should be 60 for bcrypt)</li>";
                        error_log("ISSUE: Hash length is " . strlen($user['password']) . " instead of 60");
                    }
                    if (!preg_match('/^\$2y\$\d+\$/', $user['password'])) {
                        echo "<li class='error'>Hash format doesn't match bcrypt pattern</li>";
                        error_log("ISSUE: Hash format invalid");
                    }
                    if (strpos($user['password'], "\0") !== false) {
                        echo "<li class='error'>Hash contains null bytes (encoding issue)</li>";
                        error_log("ISSUE: Hash contains null bytes");
                    }
                    echo "</ul>";
                }
            }
            
            error_log("=== MANUAL LOGIN TEST END ===");
        }
    }
    
    echo "</div>";
}

?>

<h3>Tool 1: Set Password for User</h3>
<form method="post">
    <input type="hidden" name="action" value="set_password">
    <div class="form-group">
        <label>User ID:</label>
        <input type="number" name="user_id" value="5" required>
    </div>
    <div class="form-group">
        <label>New Password:</label>
        <input type="text" name="new_password" value="TestPassword123" required>
    </div>
    <button type="submit">Set Password</button>
</form>

<hr>

<h3>Tool 2: Test Login</h3>
<form method="post">
    <input type="hidden" name="action" value="test_login">
    <div class="form-group">
        <label>Username:</label>
        <input type="text" name="username" value="Dyayin" required>
    </div>
    <div class="form-group">
        <label>Password:</label>
        <input type="text" name="password" value="TestPassword123" required>
    </div>
    <button type="submit">Test Login</button>
</form>

<hr>

<h3>Instructions:</h3>
<ol>
    <li><strong>Set a test password:</strong> Use Tool 1 to set a known password for user ID 16</li>
    <li><strong>Test the login:</strong> Use Tool 2 to verify the password works</li>
    <li><strong>Try actual login:</strong> Go to the login page and try logging in with the same credentials</li>
    <li>If Tool 2 succeeds but actual login fails, there's an issue in the login code</li>
    <li>If Tool 2 fails, there's an issue with password storage/verification</li>
</ol>

<?php $conn->close(); ?>
