<?php
/**
 * Payment Flow Debug Page
 * Tests the complete payment flow and diagnoses why successful payments fail
 */

require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? 'menu';
$test_user_id = SessionManager::getUserId() ?? 17;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Flow Debugger</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        h1 { font-size: 2em; margin-bottom: 10px; }
        .subtitle { opacity: 0.9; font-size: 1.1em; }
        .content { padding: 30px; }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .menu-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        .menu-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        .menu-card p {
            color: #6c757d;
            line-height: 1.6;
        }
        .test-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .test-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .result-box {
            background: white;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1em;
            margin: 5px;
        }
        .btn:hover { background: #5568d3; transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover { background: #f8f9fa; }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover { text-decoration: underline; }
        .flow-diagram {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .flow-step {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .flow-step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .flow-step-content h4 { color: #667eea; margin-bottom: 5px; }
        .flow-step-content p { color: #6c757d; margin: 0; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Payment Flow Debugger</h1>
            <p class="subtitle">Diagnose and test the complete payment processing flow</p>
        </div>
        <div class="content">
<?php

if ($action === 'menu') {
    // Main menu
    ?>
    <div class="menu-grid">
        <a href="?action=check_database" class="menu-card">
            <h3>🗄️ Check Database</h3>
            <p>Verify pending_payments table exists and check for stored payment records</p>
        </a>
        
        <a href="?action=check_session" class="menu-card">
            <h3>🔐 Check Session</h3>
            <p>View current session data and pending payment information</p>
        </a>
        
        <a href="?action=simulate_payment" class="menu-card">
            <h3>💳 Simulate Payment</h3>
            <p>Create a mock payment record to test the return flow</p>
        </a>
        
        <a href="?action=test_return_success" class="menu-card">
            <h3>✅ Test Success Return</h3>
            <p>Simulate a successful payment return with proper data</p>
        </a>
        
        <a href="?action=test_return_failed" class="menu-card">
            <h3>❌ Test Failed Return</h3>
            <p>Simulate a failed payment return scenario</p>
        </a>
        
        <a href="?action=view_logs" class="menu-card">
            <h3>📋 View Recent Logs</h3>
            <p>Check recent payment error logs for debugging</p>
        </a>
        
        <a href="?action=flow_diagram" class="menu-card">
            <h3>📊 Payment Flow Diagram</h3>
            <p>Visualize the complete payment processing flow</p>
        </a>
        
        <a href="?action=cleanup" class="menu-card">
            <h3>🧹 Cleanup Test Data</h3>
            <p>Remove all test payment records from database</p>
        </a>
        
        <a href="?action=data_format" class="menu-card">
            <h3>📝 Expected Data Format</h3>
            <p>View the correct order_data structure required by payment-return.php</p>
        </a>
        
        <a href="?action=inspect_data" class="menu-card">
            <h3>🔬 Inspect Payment Data</h3>
            <p>View actual order_data from database to diagnose format issues</p>
        </a>
    </div>
    <?php
}

// Action: Check Database
elseif ($action === 'check_database') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Database Status Check</h2>
        <?php
        // Check if table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'pending_payments'");
        if ($check_table && $check_table->num_rows > 0) {
            echo '<div class="result-box success">✓ Table "pending_payments" exists</div>';
            
            // Show table structure
            echo '<h3 style="margin-top: 20px;">Table Structure:</h3>';
            $structure = $conn->query("DESCRIBE pending_payments");
            if ($structure) {
                echo '<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                while ($row = $structure->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['Field']}</td>
                        <td>{$row['Type']}</td>
                        <td>{$row['Null']}</td>
                        <td>{$row['Key']}</td>
                        <td>{$row['Default']}</td>
                    </tr>";
                }
                echo '</table>';
            }
            
            // Show recent records
            echo '<h3 style="margin-top: 20px;">Recent Payment Records:</h3>';
            $recent = $conn->query("SELECT * FROM pending_payments ORDER BY created_at DESC LIMIT 10");
            if ($recent && $recent->num_rows > 0) {
                echo '<table><tr><th>ID</th><th>User ID</th><th>Payment ID</th><th>Type</th><th>Order Type</th><th>Amount</th><th>Method</th><th>Created</th><th>Expires</th></tr>';
                while ($row = $recent->fetch_assoc()) {
                    $expired = strtotime($row['expires_at']) < time() ? ' style="background: #f8d7da;"' : '';
                    echo "<tr{$expired}>
                        <td>{$row['id']}</td>
                        <td>{$row['user_id']}</td>
                        <td>" . substr($row['payment_id'], 0, 20) . "...</td>
                        <td>{$row['payment_type']}</td>
                        <td>{$row['order_type']}</td>
                        <td>₱{$row['amount']}</td>
                        <td>{$row['payment_method']}</td>
                        <td>{$row['created_at']}</td>
                        <td>{$row['expires_at']}</td>
                    </tr>";
                }
                echo '</table>';
            } else {
                echo '<div class="result-box warning">⚠ No payment records found in database</div>';
            }
        } else {
            echo '<div class="result-box error">✗ Table "pending_payments" does NOT exist!</div>';
            echo '<p style="margin-top: 15px;">Run the setup script to create the table:</p>';
            echo '<div class="code-block">php backend/api/setup-pending-payments-table.php</div>';
        }
        ?>
    </div>
    <?php
}

// Action: Check Session
elseif ($action === 'check_session') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Session Data Check</h2>
        <?php
        echo '<div class="result-box info">';
        echo '<strong>Session ID:</strong> ' . session_id() . '<br>';
        echo '<strong>User ID:</strong> ' . ($test_user_id ?? 'Not set') . '<br>';
        echo '<strong>Session Status:</strong> ' . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive');
        echo '</div>';
        
        if (isset($_SESSION['pending_payment'])) {
            echo '<div class="result-box success">✓ Pending payment data found in session</div>';
            echo '<h3>Pending Payment Data:</h3>';
            echo '<div class="code-block">' . htmlspecialchars(json_encode($_SESSION['pending_payment'], JSON_PRETTY_PRINT)) . '</div>';
        } else {
            echo '<div class="result-box warning">⚠ No pending payment data in session</div>';
        }
        
        echo '<h3 style="margin-top: 20px;">All Session Data:</h3>';
        echo '<div class="code-block">' . htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT)) . '</div>';
        ?>
    </div>
    <?php
}

