<?php
/**
 * Authentication Refactor Test Script
 * 
 * Tests the shared password functions across all authentication flows
 */

require_once __DIR__ . "/backend/pages/admin-includes/database.php";
require_once __DIR__ . "/backend/pages/admin-includes/auth-helpers.php";

echo "<!DOCTYPE html><html><head><title>Auth Refactor Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .test-section h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    .pass { color: #4CAF50; font-weight: bold; }
    .fail { color: #f44336; font-weight: bold; }
    .info { color: #2196F3; }
    pre { background: #f9f9f9; padding: 10px; border-left: 3px solid #2196F3; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #f0f0f0; font-weight: bold; }
</style></head><body>";

echo "<h1>🔐 Authentication Refactor Test Suite</h1>";

// Test 1: Shared hashPassword() function
echo "<div class='test-section'>";
echo "<h2>Test 1: hashPassword() Function</h2>";

$test_password = "TestPassword123";
$hash_result = hashPassword($test_password);

echo "<table>";
echo "<tr><th>Property</th><th>Value</th><th>Status</th></tr>";
echo "<tr><td>Success</td><td>" . ($hash_result['success'] ? 'true' : 'false') . "</td>";
echo "<td class='" . ($hash_result['success'] ? 'pass' : 'fail') . "'>" . ($hash_result['success'] ? '✓ PASS' : '✗ FAIL') . "</td></tr>";

if ($hash_result['success']) {
    echo "<tr><td>Hash Generated</td><td>" . substr($hash_result['hash'], 0, 30) . "...</td>";
    echo "<td class='pass'>✓ PASS</td></tr>";
    
    echo "<tr><td>Hash Length</td><td>" . strlen($hash_result['hash']) . "</td>";
    echo "<td class='" . (strlen($hash_result['hash']) == 60 ? 'pass' : 'fail') . "'>" . (strlen($hash_result['hash']) == 60 ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
    
    $hash_info = password_get_info($hash_result['hash']);
    echo "<tr><td>Algorithm</td><td>" . $hash_info['algoName'] . "</td>";
    echo "<td class='" . ($hash_info['algoName'] == 'bcrypt' ? 'pass' : 'fail') . "'>" . ($hash_info['algoName'] == 'bcrypt' ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
    
    $prefix_check = substr($hash_result['hash'], 0, 4) === '$2y$';
    echo "<tr><td>Bcrypt Prefix</td><td>" . substr($hash_result['hash'], 0, 4) . "</td>";
    echo "<td class='" . ($prefix_check ? 'pass' : 'fail') . "'>" . ($prefix_check ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
} else {
    echo "<tr><td colspan='3' class='fail'>Error: " . $hash_result['error'] . "</td></tr>";
}
echo "</table>";
echo "</div>";

// Test 2: Shared verifyPassword() function
echo "<div class='test-section'>";
echo "<h2>Test 2: verifyPassword() Function</h2>";

if ($hash_result['success']) {
    $verify_correct = verifyPassword($test_password, $hash_result['hash']);
    $verify_wrong = verifyPassword("WrongPassword", $hash_result['hash']);
    
    echo "<table>";
    echo "<tr><th>Test Case</th><th>Result</th><th>Status</th></tr>";
    echo "<tr><td>Correct Password</td><td>" . ($verify_correct ? 'true' : 'false') . "</td>";
    echo "<td class='" . ($verify_correct ? 'pass' : 'fail') . "'>" . ($verify_correct ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
    
    echo "<tr><td>Wrong Password</td><td>" . ($verify_wrong ? 'true' : 'false') . "</td>";
    echo "<td class='" . (!$verify_wrong ? 'pass' : 'fail') . "'>" . (!$verify_wrong ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
    echo "</table>";
} else {
    echo "<p class='fail'>Cannot test verification - hash generation failed</p>";
}
echo "</div>";

// Test 3: Whitespace trimming
echo "<div class='test-section'>";
echo "<h2>Test 3: Whitespace Trimming</h2>";

$password_with_spaces = "  TestPassword123  ";
$hash_trimmed = hashPassword($password_with_spaces);

if ($hash_trimmed['success']) {
    $verify_trimmed = verifyPassword("TestPassword123", $hash_trimmed['hash']);
    $verify_with_spaces = verifyPassword($password_with_spaces, $hash_trimmed['hash']);
    
    echo "<table>";
    echo "<tr><th>Test Case</th><th>Result</th><th>Status</th></tr>";
    echo "<tr><td>Hash password with spaces</td><td>Success</td><td class='pass'>✓ PASS</td></tr>";
    echo "<tr><td>Verify without spaces</td><td>" . ($verify_trimmed ? 'true' : 'false') . "</td>";
    echo "<td class='" . ($verify_trimmed ? 'pass' : 'fail') . "'>" . ($verify_trimmed ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
    echo "<tr><td>Verify with spaces</td><td>" . ($verify_with_spaces ? 'true' : 'false') . "</td>";
    echo "<td class='" . ($verify_with_spaces ? 'pass' : 'fail') . "'>" . ($verify_with_spaces ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
    echo "</table>";
} else {
    echo "<p class='fail'>Whitespace trimming test failed</p>";
}
echo "</div>";

// Test 4: Database integration test
echo "<div class='test-section'>";
echo "<h2>Test 4: Database Integration</h2>";

$test_email = "test_refactor_" . time() . "@example.com";
$test_username = "testuser_" . time();
$test_pass = "SecurePass123";

// Create test user with hashed password
$hash_for_db = hashPassword($test_pass);

if ($hash_for_db['success']) {
    $sql = "INSERT INTO users (firstname, lastname, username, email, password, is_verified) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $firstname = "Test";
    $lastname = "User";
    $stmt->bind_param("sssss", $firstname, $lastname, $test_username, $test_email, $hash_for_db['hash']);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        echo "<p class='pass'>✓ Test user created successfully (ID: $user_id)</p>";
        
        // Retrieve and verify
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        echo "<table>";
        echo "<tr><th>Property</th><th>Value</th><th>Status</th></tr>";
        echo "<tr><td>User Retrieved</td><td>Yes</td><td class='pass'>✓ PASS</td></tr>";
        echo "<tr><td>Stored Hash Length</td><td>" . strlen($user['password']) . "</td>";
        echo "<td class='" . (strlen($user['password']) == 60 ? 'pass' : 'fail') . "'>" . (strlen($user['password']) == 60 ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
        
        $verify_from_db = verifyPassword($test_pass, $user['password']);
        echo "<tr><td>Password Verification</td><td>" . ($verify_from_db ? 'true' : 'false') . "</td>";
        echo "<td class='" . ($verify_from_db ? 'pass' : 'fail') . "'>" . ($verify_from_db ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
        
        $hash_matches = ($hash_for_db['hash'] === $user['password']);
        echo "<tr><td>Hash Integrity</td><td>" . ($hash_matches ? 'Match' : 'Mismatch') . "</td>";
        echo "<td class='" . ($hash_matches ? 'pass' : 'fail') . "'>" . ($hash_matches ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
        echo "</table>";
        
        // Clean up test user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        echo "<p class='info'>ℹ Test user cleaned up</p>";
    } else {
        echo "<p class='fail'>✗ Failed to create test user: " . $stmt->error . "</p>";
    }
} else {
    echo "<p class='fail'>✗ Failed to hash password for database test</p>";
}
echo "</div>";

// Test 5: Consistency check
echo "<div class='test-section'>";
echo "<h2>Test 5: Hash Consistency</h2>";

$password = "ConsistencyTest123";
$hash1 = hashPassword($password);
$hash2 = hashPassword($password);

echo "<table>";
echo "<tr><th>Property</th><th>Result</th><th>Status</th></tr>";
echo "<tr><td>Both hashes generated</td><td>" . ($hash1['success'] && $hash2['success'] ? 'Yes' : 'No') . "</td>";
echo "<td class='" . ($hash1['success'] && $hash2['success'] ? 'pass' : 'fail') . "'>" . ($hash1['success'] && $hash2['success'] ? '✓ PASS' : '✗ FAIL') . "</td></tr>";

if ($hash1['success'] && $hash2['success']) {
    $hashes_different = ($hash1['hash'] !== $hash2['hash']);
    echo "<tr><td>Hashes are different (salt)</td><td>" . ($hashes_different ? 'Yes' : 'No') . "</td>";
    echo "<td class='" . ($hashes_different ? 'pass' : 'fail') . "'>" . ($hashes_different ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
    
    $both_verify = verifyPassword($password, $hash1['hash']) && verifyPassword($password, $hash2['hash']);
    echo "<tr><td>Both verify correctly</td><td>" . ($both_verify ? 'Yes' : 'No') . "</td>";
    echo "<td class='" . ($both_verify ? 'pass' : 'fail') . "'>" . ($both_verify ? '✓ PASS' : '✗ FAIL') . "</td></tr>";
}
echo "</table>";
echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>📊 Test Summary</h2>";
echo "<p><strong>All core functionality tests completed.</strong></p>";
echo "<p class='info'>Next steps: Test manually through the web interface:</p>";
echo "<ol>";
echo "<li>Create a new account via signup form</li>";
echo "<li>Login with the new account credentials</li>";
echo "<li>Request a password reset</li>";
echo "<li>Reset password using the email link</li>";
echo "<li>Login with the new password</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
