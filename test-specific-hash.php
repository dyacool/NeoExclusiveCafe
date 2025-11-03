<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Specific Hash Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    pre { background: #fff; padding: 10px; border: 1px solid #ddd; }
</style>";

// The exact password from logs
$password = "Bugoy2766!";

// The exact hash from logs
$hash = '$2y$10$f9XhVtFBQs1Awz3GaL.aguSV6/aKN3oJFuYIm8CXXQL45gT3hxxbS';

echo "<div class='section'>";
echo "<h3>Test Data from Logs</h3>";
echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
echo "<p><strong>Password Length:</strong> " . strlen($password) . "</p>";
echo "<p><strong>Password Hex:</strong> " . bin2hex($password) . "</p>";
echo "<p><strong>Password Bytes:</strong> " . implode(',', array_map('ord', str_split($password))) . "</p>";
echo "<p><strong>Hash:</strong> <code>" . htmlspecialchars($hash) . "</code></p>";
echo "<p><strong>Hash Length:</strong> " . strlen($hash) . "</p>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>Direct Verification Test</h3>";

$result = password_verify($password, $hash);

echo "<p class='" . ($result ? "success" : "error") . "' style='font-size: 18px; padding: 15px;'>";
echo $result ? "✓✓✓ VERIFICATION PASSED ✓✓✓" : "❌❌❌ VERIFICATION FAILED ❌❌❌";
echo "</p>";

if (!$result) {
    echo "<p class='error'><strong>This is very strange!</strong> The password should match the hash based on the logs.</p>";
    
    // Try to regenerate the hash
    echo "<h4>Regenerating Hash</h4>";
    $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    echo "<p><strong>New Hash:</strong> <code>" . htmlspecialchars($new_hash) . "</code></p>";
    
    $new_verify = password_verify($password, $new_hash);
    echo "<p class='" . ($new_verify ? "success" : "error") . "'>";
    echo "New hash verification: " . ($new_verify ? "✓ PASS" : "✗ FAIL");
    echo "</p>";
    
    // Check if hashes are different
    if ($hash !== $new_hash) {
        echo "<p>The hashes are different (this is normal for bcrypt), but both should verify the same password.</p>";
    }
}
echo "</div>";

// Test with database
require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<div class='section'>";
echo "<h3>Database Check</h3>";

$user_id = 5;
$sql = "SELECT username, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "<p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>";
echo "<p><strong>Hash in DB:</strong> <code>" . htmlspecialchars($user['password']) . "</code></p>";
echo "<p><strong>Hash Length:</strong> " . strlen($user['password']) . "</p>";

// Compare hashes
if ($hash === $user['password']) {
    echo "<p class='success'>✓ Hash in DB matches the hash from logs</p>";
} else {
    echo "<p class='error'>❌ Hash in DB is DIFFERENT from logs!</p>";
    echo "<pre>";
    echo "Expected: $hash\n";
    echo "In DB:    " . $user['password'] . "\n";
    echo "</pre>";
}

// Test verification with DB hash
$db_verify = password_verify($password, $user['password']);
echo "<p class='" . ($db_verify ? "success" : "error") . "' style='font-size: 16px;'>";
echo "Verification with DB hash: " . ($db_verify ? "✓ PASS" : "❌ FAIL");
echo "</p>";

echo "</div>";

// Character-by-character analysis
echo "<div class='section'>";
echo "<h3>Character Analysis</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Position</th><th>Character</th><th>ASCII</th><th>Hex</th></tr>";

for ($i = 0; $i < strlen($password); $i++) {
    $char = $password[$i];
    $ascii = ord($char);
    $hex = dechex($ascii);
    echo "<tr><td>$i</td><td>" . htmlspecialchars($char) . "</td><td>$ascii</td><td>$hex</td></tr>";
}

echo "</table>";
echo "</div>";

// Test if there are any hidden characters
echo "<div class='section'>";
echo "<h3>Hidden Character Check</h3>";
$clean_password = preg_replace('/[^\x20-\x7E]/', '', $password);
echo "<p><strong>Original length:</strong> " . strlen($password) . "</p>";
echo "<p><strong>Clean length:</strong> " . strlen($clean_password) . "</p>";

if ($password === $clean_password) {
    echo "<p class='success'>✓ No hidden characters found</p>";
} else {
    echo "<p class='error'>❌ Hidden characters detected!</p>";
}
echo "</div>";

$conn->close();
?>
