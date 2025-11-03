<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>password_verify() Function Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    pre { background: #fff; padding: 10px; border: 1px solid #ddd; font-family: monospace; }
</style>";

// Test 1: Basic password_verify test
echo "<div class='section'>";
echo "<h3>Test 1: Basic Function Test</h3>";

$test_pwd = "test123";
$test_hash = password_hash($test_pwd, PASSWORD_BCRYPT, ['cost' => 10]);

echo "<p><strong>Test Password:</strong> $test_pwd</p>";
echo "<p><strong>Generated Hash:</strong> <code>$test_hash</code></p>";

$result = password_verify($test_pwd, $test_hash);
echo "<p class='" . ($result ? "success" : "error") . "'>";
echo "Verification: " . ($result ? "✓ PASS (function works)" : "❌ FAIL (function broken!)");
echo "</p>";
echo "</div>";

// Test 2: Use the EXACT values from the logs
echo "<div class='section'>";
echo "<h3>Test 2: Exact Values from Logs</h3>";

$password = "Bugoy2766!";
$hash = '$2y$10$K85BrCifDXKKED9QGbr92u2sYwate57O4LEDt3hbDxqyBIYtC85tm';

echo "<p><strong>Password:</strong> $password</p>";
echo "<p><strong>Password Hex:</strong> " . bin2hex($password) . "</p>";
echo "<p><strong>Hash:</strong> <code>$hash</code></p>";

$result = password_verify($password, $hash);
echo "<p class='" . ($result ? "success" : "error") . "' style='font-size: 18px;'>";
echo "Verification: " . ($result ? "✓✓✓ PASS ✓✓✓" : "❌❌❌ FAIL ❌❌❌");
echo "</p>";

if (!$result) {
    echo "<p class='error'><strong>CRITICAL:</strong> password_verify() fails with the exact values from logs!</p>";
    
    // Try to understand why
    echo "<h4>Debugging</h4>";
    echo "<pre>";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "password_verify exists: " . (function_exists('password_verify') ? "YES" : "NO") . "\n";
    echo "Hash info: " . print_r(password_get_info($hash), true);
    echo "</pre>";
}
echo "</div>";

// Test 3: Generate a NEW hash for the same password and test
echo "<div class='section'>";
echo "<h3>Test 3: Generate New Hash</h3>";

$new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
echo "<p><strong>New Hash:</strong> <code>$new_hash</code></p>";

$new_result = password_verify($password, $new_hash);
echo "<p class='" . ($new_result ? "success" : "error") . "'>";
echo "Verification with new hash: " . ($new_result ? "✓ PASS" : "❌ FAIL");
echo "</p>";

// Compare hashes
echo "<h4>Hash Comparison</h4>";
echo "<pre>";
echo "Old hash: $hash\n";
echo "New hash: $new_hash\n";
echo "Are they equal: " . ($hash === $new_hash ? "YES" : "NO (this is normal)") . "\n";
echo "</pre>";
echo "</div>";

// Test 4: Check if there's a character encoding issue
echo "<div class='section'>";
echo "<h3>Test 4: Character Encoding Check</h3>";

echo "<p><strong>Password encoding:</strong> " . mb_detect_encoding($password) . "</p>";
echo "<p><strong>Hash encoding:</strong> " . mb_detect_encoding($hash) . "</p>";

// Try with explicit UTF-8
$password_utf8 = mb_convert_encoding($password, 'UTF-8');
$result_utf8 = password_verify($password_utf8, $hash);

echo "<p>Verification with UTF-8 encoded password: " . ($result_utf8 ? "✓ PASS" : "❌ FAIL") . "</p>";
echo "</div>";

// Test 5: PHP Configuration
echo "<div class='section'>";
echo "<h3>Test 5: PHP Configuration</h3>";
echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Bcrypt Cost: " . PASSWORD_BCRYPT_DEFAULT_COST . "\n";
echo "Default Algorithm: " . PASSWORD_DEFAULT . "\n";
echo "Bcrypt Algorithm: " . PASSWORD_BCRYPT . "\n";
echo "</pre>";
echo "</div>";

?>