// Action: Simulate Payment
elseif ($action === 'simulate_payment') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Simulate Payment Creation</h2>
        <?php
        $source_id = 'src_test_' . time() . '_' . rand(1000, 9999);
        $order_id = rand(10000, 99999);
        $amount = 500.00;
        $payment_method = 'gcash';
        $order_type = 'regular';
        
        $order_data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '09123456789',
            'address' => '123 Test Street',
            'city' => 'Test City',
            'shipping_method' => 'delivery',
            'cart_items' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Test Product',
                    'quantity' => 2,
                    'price' => 250.00,
                    'subtotal' => 500.00
                ]
            ],
            'cart_total' => 500.00
        ];
        
        // Save to session
        $_SESSION['pending_payment'] = [
            'source_id' => $source_id,
            'order_id' => $order_id,
            'order_type' => $order_type,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'order_data' => $order_data
        ];
        
        echo '<div class="result-box success">✓ Payment data saved to session</div>';
        
        // Save to database
        try {
            $order_data_json = json_encode($order_data);
            $payment_type = 'source';
            
            $save_sql = "INSERT INTO pending_payments 
                         (user_id, payment_id, payment_type, order_type, amount, payment_method, order_data)
                         VALUES (?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE 
                         order_data = VALUES(order_data),
                         created_at = CURRENT_TIMESTAMP";
            
            $save_stmt = $conn->prepare($save_sql);
            if ($save_stmt) {
                $save_stmt->bind_param("isssdss", 
                    $test_user_id,
                    $source_id,
                    $payment_type,
                    $order_type,
                    $amount,
                    $payment_method,
                    $order_data_json
                );
                
                if ($save_stmt->execute()) {
                    echo '<div class="result-box success">✓ Payment data saved to database</div>';
                } else {
                    echo '<div class="result-box error">✗ Database save failed: ' . $save_stmt->error . '</div>';
                }
                $save_stmt->close();
            } else {
                echo '<div class="result-box error">✗ Database prepare failed: ' . $conn->error . '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="result-box error">✗ Exception: ' . $e->getMessage() . '</div>';
        }
        
        echo '<h3 style="margin-top: 20px;">Simulated Payment Data:</h3>';
        echo '<div class="code-block">' . htmlspecialchars(json_encode($_SESSION['pending_payment'], JSON_PRETTY_PRINT)) . '</div>';
        
        echo '<div style="margin-top: 20px;">';
        echo '<a href="?action=test_return_success&source_id=' . urlencode($source_id) . '" class="btn btn-success">Test Success Return with This Payment</a>';
        echo '<a href="?action=check_session" class="btn btn-secondary">View Session</a>';
        echo '<a href="?action=check_database" class="btn btn-secondary">View Database</a>';
        echo '</div>';
        ?>
    </div>
    <?php
}

// Action: Test Return Success
elseif ($action === 'test_return_success') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Test Successful Payment Return</h2>
        <?php
        $source_id = $_GET['source_id'] ?? null;
        
        if (!$source_id) {
            echo '<div class="result-box warning">⚠ No source_id provided. Creating a test payment first...</div>';
            echo '<p style="margin: 15px 0;">Click the button below to simulate a payment and then test the return:</p>';
            echo '<a href="?action=simulate_payment" class="btn">Simulate Payment First</a>';
        } else {
            echo '<div class="result-box info">';
            echo '<strong>Testing payment return with:</strong><br>';
            echo 'Source ID: ' . htmlspecialchars($source_id) . '<br>';
            echo 'Status: success<br>';
            echo 'Type: regular';
            echo '</div>';
            
            // Check if payment exists in session
            $session_exists = isset($_SESSION['pending_payment']);
            echo '<h3>Pre-Return Checks:</h3>';
            echo '<div class="result-box ' . ($session_exists ? 'success' : 'warning') . '">';
            echo $session_exists ? '✓ Payment data exists in session' : '⚠ No payment data in session (will try database recovery)';
            echo '</div>';
            
            // Check if payment exists in database
            $db_check = $conn->prepare("SELECT * FROM pending_payments WHERE payment_id = ? AND user_id = ?");
            $db_check->bind_param("si", $source_id, $test_user_id);
            $db_check->execute();
            $db_result = $db_check->get_result();
            $db_exists = $db_result->num_rows > 0;
            
            echo '<div class="result-box ' . ($db_exists ? 'success' : 'error') . '">';
            echo $db_exists ? '✓ Payment data exists in database' : '✗ Payment data NOT found in database';
            echo '</div>';
            
            if ($db_exists) {
                $payment_data = $db_result->fetch_assoc();
                echo '<h3>Database Payment Record:</h3>';
                echo '<div class="code-block">' . htmlspecialchars(json_encode($payment_data, JSON_PRETTY_PRINT)) . '</div>';
            }
            
            echo '<h3 style="margin-top: 20px;">Test Actions:</h3>';
            echo '<div style="margin-top: 15px;">';
            echo '<a href="payment-return.php?type=regular&status=success&source_id=' . urlencode($source_id) . '" class="btn btn-success" target="_blank">🚀 Trigger Payment Return (Opens in new tab)</a>';
            echo '<a href="?action=check_session" class="btn btn-secondary">Check Session After</a>';
            echo '<a href="?action=view_logs" class="btn btn-secondary">View Logs</a>';
            echo '</div>';
            
            echo '<div class="result-box info" style="margin-top: 20px;">';
            echo '<strong>What should happen:</strong><br>';
            echo '1. payment-return.php receives the source_id<br>';
            echo '2. It retrieves payment data from session OR database<br>';
            echo '3. It verifies the payment with PayMongo<br>';
            echo '4. It creates the order in the database<br>';
            echo '5. It redirects to payment-success.php<br><br>';
            echo '<strong>If it fails:</strong> Check the logs to see where the process breaks';
            echo '</div>';
        }
        ?>
    </div>
    <?php
}

