<?php
/**
 * SessionManager Test & Debug Tool
 * 
 * This file helps you test and debug the SessionManager implementation.
 * Access: https://yourdomain.com/test-session-manager.php
 * 
 * SECURITY WARNING: Remove this file from production!
 */

// Include SessionManager
require_once __DIR__ . '/includes/session-manager.php';
require_once __DIR__ . '/backend/pages/admin-includes/database.php';

// Get current session state
$sessionData = [
    'status' => session_status(),
    'id' => session_id(),
    'all_data' => $_SESSION ?? []
];

// Test SessionManager methods
$tests = [];

// Test 1: User Login Check
$tests['isUserLoggedIn'] = [
    'result' => SessionManager::isUserLoggedIn(),
    'expected' => 'boolean',
    'description' => 'Check if a user is logged in'
];

// Test 2: Admin Login Check
$tests['isAdminLoggedIn'] = [
    'result' => SessionManager::isAdminLoggedIn(),
    'expected' => 'boolean',
    'description' => 'Check if an admin is logged in'
];

// Test 3: Preview Mode Check
$tests['isPreviewMode'] = [
    'result' => SessionManager::isPreviewMode(),
    'expected' => 'boolean',
    'description' => 'Check if in preview mode (not logged in)'
];

// Test 4: Get User ID
$tests['getUserId'] = [
    'result' => SessionManager::getUserId(),
    'expected' => 'int|null',
    'description' => 'Get current user ID'
];

// Test 5: Get User Data
$tests['getUserData'] = [
    'result' => SessionManager::getUserData(),
    'expected' => 'array|null',
    'description' => 'Get current user data'
];

// Test 6: Get Admin Data
$tests['getAdminData'] = [
    'result' => SessionManager::getAdminData(),
    'expected' => 'array|null',
    'description' => 'Get current admin data'
];

// Test 7: Get Role
$tests['getRole'] = [
    'result' => SessionManager::getRole(),
    'expected' => 'string (user|admin|guest)',
    'description' => 'Get current session role'
];

// Check for common migration issues
$issues = [];

// Issue 1: Old session variables still present
if (isset($_SESSION['user_id']) && !SessionManager::isUserLoggedIn()) {
    $issues[] = [
        'severity' => 'warning',
        'message' => 'user_id exists in session but SessionManager says not logged in',
        'details' => 'Check if user_role is set correctly'
    ];
}

if (isset($_SESSION['admin_id']) && !SessionManager::isAdminLoggedIn()) {
    $issues[] = [
        'severity' => 'warning',
        'message' => 'admin_id exists in session but SessionManager says not logged in',
        'details' => 'Check if is_admin and admin_role are set correctly'
    ];
}

// Issue 2: Missing required session variables
if (SessionManager::isUserLoggedIn()) {
    if (!isset($_SESSION['user_id'])) {
        $issues[] = [
            'severity' => 'error',
            'message' => 'SessionManager says user logged in but user_id missing',
            'details' => 'Session data may be corrupted'
        ];
    }
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
        $issues[] = [
            'severity' => 'error',
            'message' => 'user_role is missing or incorrect',
            'details' => 'Should be set to "user"'
        ];
    }
}

if (SessionManager::isAdminLoggedIn()) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        $issues[] = [
            'severity' => 'error',
            'message' => 'is_admin flag is missing or incorrect',
            'details' => 'Should be set to true'
        ];
    }
    if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
        $issues[] = [
            'severity' => 'error',
            'message' => 'admin_role is missing or incorrect',
            'details' => 'Should be set to "admin"'
        ];
    }
}

// Database verification
$dbTests = [];
if (SessionManager::isUserLoggedIn()) {
    $userId = SessionManager::getUserId();
    $stmt = $conn->prepare("SELECT id, username, firstname, lastname FROM users WHERE id = ? AND is_admin = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $dbTests['user_exists'] = [
        'result' => $result->num_rows > 0,
        'description' => 'User exists in database',
        'data' => $result->fetch_assoc()
    ];
    $stmt->close();
}

