<?php
/**
 * Domain Routing Test Script
 * Use this to verify domain detection and routing logic
 */

// Include domain configuration
$config = require_once 'config/domain-config.php';

// Get current domain
$current_domain = $_SERVER['HTTP_HOST'];
$request_uri = $_SERVER['REQUEST_URI'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Domain Routing Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .info {
            color: #007bff;
        }
        .warning {
            color: #ffc107;
        }
        h1 {
            color: #333;
        }
        h2 {
            color: #666;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        td:first-child {
            font-weight: bold;
            width: 200px;
        }
        .test-links {
            margin: 20px 0;
        }
        .test-links a {
            display: inline-block;
            margin: 5px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .test-links a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🔍 Domain Routing Test</h1>
    
    <div class='test-box'>
        <h2>Current Request Information</h2>
        <table>
            <tr>
                <td>Current Domain:</td>
                <td class='info'>{$current_domain}</td>
            </tr>
            <tr>
                <td>Request URI:</td>
                <td class='info'>{$request_uri}</td>
            </tr>
            <tr>
                <td>Environment:</td>
                <td class='info'>" . getEnvironment() . "</td>
            </tr>
        </table>
    </div>
    
    <div class='test-box'>
        <h2>Domain Detection Results</h2>
        <table>
            <tr>
                <td>Is Admin Domain?</td>
                <td class='" . (isAdminDomain($current_domain) ? 'success' : 'warning') . "'>" 
                    . (isAdminDomain($current_domain) ? '✓ YES' : '✗ NO') . 
                "</td>
            </tr>
            <tr>
                <td>Is Rider Domain?</td>
                <td class='" . (isRiderDomain($current_domain) ? 'success' : 'warning') . "'>" 
                    . (isRiderDomain($current_domain) ? '✓ YES' : '✗ NO') . 
                "</td>
            </tr>
            <tr>
                <td>Is Main Domain?</td>
                <td class='" . (!isAdminDomain($current_domain) && !isRiderDomain($current_domain) ? 'success' : 'warning') . "'>" 
                    . (!isAdminDomain($current_domain) && !isRiderDomain($current_domain) ? '✓ YES' : '✗ NO') . 
                "</td>
            </tr>
        </table>
    </div>
    
    <div class='test-box'>
        <h2>Expected Routing</h2>
        <table>
            <tr>
                <td>Would Redirect To:</td>
                <td class='info'>";
                
if (isAdminDomain($current_domain)) {
    echo $config['admin_path'];
} elseif (isRiderDomain($current_domain)) {
    echo $config['rider_path'];
} else {
    echo $config['default_path'];
}

echo "          </td>
            </tr>
        </table>
    </div>
    
    <div class='test-box'>
        <h2>Configuration for Current Environment</h2>
        <table>
            <tr>
                <td>Admin Domain:</td>
                <td>{$config['admin_domain']}</td>
            </tr>
            <tr>
                <td>Rider Domain:</td>
                <td>{$config['rider_domain']}</td>
            </tr>
            <tr>
                <td>Main Domain:</td>
                <td>{$config['main_domain']}</td>
            </tr>
            <tr>
                <td>Admin Path:</td>
                <td>{$config['admin_path']}</td>
            </tr>
            <tr>
                <td>Rider Path:</td>
                <td>{$config['rider_path']}</td>
            </tr>
            <tr>
                <td>Default Path:</td>
                <td>{$config['default_path']}</td>
            </tr>
        </table>
    </div>
    
    <div class='test-box'>
        <h2>Test Links</h2>
        <p>Click these links to test routing (make sure domains are configured in hosts file):</p>
        <div class='test-links'>";

// Generate test links based on environment
$env = getEnvironment();
if ($env === 'development') {
    echo "
            <a href='http://neocafe.cafe:8080/test-domain-routing.php'>Main Domain</a>
            <a href='http://rider.neocafe.cafe:8080/test-domain-routing.php'>Rider Domain</a>
            <a href='http://admin.neocafe.cafe:8080/test-domain-routing.php'>Admin Domain</a>
    ";
} elseif ($env === 'production') {
    echo "
            <a href='https://neocafe.shop/test-domain-routing.php'>Main Domain</a>
            <a href='https://rider.neocafe.shop/test-domain-routing.php'>Rider Domain</a>
            <a href='https://admin.neocafe.shop/test-domain-routing.php'>Admin Domain</a>
    ";
}

echo "
        </div>
    </div>
    
    <div class='test-box'>
        <h2>Setup Instructions</h2>
        <p>For detailed setup instructions, see: <code>docs/RIDER-DOMAIN-SETUP.md</code></p>
    </div>
</body>
</html>";
?>