// Action: Test Return Failed
elseif ($action === 'test_return_failed') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Test Failed Payment Return</h2>
        <div class="result-box warning">
            ⚠ This simulates what happens when a payment return fails even though PayMongo says it succeeded
        </div>
        
        <h3>Common Failure Scenarios:</h3>
        <div class="flow-diagram">
            <div class="flow-step">
                <div class="flow-step-number">1</div>
                <div class="flow-step-content">
                    <h4>No Session Data</h4>
                    <p>Session lost during redirect - should recover from database</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="flow-step-number">2</div>
                <div class="flow-step-content">
                    <h4>No Database Record</h4>
                    <p>Payment not saved to database - cannot recover</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="flow-step-number">3</div>
                <div class="flow-step-content">
                    <h4>Missing source_id</h4>
                    <p>URL doesn't contain source_id parameter - cannot identify payment</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="flow-step-number">4</div>
                <div class="flow-step-content">
                    <h4>Expired Payment</h4>
                    <p>Payment record expired (>30 minutes old)</p>
                </div>
            </div>
        </div>
        
        <h3>Test Different Failure Scenarios:</h3>
        <div style="margin-top: 15px;">
            <a href="payment-return.php?type=regular&status=success" class="btn btn-danger" target="_blank">
                Test: No source_id
            </a>
            <a href="payment-return.php?type=regular&status=success&source_id=invalid_source_123" class="btn btn-danger" target="_blank">
                Test: Invalid source_id
            </a>
            <a href="payment-return.php?type=regular&status=failed" class="btn btn-danger" target="_blank">
                Test: Failed status
            </a>
        </div>
        
        <div class="result-box info" style="margin-top: 20px;">
            <strong>Expected behavior:</strong><br>
            All of these should redirect to payment-failed.php with an appropriate error message.
            Check the logs to see the exact error that occurred.
        </div>
        
        <div style="margin-top: 20px;">
            <a href="?action=view_logs" class="btn btn-secondary">View Error Logs</a>
        </div>
    </div>
    <?php
}