if (SessionManager::isAdminLoggedIn()) {
    $adminData = SessionManager::getAdminData();
    $adminId = $adminData['id'];
    $stmt = $conn->prepare("SELECT id, username, firstname, lastname, is_admin FROM users WHERE id = ? AND is_admin = 1");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $dbTests['admin_exists'] = [
        'result' => $result->num_rows > 0,
        'description' => 'Admin exists in database',
        'data' => $result->fetch_assoc()
    ];
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SessionManager Test & Debug</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
        }
        
        .warning-banner {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .test-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .test-card h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .test-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .test-result {
            background: white;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            word-break: break-all;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .issue-card {
            background: #fff;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .issue-card.warning {
            border-color: #ffc107;
        }
        
        .issue-card h4 {
            color: #dc3545;
            margin-bottom: 8px;
        }
        
        .issue-card.warning h4 {
            color: #856404;
        }
        
        .session-data {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-active {
            background: #28a745;
        }
        
        .status-inactive {
            background: #dc3545;
        }
        
        .status-unknown {
            background: #ffc107;
        }
        
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 SessionManager Test & Debug Tool</h1>
            <p>Comprehensive testing and debugging for the SessionManager implementation</p>
        </div>
        
        <div class="warning-banner">
            ⚠️ <strong>Security Warning:</strong> This file should be removed from production environments!
        </div>
        
        <!-- Current Session Status -->
        <div class="section">
            <h2>📊 Current Session Status</h2>
            <div class="test-grid">
                <div class="test-card">
                    <h3>
                        <span class="status-indicator <?php echo SessionManager::isUserLoggedIn() ? 'status-active' : 'status-inactive'; ?>"></span>
                        User Login
                    </h3>
                    <p><?php echo SessionManager::isUserLoggedIn() ? 'Logged In' : 'Not Logged In'; ?></p>
                    <?php if (SessionManager::isUserLoggedIn()): ?>
                        <div class="test-result">
                            ID: <?php echo SessionManager::getUserId(); ?><br>
                            <?php $userData = SessionManager::getUserData(); ?>
                            Name: <?php echo htmlspecialchars($userData['firstname'] . ' ' . $userData['lastname']); ?><br>
                            Username: <?php echo htmlspecialchars($userData['username']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="test-card">
                    <h3>
                        <span class="status-indicator <?php echo SessionManager::isAdminLoggedIn() ? 'status-active' : 'status-inactive'; ?>"></span>
                        Admin Login
                    </h3>
                    <p><?php echo SessionManager::isAdminLoggedIn() ? 'Logged In' : 'Not Logged In'; ?></p>
                    <?php if (SessionManager::isAdminLoggedIn()): ?>
                        <div class="test-result">
                            <?php $adminData = SessionManager::getAdminData(); ?>
                            ID: <?php echo $adminData['id']; ?><br>
                            Name: <?php echo htmlspecialchars($adminData['firstname'] . ' ' . $adminData['lastname']); ?><br>
                            Username: <?php echo htmlspecialchars($adminData['username']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="test-card">
                    <h3>
                        <span class="status-indicator <?php echo SessionManager::isPreviewMode() ? 'status-active' : 'status-inactive'; ?>"></span>
                        Preview Mode
                    </h3>
                    <p><?php echo SessionManager::isPreviewMode() ? 'Active (Guest)' : 'Inactive'; ?></p>
                    <div class="test-result">
                        Role: <?php echo SessionManager::getRole(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SessionManager API Tests -->
        <div class="section">
            <h2>🧪 SessionManager API Tests</h2>
            <div class="test-grid">
                <?php foreach ($tests as $method => $test): ?>
                <div class="test-card">
                    <h3><?php echo $method; ?>()</h3>
                    <p><?php echo $test['description']; ?></p>
                    <p><small>Expected: <?php echo $test['expected']; ?></small></p>
                    <div class="test-result">
                        <?php 
                        if (is_array($test['result'])) {
                            echo '<pre>' . json_encode($test['result'], JSON_PRETTY_PRINT) . '</pre>';
                        } elseif (is_bool($test['result'])) {
                            echo $test['result'] ? 'true' : 'false';
                        } elseif (is_null($test['result'])) {
                            echo 'null';
                        } else {
                            echo htmlspecialchars($test['result']);
                        }
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Database Verification -->
        <?php if (!empty($dbTests)): ?>
        <div class="section">
            <h2>💾 Database Verification</h2>
            <?php foreach ($dbTests as $key => $test): ?>
            <div class="test-card">
                <h3>
                    <?php echo $test['description']; ?>
                    <span class="badge <?php echo $test['result'] ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo $test['result'] ? 'PASS' : 'FAIL'; ?>
                    </span>
                </h3>
                <?php if ($test['data']): ?>
                <div class="test-result">
                    <pre><?php echo json_encode($test['data'], JSON_PRETTY_PRINT); ?></pre>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Issues & Warnings -->
        <?php if (!empty($issues)): ?>
        <div class="section">
            <h2>⚠️ Issues & Warnings</h2>
            <?php foreach ($issues as $issue): ?>
            <div class="issue-card <?php echo $issue['severity']; ?>">
                <h4><?php echo strtoupper($issue['severity']); ?>: <?php echo $issue['message']; ?></h4>
                <p><?php echo $issue['details']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="section">
            <h2>✅ No Issues Detected</h2>
            <p>SessionManager is working correctly with no detected issues.</p>
        </div>
        <?php endif; ?>
        
        <!-- Raw Session Data -->
        <div class="section">
            <h2>🔍 Raw Session Data</h2>
            <div class="session-data">
                <strong>Session Status:</strong> <?php 
                    $statuses = [
                        PHP_SESSION_DISABLED => 'DISABLED',
                        PHP_SESSION_NONE => 'NONE',
                        PHP_SESSION_ACTIVE => 'ACTIVE'
                    ];
                    echo $statuses[$sessionData['status']];
                ?><br>
                <strong>Session ID:</strong> <?php echo $sessionData['id']; ?><br><br>
                <strong>Session Variables:</strong>
                <pre><?php echo json_encode($sessionData['all_data'], JSON_PRETTY_PRINT); ?></pre>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="section">
            <h2>🎯 Quick Actions</h2>
            <div class="action-buttons">
                <a href="backend/login/admin/admin-login.php" class="btn btn-primary">Admin Login</a>
                <a href="frontend/login/user/login-signup.php" class="btn btn-primary">User Login</a>
                <a href="backend/login/admin/logout.php" class="btn btn-danger">Logout (Admin)</a>
                <a href="frontend/login/user/logout.php" class="btn btn-danger">Logout (User)</a>
                <a href="?" class="btn btn-secondary">Refresh Test</a>
            </div>
        </div>
        
        <!-- Migration Checklist -->
        <div class="section">
            <h2>📋 Migration Checklist</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 8px 0;">
                    <span class="badge <?php echo SessionManager::isUserLoggedIn() || SessionManager::isAdminLoggedIn() ? 'badge-success' : 'badge-info'; ?>">
                        <?php echo SessionManager::isUserLoggedIn() || SessionManager::isAdminLoggedIn() ? '✓' : '○'; ?>
                    </span>
                    SessionManager correctly identifies login state
                </li>
                <li style="padding: 8px 0;">
                    <span class="badge <?php echo empty($issues) ? 'badge-success' : 'badge-warning'; ?>">
                        <?php echo empty($issues) ? '✓' : '!'; ?>
                    </span>
                    No session data inconsistencies
                </li>
                <li style="padding: 8px 0;">
                    <span class="badge <?php echo !empty($dbTests) && array_reduce($dbTests, fn($carry, $test) => $carry && $test['result'], true) ? 'badge-success' : 'badge-info'; ?>">
                        <?php echo !empty($dbTests) && array_reduce($dbTests, fn($carry, $test) => $carry && $test['result'], true) ? '✓' : '○'; ?>
                    </span>
                    Database records match session data
                </li>
                <li style="padding: 8px 0;">
                    <span class="badge badge-info">○</span>
                    All old session checks replaced with SessionManager
                </li>
                <li style="padding: 8px 0;">
                    <span class="badge badge-info">○</span>
                    Test login/logout flows for both user and admin
                </li>
            </ul>
        </div>
    </div>
</body>
</html>
