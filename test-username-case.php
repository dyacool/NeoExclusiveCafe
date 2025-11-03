<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Username Case Sensitivity Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

$password = "Bugoy2766!";

echo "<div class='section'>";
echo "<h3>Test 1: Lowercase username</h3>";

$username_lower = "dyayin";
$sql = "SELECT * FROM users WHERE username = ? AND is_admin = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $username_lower);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user) {
    echo "<p class='success'>✓ User found with lowercase 'dyayin'</p>";
    echo "<p><strong>Actual username in DB:</strong> " . htmlspecialchars($user['username']) . "</p>";
    
    $verify = password_verify($password, $user['password']);
    echo "<p class='" . ($verify ? "success" : "error") . "'>";
    echo "Password verification: " . ($verify ? "✓ PASS" : "❌ FAIL");
    echo "</p>";
} else {
    echo "<p class='error'>❌ User NOT found with lowercase 'dyayin'</p>";
}
mysqli_stmt_close($stmt);
echo "</div>";

echo "<div class='section'>";
echo "<h3>Test 2: Capitalized username</h3>";

$username_cap = "Dyayin";
$sql = "SELECT * FROM users WHERE username = ? AND is_admin = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $username_cap);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user) {
    echo "<p class='success'>✓ User found with capitalized 'Dyayin'</p>";
    echo "<p><strong>Actual username in DB:</strong> " . htmlspecialchars($user['username']) . "</p>";
    
    $verify = password_verify($password, $user['password']);
    echo "<p class='" . ($verify ? "success" : "error") . "'>";
    echo "Password verification: " . ($verify ? "✓ PASS" : "❌ FAIL");
    echo "</p>";
} else {
    echo "<p class='error'>❌ User NOT found with capitalized 'Dyayin'</p>";
}
mysqli_stmt_close($stmt);
echo "</div>";

echo "<div class='section'>";
echo "<h3>Database Collation Check</h3>";

$collation_sql = "SHOW FULL COLUMNS FROM users WHERE Field = 'username'";
$collation_result = $conn->query($collation_sql);
$collation_info = $collation_result->fetch_assoc();

echo "<p><strong>Username field collation:</strong> " . $collation_info['Collation'] . "</p>";
echo "<p><strong>Type:</strong> " . $collation_info['Type'] . "</p>";

if (strpos($collation_info['Collation'], '_ci') !== false) {
    echo "<p class='success'>✓ Case-insensitive collation (should match both)</p>";
} else {
    echo "<p class='error'>❌ Case-sensitive collation (must match exact case)</p>";
}
echo "</div>";

$conn->close();
?>