// Action: View Logs
elseif ($action === 'view_logs') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Recent Payment Error Logs</h2>
        <?php
        $log_file = __DIR__ . '/../../../logs/payment_errors.log';
        if (file_exists($log_file)) {
            $lines = file($log_file);
            $recent_lines = array_slice($lines, -100); // Last 100 lines
            
            echo '<div class="result-box info">Showing last 100 lines from payment_errors.log</div>';
            echo '<div class="code-block" style="max-height: 500px; overflow-y: auto;">';
            
            foreach ($recent_lines as $line) {
                $line = htmlspecialchars($line);
                // Highlight important keywords
                $line = str_replace('[PAYMENT-RETURN]', '<span style="color: #ffc107;">[PAYMENT-RETURN]</span>', $line);
                $line = str_replace('[PROCESS-PAYMENT]', '<span style="color: #17a2b8;">[PROCESS-PAYMENT]</span>', $line);
                $line = str_replace('ERROR', '<span style="color: #dc3545;">ERROR</span>', $line);
                $line = str_replace('✓', '<span style="color: #28a745;">✓</span>', $line);
                $line = str_replace('✗', '<span style="color: #dc3545;">✗</span>', $line);
                $line = str_replace('⚠', '<span style="color: #ffc107;">⚠</span>', $line);
                echo $line;
            }
            echo '</div>';
            
            // Search for specific patterns
            $full_log = file_get_contents($log_file);
            $process_payment_count = substr_count($full_log, '[PROCESS-PAYMENT]');
            $payment_return_count = substr_count($full_log, '[PAYMENT-RETURN]');
            $error_count = substr_count($full_log, 'ERROR');
            
            echo '<h3 style="margin-top: 20px;">Log Statistics:</h3>';
            echo '<div class="result-box info">';
            echo '<strong>Total [PROCESS-PAYMENT] entries:</strong> ' . $process_payment_count . '<br>';
            echo '<strong>Total [PAYMENT-RETURN] entries:</strong> ' . $payment_return_count . '<br>';
            echo '<strong>Total ERROR entries:</strong> ' . $error_count;
            echo '</div>';
            
            if ($process_payment_count === 0) {
                echo '<div class="result-box error">';
                echo '⚠ <strong>Critical Issue:</strong> No [PROCESS-PAYMENT] logs found!<br>';
                echo 'This means process-payment.php is never being called.<br>';
                echo 'Check if the checkout page JavaScript is properly sending payment requests.';
                echo '</div>';
            }
        } else {
            echo '<div class="result-box warning">⚠ Log file not found: ' . $log_file . '</div>';
        }
        ?>
        
        <div style="margin-top: 20px;">
            <button onclick="location.reload()" class="btn">Refresh Logs</button>
            <a href="?action=clear_logs" class="btn btn-danger" onclick="return confirm('Clear all payment logs?')">Clear Logs</a>
        </div>
    </div>
    <?php
}

