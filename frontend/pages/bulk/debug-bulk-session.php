<?php
/**
 * Debug Script for Bulk Form Session Issues
 * Access at: http://neocafe.cafe:8080/frontend/pages/bulk/debug-bulk-session.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Bulk Form Session Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #4CAF50; }
    .error { border-left-color: #f44336; }
    .warning { border-left-color: #ff9800; }
    .success { border-left-color: #4CAF50; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    h2 { margin-top: 0; color: #333; }
    .status { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
    .status.ok { background: #4CAF50; color: white; }
    .status.fail { background: #f44336; color: white; }
</style>";

// Test 1: Check if SessionManager file exists
echo "<div class='section'>";
echo "<h2>1. SessionManager File Check</h2>";
$session_manager_path = __DIR__ . '/../../../includes/session-manager.php';
if (file_exists($session_manager_path)) {
    echo "<span class='status ok'>✓ FOUND</span> SessionManager file exists at: <code>$session_manager_path</code>";
} else {
    echo "<span class='status fail'>✗ NOT FOUND</span> SessionManager file missing at: <code>$session_manager_path</code>";
}
echo "</div>";

// Test 2: Try to include SessionManager
echo "<div class='section'>";
echo "<h2>2. SessionManager Include Test</h2>";
try {
    require_once __DIR__ . '/../../../includes/session-manager.php';
    echo "<span class='status ok'>✓ SUCCESS</span> SessionManager loaded successfully<br>";
    echo "SessionManager class exists: " . (class_exists('SessionManager') ? '<span class="status ok">YES</span>' : '<span class="status fail">NO</span>');
} catch (Exception $e) {
    echo "<span class='status fail'>✗ ERROR</span> Failed to load SessionManager: " . $e->getMessage();
}
echo "</div>";

// Test 3: Check session status
echo "<div class='section'>";
echo "<h2>3. Session Status</h2>";
echo "Session Status: <code>" . session_status() . "</code><br>";
echo "Session Status Meaning: ";
switch (session_status()) {
    case PHP_SESSION_DISABLED:
        echo "<span class='status fail'>DISABLED</span>";
        break;
    case PHP_SESSION_NONE:
        echo "<span class='status warning'>NOT STARTED</span>";
        break;
    case PHP_SESSION_ACTIVE:
        echo "<span class='status ok'>ACTIVE</span>";
        break;
}
echo "</div>";

// Test 4: Check if user is logged in
echo "<div class='section'>";
echo "<h2>4. User Authentication Check</h2>";
if (class_exists('SessionManager')) {
    try {
        $isLoggedIn = SessionManager::isUserLoggedIn();
        echo "SessionManager::isUserLoggedIn(): ";
        if ($isLoggedIn) {
            echo "<span class='status ok'>✓ TRUE</span> - User is logged in<br>";
            
            // Get user data
            $userData = SessionManager::getUserData();
            echo "<h3>User Data:</h3>";
            echo "<pre>" . print_r($userData, true) . "</pre>";
            
            $userId = SessionManager::getUserId();
            echo "User ID: <strong>$userId</strong><br>";
        } else {
            echo "<span class='status fail'>✗ FALSE</span> - User is NOT logged in<br>";
            echo "<div class='warning' style='margin-top: 10px; padding: 10px; background: #fff3cd; border-left-color: #ff9800;'>";
            echo "<strong>⚠ This is why you're being logged out!</strong><br>";
            echo "The SessionManager thinks you're not logged in.";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<span class='status fail'>✗ ERROR</span> " . $e->getMessage();
    }
} else {
    echo "<span class='status fail'>✗ ERROR</span> SessionManager class not available";
}
echo "</div>";

// Test 5: Check raw session data
echo "<div class='section'>";
echo "<h2>5. Raw Session Data</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<h3>All Session Variables:</h3>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    echo "<h3>Key Session Checks:</h3>";
    echo "isset(\$_SESSION['user_id']): " . (isset($_SESSION['user_id']) ? '<span class="status ok">YES</span>' : '<span class="status fail">NO</span>') . "<br>";
    echo "isset(\$_SESSION['user_role']): " . (isset($_SESSION['user_role']) ? '<span class="status ok">YES</span>' : '<span class="status fail">NO</span>') . "<br>";
    echo "isset(\$_SESSION['username']): " . (isset($_SESSION['username']) ? '<span class="status ok">YES</span>' : '<span class="status fail">NO</span>') . "<br>";
    
    if (isset($_SESSION['user_id'])) {
        echo "<br>User ID from \$_SESSION: <strong>" . $_SESSION['user_id'] . "</strong><br>";
    }
    if (isset($_SESSION['user_role'])) {
        echo "User Role from \$_SESSION: <strong>" . $_SESSION['user_role'] . "</strong><br>";
    }
} else {
    echo "<span class='status warning'>Session not active - cannot read session data</span>";
}
echo "</div>";

// Test 6: Check session configuration
echo "<div class='section'>";
echo "<h2>6. Session Configuration</h2>";
echo "Session Name: <code>" . session_name() . "</code><br>";
echo "Session ID: <code>" . (session_status() === PHP_SESSION_ACTIVE ? session_id() : 'N/A') . "</code><br>";
echo "Session Save Path: <code>" . session_save_path() . "</code><br>";
echo "Session Cookie Params:<br>";
echo "<pre>" . print_r(session_get_cookie_params(), true) . "</pre>";
echo "</div>";

// Test 7: Check if SessionManager methods work
echo "<div class='section'>";
echo "<h2>7. SessionManager Methods Test</h2>";
if (class_exists('SessionManager')) {
    echo "<h3>Available Methods:</h3>";
    $methods = get_class_methods('SessionManager');
    echo "<pre>" . print_r($methods, true) . "</pre>";
    
    echo "<h3>Method Tests:</h3>";
    try {
        echo "getSessionData(): ";
        $sessionData = SessionManager::getSessionData();
        echo "<span class='status ok'>✓ Works</span><br>";
        echo "<pre>" . print_r($sessionData, true) . "</pre>";
    } catch (Exception $e) {
        echo "<span class='status fail'>✗ Error: " . $e->getMessage() . "</span><br>";
    }
}
echo "</div>";

// Test 8: Recommendations
echo "<div class='section'>";
echo "<h2>8. Diagnosis & Recommendations</h2>";

if (!class_exists('SessionManager')) {
    echo "<div class='error' style='background: #ffebee; padding: 10px; border-left-color: #f44336;'>";
    echo "<strong>❌ CRITICAL:</strong> SessionManager class not found. Check the include path.";
    echo "</div>";
} elseif (!SessionManager::isUserLoggedIn()) {
    echo "<div class='error' style='background: #ffebee; padding: 10px; border-left-color: #f44336;'>";
    echo "<strong>❌ PROBLEM FOUND:</strong> SessionManager says user is not logged in.<br><br>";
    echo "<strong>Possible causes:</strong><br>";
    echo "1. Session data structure mismatch (SessionManager expects different keys)<br>";
    echo "2. Session was destroyed or expired<br>";
    echo "3. Cookie domain/path mismatch<br>";
    echo "4. SessionManager initialization issue<br><br>";
    
    if (isset($_SESSION['user_id'])) {
        echo "<strong>⚠ IMPORTANT:</strong> Raw \$_SESSION['user_id'] EXISTS but SessionManager doesn't recognize it!<br>";
        echo "This suggests SessionManager is looking for session data in a different format or location.";
    } else {
        echo "<strong>⚠ IMPORTANT:</strong> No user_id in session at all. User needs to log in again.";
    }
    echo "</div>";
} else {
    echo "<div class='success' style='background: #e8f5e9; padding: 10px; border-left-color: #4CAF50;'>";
    echo "<strong>✓ ALL GOOD:</strong> User is properly logged in via SessionManager!";
    echo "</div>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>9. Next Steps</h2>";
echo "<ol>";
echo "<li>If SessionManager says you're NOT logged in but \$_SESSION['user_id'] exists, check SessionManager's isUserLoggedIn() method</li>";
echo "<li>If no session data exists at all, try logging in again</li>";
echo "<li>Check the SessionManager implementation to see what session keys it expects</li>";
echo "<li>Verify session cookie is being sent (check browser dev tools > Application > Cookies)</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><a href='bulk-form.php'>← Back to Bulk Form</a> | <a href='javascript:location.reload()'>🔄 Refresh Debug</a></p>";
?>
