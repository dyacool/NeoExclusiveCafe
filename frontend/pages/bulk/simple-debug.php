<?php
/**
 * Simple Debug - No SessionManager loading
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session manually
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Session Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .error { border-left-color: #f44336; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Simple Session Debug</h1>
    
    <div class="box">
        <h2>Session Status</h2>
        <p>Session Status Code: <?php echo session_status(); ?></p>
        <p>Session Active: <?php echo (session_status() === PHP_SESSION_ACTIVE) ? 'YES' : 'NO'; ?></p>
        <p>Session ID: <?php echo session_id(); ?></p>
    </div>
    
    <div class="box">
        <h2>Session Data</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="box">
        <h2>Key Checks</h2>
        <p>isset($_SESSION['user_id']): <?php echo isset($_SESSION['user_id']) ? 'YES - Value: ' . $_SESSION['user_id'] : 'NO'; ?></p>
        <p>isset($_SESSION['user_role']): <?php echo isset($_SESSION['user_role']) ? 'YES - Value: ' . $_SESSION['user_role'] : 'NO'; ?></p>
        <p>isset($_SESSION['username']): <?php echo isset($_SESSION['username']) ? 'YES - Value: ' . $_SESSION['username'] : 'NO'; ?></p>
        <p>isset($_SESSION['is_admin']): <?php echo isset($_SESSION['is_admin']) ? 'YES - Value: ' . var_export($_SESSION['is_admin'], true) : 'NO'; ?></p>
    </div>
    
    <div class="box <?php echo (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') ? 'error' : ''; ?>">
        <h2>Login Status</h2>
        <?php
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user') {
            echo "<p style='color: green; font-weight: bold;'>✓ USER IS LOGGED IN</p>";
            echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
            echo "<p>User Role: " . $_SESSION['user_role'] . "</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>✗ USER IS NOT LOGGED IN</p>";
            echo "<p>Reason: ";
            if (!isset($_SESSION['user_id'])) {
                echo "No user_id in session";
            } elseif (!isset($_SESSION['user_role'])) {
                echo "No user_role in session";
            } elseif ($_SESSION['user_role'] !== 'user') {
                echo "user_role is '" . $_SESSION['user_role'] . "' (expected 'user')";
            }
            echo "</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>SessionManager Path Check</h2>
        <?php
        $sm_path = __DIR__ . '/../../../includes/session-manager.php';
        echo "<p>Expected path: <code>$sm_path</code></p>";
        echo "<p>File exists: " . (file_exists($sm_path) ? 'YES' : 'NO') . "</p>";
        ?>
    </div>
    
    <hr>
    <p><a href="bulk-form.php">← Back to Bulk Form</a></p>
</body>
</html>