// Action: Flow Diagram
elseif ($action === 'flow_diagram') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Complete Payment Flow Diagram</h2>
        
        <div class="flow-diagram">
            <div class="flow-step">
                <div class="flow-step-number">1</div>
                <div class="flow-step-content">
                    <h4>User Initiates Payment</h4>
                    <p>User fills checkout form and clicks "Pay Now" button</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">2</div>
                <div class="flow-step-content">
                    <h4>JavaScript Sends Request</h4>
                    <p>checkout.js sends POST request to process-payment.php with order data</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">3</div>
                <div class="flow-step-content">
                    <h4>process-payment.php Creates Payment</h4>
                    <p>Creates PayMongo source/payment intent, saves to session AND database</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">4</div>
                <div class="flow-step-content">
                    <h4>Redirect to PayMongo</h4>
                    <p>User is redirected to PayMongo checkout page (GCash/Maya/Card)</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">5</div>
                <div class="flow-step-content">
                    <h4>User Completes Payment</h4>
                    <p>User enters payment details and confirms on PayMongo</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">6</div>
                <div class="flow-step-content">
                    <h4>PayMongo Redirects Back</h4>
                    <p>PayMongo redirects to payment-return.php with status and source_id</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">7</div>
                <div class="flow-step-content">
                    <h4>payment-return.php Recovers Data</h4>
                    <p>Retrieves payment data from session, or falls back to database if session lost</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">8</div>
                <div class="flow-step-content">
                    <h4>Verify Payment with PayMongo</h4>
                    <p>Calls PayMongo API to verify payment status is actually "paid"</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">9</div>
                <div class="flow-step-content">
                    <h4>Create Order in Database</h4>
                    <p>Saves order to orders table, updates inventory, sends notifications</p>
                </div>
            </div>
            
            <div class="flow-step">
                <div class="flow-step-number">10</div>
                <div class="flow-step-content">
                    <h4>Success!</h4>
                    <p>Redirects to payment-success.php with order details</p>
                </div>
            </div>
        </div>
        
        <h3 style="margin-top: 30px;">Common Failure Points:</h3>
        <div class="result-box error">
            <strong>Step 2-3:</strong> JavaScript error or process-payment.php not being called
        </div>
        <div class="result-box error">
            <strong>Step 7:</strong> Session lost and no database backup (most common issue)
        </div>
        <div class="result-box error">
            <strong>Step 8:</strong> PayMongo API verification fails or returns unexpected status
        </div>
        <div class="result-box error">
            <strong>Step 9:</strong> Database error when creating order
        </div>
    </div>
    <?php
}

