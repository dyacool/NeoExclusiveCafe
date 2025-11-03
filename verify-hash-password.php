<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Hash Password Verification</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    input { padding: 8px; margin: 5px; width: 300px; }
    button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
</style>";

require_once __DIR__ . "/backend/pages/admin-includes/database.php";

// Get the current hash from database
$user_id = 5; // Aine
$sql = "SELECT username, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo "<div class='section'>";
echo "<h3>Current User Info</h3>";
echo "<p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>";
echo "<p><strong>Current Hash:</strong> <code>" . htmlspecialchars($user['password']) . "</code></p>";
echo "</div>";

// Test common passwords
$test_passwords = [
    "TestPassword123",
    "testpassword123",
    "TestPassword",
    "Allysa123",
    "allysa123",
    "password123",
    "Password123",
    "test123",
    "Test123",
];

echo "<div class='section'>";
echo "<h3>Testing Common Passwords</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Password</th><th>Result</th></tr>";

foreach ($test_passwords as $test_pwd) {
    $result = password_verify($test_pwd, $user['password']);
    $class = $result ? 'success' : 'error';
    $symbol = $result ? '✓' : '✗';
    echo "<tr><td>" . htmlspecialchars($test_pwd) . "</td><td class='$class'>$symbol " . ($result ? "MATCH!" : "No match") . "</td></tr>";
    
    if ($result) {
        error_log("FOUND MATCHING PASSWORD: $test_pwd");
    }
}

echo "</table>";
echo "</div>";

// Manual test form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_password'])) {
    $test_pwd = $_POST['test_password'];
    echo "<div class='section'>";
    echo "<h3>Manual Test Result</h3>";
    echo "<p><strong>Testing password:</strong> " . htmlspecialchars($test_pwd) . "</p>";
    
    $result = password_verify($test_pwd, $user['password']);
    echo "<p class='" . ($result ? "success" : "error") . "' style='font-size: 18px;'>";
    echo $result ? "✓✓✓ PASSWORD MATCHES! ✓✓✓" : "✗ Password does not match";
    echo "</p>";
    
    if ($result) {
        error_log("MANUAL TEST - FOUND MATCHING PASSWORD: $test_pwd");
    }
    echo "</div>";
}

echo "<div class='section'>";
echo "<h3>Manual Password Test</h3>";
echo "<form method='post'>";
echo "<input type='text' name='test_password' placeholder='Enter password to test' required>";
echo "<button type='submit'>Test Password</button>";
echo "</form>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>Set New Password</h3>";
echo "<p>If none of the passwords match, use the form below to set a new password:</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_pwd = $_POST['new_password'];
    $new_hash = password_hash($new_pwd, PASSWORD_BCRYPT, ['cost' => 10]);
    
    error_log("Setting new password: $new_pwd");
    error_log("New hash: $new_hash");
    
    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_hash, $user_id);
    
    if ($update_stmt->execute()) {
        echo "<p class='success'>✓ Password updated successfully!</p>";
        echo "<p>New password: <strong>" . htmlspecialchars($new_pwd) . "</strong></p>";
        echo "<p>You can now login with this password.</p>";
        
        // Verify it works
        $verify_test = password_verify($new_pwd, $new_hash);
        echo "<p class='" . ($verify_test ? "success" : "error") . "'>";
        echo "Verification test: " . ($verify_test ? "✓ PASS" : "✗ FAIL");
        echo "</p>";
        
        error_log("Password updated successfully for user ID: $user_id");
    } else {
        echo "<p class='error'>✗ Failed to update password: " . $update_stmt->error . "</p>";
        error_log("Failed to update password: " . $update_stmt->error);
    }
}

echo "<form method='post'>";
echo "<input type='text' name='new_password' placeholder='Enter new password' required>";
echo "<button type='submit' style='background: #28a745;'>Set New Password</button>";
echo "</form>";
echo "</div>";

$conn->close();
?>
