<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Hash Comparison Debug</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    pre { background: #fff; padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; }
</style>";

$password = "Bugoy2766!";
$user_id = 5;

// Get hash from database
$sql = "SELECT password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$db_hash = $user['password'];

echo "<div class='section'>";
echo "<h3>Password Test</h3>";
echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
echo "<p><strong>Password Hex:</strong> " . bin2hex($password) . "</p>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>Hash from Database</h3>";
echo "<pre>" . htmlspecialchars($db_hash) . "</pre>";
echo "<p><strong>Length:</strong> " . strlen($db_hash) . "</p>";
echo "<p><strong>Hex (first 40 chars):</strong> " . bin2hex(substr($db_hash, 0, 40)) . "</p>";

$db_verify = password_verify($password, $db_hash);
echo "<p class='" . ($db_verify ? "success" : "error") . "' style='font-size: 18px;'>";
echo "Verification: " . ($db_verify ? "✓✓✓ PASS ✓✓✓" : "❌ FAIL");
echo "</p>";
echo "</div>";

// Now test what happens during actual login
echo "<div class='section'>";
echo "<h3>Simulating Actual Login Process</h3>";

$username = "dyayin";

// Use exact same query as login
$login_sql = "SELECT * FROM users WHERE username = ? AND is_admin = 0";
$login_stmt = mysqli_prepare($conn, $login_sql);
mysqli_stmt_bind_param($login_stmt, "s", $username);
mysqli_stmt_execute($login_stmt);
$login_result = mysqli_stmt_get_result($login_stmt);
$login_user = mysqli_fetch_assoc($login_result);

if ($login_user) {
    echo "<p class='success'>✓ User found</p>";
    echo "<p><strong>Hash from login query:</strong></p>";
    echo "<pre>" . htmlspecialchars($login_user['password']) . "</pre>";
    echo "<p><strong>Length:</strong> " . strlen($login_user['password']) . "</p>";
    
    // Compare hashes byte by byte
    echo "<h4>Hash Comparison</h4>";
    if ($db_hash === $login_user['password']) {
        echo "<p class='success'>✓ Hashes are identical</p>";
    } else {
        echo "<p class='error'>❌ Hashes are DIFFERENT!</p>";
        echo "<pre>";
        echo "DB hash:    " . bin2hex($db_hash) . "\n";
        echo "Login hash: " . bin2hex($login_user['password']) . "\n";
        echo "</pre>";
    }
    
    // Test verification
    $login_verify = password_verify($password, $login_user['password']);
    echo "<p class='" . ($login_verify ? "success" : "error") . "' style='font-size: 18px;'>";
    echo "Login verification: " . ($login_verify ? "✓✓✓ PASS ✓✓✓" : "❌ FAIL");
    echo "</p>";
    
    if (!$login_verify) {
        echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
        echo "<h4>Debugging Failed Verification</h4>";
        
        // Check password_get_info
        $hash_info = password_get_info($login_user['password']);
        echo "<p><strong>Hash Info:</strong></p>";
        echo "<pre>" . print_r($hash_info, true) . "</pre>";
        
        // Try with different password variations
        echo "<h4>Testing Password Variations</h4>";
        $variations = [
            $password,
            trim($password),
            strtolower($password),
            ucfirst(strtolower($password)),
        ];
        
        foreach ($variations as $var) {
            $test = password_verify($var, $login_user['password']);
            echo "<p>" . htmlspecialchars($var) . ": " . ($test ? "✓" : "✗") . "</p>";
        }
        echo "</div>";
    }
}

mysqli_stmt_close($login_stmt);
echo "</div>";

// Test if password_verify is working at all
echo "<div class='section'>";
echo "<h3>PHP password_verify() Function Test</h3>";

$test_pwd = "test123";
$test_hash = password_hash($test_pwd, PASSWORD_BCRYPT, ['cost' => 10]);
$test_verify = password_verify($test_pwd, $test_hash);

echo "<p><strong>Test password:</strong> $test_pwd</p>";
echo "<p><strong>Test hash:</strong> <code>" . htmlspecialchars($test_hash) . "</code></p>";
echo "<p class='" . ($test_verify ? "success" : "error") . "'>";
echo "Test verification: " . ($test_verify ? "✓ PASS (function works)" : "❌ FAIL (function broken!)");
echo "</p>";
echo "</div>";

$conn->close();
?>
