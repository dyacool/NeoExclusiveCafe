<?php
// Test script to verify password hashing
require_once __DIR__ . "/backend/pages/admin-includes/database.php";

echo "<h2>Password Hash Test</h2>";

// Test password
$test_password = "TestPassword123";

// Generate hash with bcrypt cost 10 (same as your signup)
$hash = password_hash($test_password, PASSWORD_BCRYPT, ['cost' => 10]);

echo "<p><strong>Test Password:</strong> " . htmlspecialchars($test_password) . "</p>";
echo "<p><strong>Generated Hash:</strong> " . htmlspecialchars($hash) . "</p>";
echo "<p><strong>Hash Length:</strong> " . strlen($hash) . " characters</p>";

// Verify the hash
$verify = password_verify($test_password, $hash);
echo "<p><strong>Verification Result:</strong> " . ($verify ? "✓ SUCCESS" : "✗ FAILED") . "</p>";

// Test with database
echo "<hr><h3>Database Test</h3>";

// Get a user from database (user ID 16 - Allysa who just reset password)
$sql = "SELECT id, username, email, password FROM users WHERE id = 16";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo "<p><strong>User:</strong> " . htmlspecialchars($row['username']) . " (ID: " . $row['id'] . ")</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($row['email']) . "</p>";
    echo "<p><strong>Stored Hash:</strong> " . htmlspecialchars(substr($row['password'], 0, 60)) . "...</p>";
    echo "<p><strong>Hash Length in DB:</strong> " . strlen($row['password']) . " characters</p>";
    
    // Test if we can verify with a known password (you'll need to know what password was set)
    echo "<p><em>Note: To test verification, you need to know the actual password for this user.</em></p>";
} else {
    echo "<p>Could not retrieve user from database.</p>";
}

echo "<hr><h3>Character Set Test</h3>";
$charset_result = $conn->query("SELECT @@character_set_database, @@collation_database");
if ($charset_result && $charset_row = $charset_result->fetch_assoc()) {
    echo "<p><strong>Database Character Set:</strong> " . $charset_row['@@character_set_database'] . "</p>";
    echo "<p><strong>Database Collation:</strong> " . $charset_row['@@collation_database'] . "</p>";
}

$conn_charset = $conn->character_set_name();
echo "<p><strong>Connection Character Set:</strong> " . $conn_charset . "</p>";

echo "<hr><h3>Password Field Info</h3>";
$field_info = $conn->query("SHOW FULL COLUMNS FROM users WHERE Field = 'password'");
if ($field_info && $field_row = $field_info->fetch_assoc()) {
    echo "<pre>" . print_r($field_row, true) . "</pre>";
}
?>