// Action: Cleanup
elseif ($action === 'cleanup') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Cleanup Test Data</h2>
        <?php
        if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
            // Delete ALL test records (including old ones with wrong format)
            $delete_sql = "DELETE FROM pending_payments WHERE payment_id LIKE 'src_test_%' OR payment_id LIKE 'test_%' OR user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $test_user_id);
            $result = $delete_stmt->execute();
            
            if ($result) {
                echo '<div class="result-box success">✓ Deleted ' . $delete_stmt->affected_rows . ' test payment records</div>';
            } else {
                echo '<div class="result-box error">✗ Error deleting records: ' . $conn->error . '</div>';
            }
            $delete_stmt->close();
            
            // Clear session
            if (isset($_SESSION['pending_payment'])) {
                unset($_SESSION['pending_payment']);
                echo '<div class="result-box success">✓ Cleared pending payment from session</div>';
            }
            
            echo '<div class="result-box info" style="margin-top: 15px;">';
            echo '<strong>✓ All old test data cleaned up!</strong><br>';
            echo 'You can now create fresh test payments with the correct format.';
            echo '</div>';
            
            echo '<div style="margin-top: 20px;">';
            echo '<a href="?action=simulate_payment" class="btn btn-success">Create New Test Payment</a>';
            echo '<a href="?action=check_database" class="btn">View Database</a>';
            echo '<a href="?" class="btn btn-secondary">Back to Menu</a>';
            echo '</div>';
        } else {
            echo '<div class="result-box warning">';
            echo '⚠ This will delete all test payment records from the database.<br>';
            echo 'Records with payment_id starting with "src_test_" or "test_" will be removed.<br>';
            echo '<strong>This includes old test data with incorrect format.</strong>';
            echo '</div>';
            
            // Show what will be deleted
            $preview_sql = "SELECT COUNT(*) as count FROM pending_payments WHERE payment_id LIKE 'src_test_%' OR payment_id LIKE 'test_%' OR user_id = ?";
            $preview_stmt = $conn->prepare($preview_sql);
            $preview_stmt->bind_param("i", $test_user_id);
            $preview_stmt->execute();
            $preview_result = $preview_stmt->get_result();
            if ($preview_result) {
                $count = $preview_result->fetch_assoc()['count'];
                echo '<div class="result-box info">';
                echo '<strong>Records to be deleted:</strong> ' . $count;
                echo '</div>';
            }
            $preview_stmt->close();
            
            echo '<div style="margin-top: 20px;">';
            echo '<a href="?action=cleanup&confirm=yes" class="btn btn-danger">Confirm Cleanup</a>';
            echo '<a href="?" class="btn btn-secondary">Cancel</a>';
            echo '</div>';
        }
        ?>
    </div>
    <?php
}

// Action: Data Format
elseif ($action === 'data_format') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Expected Order Data Format</h2>
        
        <div class="result-box info">
            <strong>Important:</strong> payment-return.php expects specific field names in the order_data.
            Using incorrect field names will cause "No cart items provided" error.
        </div>
        
        <h3>Required Structure:</h3>
        <div class="code-block">{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "09123456789",
    "address": "123 Main Street",
    "city": "Manila",
    "shipping_method": "delivery",
    
    // CRITICAL: Must be "cart_items" not "items"
    "cart_items": [
        {
            "product_id": 1,
            "product_name": "Product Name",
            "quantity": 2,
            "price": 250.00,
            "subtotal": 500.00
        }
    ],
    
    "cart_total": 500.00,
    
    // Optional fields
    "barangay": "Barangay Name",
    "province": "Province Name",
    "zip_code": "1000",
    "notes": "Special instructions",
    "coupon_code": "DISCOUNT10",
    "discount_amount": 50.00
}</div>

        <h3>Common Mistakes:</h3>
        <div class="result-box error">
            ❌ Using "items" instead of "cart_items"
        </div>
        <div class="result-box error">
            ❌ Missing required fields in cart_items (product_id, quantity, price)
        </div>
        <div class="result-box error">
            ❌ cart_items is a string instead of an array
        </div>
        <div class="result-box error">
            ❌ Empty cart_items array
        </div>
        
        <h3>What payment-return.php Does:</h3>
        <div class="flow-diagram">
            <div class="flow-step">
                <div class="flow-step-number">1</div>
                <div class="flow-step-content">
                    <h4>Extract cart_items</h4>
                    <p>Looks for $order_data['cart_items']</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="flow-step-number">2</div>
                <div class="flow-step-content">
                    <h4>Decode if String</h4>
                    <p>If cart_items is a JSON string, decode it to array</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="flow-step-number">3</div>
                <div class="flow-step-content">
                    <h4>Validate</h4>
                    <p>Checks if cart_items is non-empty array</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="flow-step-number">4</div>
                <div class="flow-step-content">
                    <h4>Calculate Totals</h4>
                    <p>Sums up quantities and validates amounts</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="flow-step-number">5</div>
                <div class="flow-step-content">
                    <h4>Create Order</h4>
                    <p>Inserts order into database with all items</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Action: Inspect Data
elseif ($action === 'inspect_data') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Inspect Payment Data</h2>
        <?php
        // Get most recent payment for this user
        $inspect_sql = "SELECT * FROM pending_payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $inspect_stmt = $conn->prepare($inspect_sql);
        $inspect_stmt->bind_param("i", $test_user_id);
        $inspect_stmt->execute();
        $inspect_result = $inspect_stmt->get_result();
        
        if ($inspect_result && $inspect_result->num_rows > 0) {
            $payment = $inspect_result->fetch_assoc();
            $order_data = json_decode($payment['order_data'], true);
            
            echo '<h3>Payment Record:</h3>';
            echo '<table>';
            echo '<tr><th>Field</th><th>Value</th></tr>';
            echo '<tr><td>Payment ID</td><td>' . htmlspecialchars($payment['payment_id']) . '</td></tr>';
            echo '<tr><td>Payment Type</td><td>' . htmlspecialchars($payment['payment_type']) . '</td></tr>';
            echo '<tr><td>Order Type</td><td>' . htmlspecialchars($payment['order_type']) . '</td></tr>';
            echo '<tr><td>Amount</td><td>₱' . number_format($payment['amount'], 2) . '</td></tr>';
            echo '<tr><td>Payment Method</td><td>' . htmlspecialchars($payment['payment_method']) . '</td></tr>';
            echo '<tr><td>Created</td><td>' . $payment['created_at'] . '</td></tr>';
            echo '<tr><td>Expires</td><td>' . $payment['expires_at'] . '</td></tr>';
            echo '</table>';
            
            echo '<h3>Order Data Structure:</h3>';
            echo '<div class="code-block">' . htmlspecialchars(json_encode($order_data, JSON_PRETTY_PRINT)) . '</div>';
            
            // Check for cart_items vs items
            echo '<h3>Diagnostic Checks:</h3>';
            
            if (isset($order_data['cart_items'])) {
                echo '<div class="result-box success">✓ Has "cart_items" field (CORRECT)</div>';
                if (is_array($order_data['cart_items'])) {
                    echo '<div class="result-box success">✓ cart_items is an array (CORRECT)</div>';
                    echo '<div class="result-box info">Cart items count: ' . count($order_data['cart_items']) . '</div>';
                } else {
                    echo '<div class="result-box error">✗ cart_items is NOT an array (WRONG - it\'s a ' . gettype($order_data['cart_items']) . ')</div>';
                }
            } else {
                echo '<div class="result-box error">✗ Missing "cart_items" field (WRONG)</div>';
            }
            
            if (isset($order_data['items'])) {
                echo '<div class="result-box warning">⚠ Has "items" field (OLD FORMAT - should be "cart_items")</div>';
                echo '<div class="result-box info">This is old test data. Please cleanup and create new test payment.</div>';
            }
            
            echo '<div style="margin-top: 20px;">';
            echo '<a href="?action=cleanup" class="btn btn-danger">Cleanup Old Data</a>';
            echo '<a href="?action=simulate_payment" class="btn btn-success">Create New Test Payment</a>';
            echo '</div>';
            
        } else {
            echo '<div class="result-box warning">⚠ No payment records found for user ' . $test_user_id . '</div>';
            echo '<div style="margin-top: 20px;">';
            echo '<a href="?action=simulate_payment" class="btn btn-success">Create Test Payment</a>';
            echo '</div>';
        }
        $inspect_stmt->close();
        ?>
    </div>
    <?php
}

// Action: Clear Logs
elseif ($action === 'clear_logs') {
    ?>
    <a href="?" class="back-link">← Back to Menu</a>
    <div class="test-section">
        <h2>Clear Logs</h2>
        <?php
        $log_file = __DIR__ . '/../../../logs/payment_errors.log';
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            echo '<div class="result-box success">✓ Payment error logs cleared</div>';
        } else {
            echo '<div class="result-box warning">⚠ Log file not found</div>';
        }
        
        echo '<div style="margin-top: 20px;">';
        echo '<a href="?action=view_logs" class="btn">View Logs</a>';
        echo '<a href="?" class="btn btn-secondary">Back to Menu</a>';
        echo '</div>';
        ?>
    </div>
    <?php
}

?>
        </div>
    </div>
</body>
</html>
