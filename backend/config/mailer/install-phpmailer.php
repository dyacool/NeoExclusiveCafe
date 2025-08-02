<?php
// Enable full error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if PHPMailer is already available
$phpmailer_available = class_exists('PHPMailer\PHPMailer\PHPMailer');

// Define possible PHPMailer locations
$possible_paths = [
    __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php',
    __DIR__ . '/../../php/auth/vendor/phpmailer/phpmailer/src/PHPMailer.php',
    __DIR__ . '/../auth/vendor/phpmailer/phpmailer/src/PHPMailer.php'
];

// Check if PHPMailer files exist
$phpmailer_exists = false;
$found_path = '';

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $phpmailer_exists = true;
        $found_path = realpath($path);
        break;
    }
}

// Define base directory for the application
$base_dir = realpath(__DIR__ . '/../../');

// Check if we can create a composer.json file
$composer_exists = file_exists($base_dir . '/composer.json');
$can_create_composer = is_writable($base_dir);

// Check if Composer is installed on the system
$composer_installed = false;
if (function_exists('exec')) {
    exec('composer --version', $output, $return_var);
    $composer_installed = ($return_var === 0);
}

// Process form submission
$install_attempted = false;
$install_success = false;
$install_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $install_attempted = true;
    
    if ($_POST['action'] === 'install_manual') {
        $install_message = "To manually install PHPMailer:<br>
            1. Download PHPMailer from <a href='https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip'>GitHub</a><br>
            2. Extract the zip file<br>
            3. Create a directory at " . $base_dir . "/vendor/phpmailer/phpmailer/<br>
            4. Copy the 'src' folder from the extracted zip to that directory<br>";
    } elseif ($_POST['action'] === 'install_composer' && $composer_installed) {
        // Attempt to install PHPMailer using Composer
        $current_dir = getcwd();
        chdir($base_dir);
        
        if (!$composer_exists) {
            // Create a basic composer.json file
            $composer_json = json_encode([
                'require' => [
                    'phpmailer/phpmailer' => '^6.8'
                ]
            ], JSON_PRETTY_PRINT);
            
            file_put_contents('composer.json', $composer_json);
        }
        
        // Run composer install
        exec('composer require phpmailer/phpmailer', $output, $return_var);
        
        // Change back to original directory
        chdir($current_dir);
        
        $install_success = ($return_var === 0);
        $install_message = $install_success ? 
            "PHPMailer installed successfully!" : 
            "Failed to install PHPMailer. Error: " . implode("<br>", $output);
    }
}

// Check again after installation attempt
if ($install_attempted && !$phpmailer_exists) {
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $phpmailer_exists = true;
            $found_path = realpath($path);
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPMailer Installation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 800px; margin: 0 auto; }
        .container { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        button, .button { background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 10px; }
        button:hover, .button:hover { background-color: #0069d9; }
        .status-box { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .status-success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .status-error { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .status-warning { background-color: #fff3cd; border: 1px solid #ffeeba; }
        .status-info { background-color: #d1ecf1; border: 1px solid #bee5eb; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <h1>PHPMailer Installation</h1>
    
    <div class="container">
        <h2>Current Status</h2>
        
        <?php if ($phpmailer_available || $phpmailer_exists): ?>
            <div class="status-box status-success">
                <h3 class="success">PHPMailer is available</h3>
                <?php if ($found_path): ?>
                    <p>PHPMailer found at: <?= htmlspecialchars($found_path) ?></p>
                <?php endif; ?>
            </div>
            <p>You can now use the email features in your application.</p>
            <a href="test-email.php" class="button">Test Email Sending</a>
            
        <?php else: ?>
            <div class="status-box status-error">
                <h3 class="error">PHPMailer is not installed</h3>
                <p>The email functionality will not work until PHPMailer is installed.</p>
            </div>
            
            <h2>Installation Options</h2>
            
            <?php if ($install_attempted): ?>
                <div class="status-box <?= $install_success ? 'status-success' : 'status-error' ?>">
                    <h3><?= $install_success ? 'Installation Successful' : 'Installation Failed' ?></h3>
                    <p><?= $install_message ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php if ($composer_installed): ?>
                    <div class="status-box status-info">
                        <h3>Option 1: Install with Composer (Recommended)</h3>
                        <p>Composer is installed on your system. You can install PHPMailer automatically.</p>
                        <button type="submit" name="action" value="install_composer">Install PHPMailer with Composer</button>
                    </div>
                <?php else: ?>
                    <div class="status-box status-warning">
                        <h3>Option 1: Install with Composer (Not Available)</h3>
                        <p>Composer is not installed on your system. Consider installing it from <a href="https://getcomposer.org/download/" target="_blank">getcomposer.org</a></p>
                    </div>
                <?php endif; ?>
                
                <div class="status-box status-info">
                    <h3>Option 2: Manual Installation</h3>
                    <p>Download PHPMailer and place it in the correct directory.</p>
                    <button type="submit" name="action" value="install_manual">Show Manual Installation Instructions</button>
                </div>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <h3>Additional Information</h3>
            <ul>
                <li>Base Directory: <?= htmlspecialchars($base_dir) ?></li>
                <li>Composer Installed: <?= $composer_installed ? 'Yes' : 'No' ?></li>
                <li>composer.json Exists: <?= $composer_exists ? 'Yes' : 'No' ?></li>
                <li>Can Create Files in Base Directory: <?= $can_create_composer ? 'Yes' : 'No' ?></li>
            </ul>
        </div>
    </div>
    
    <p style="margin-top: 30px; text-align: center;">
        <a href="test-email.php">Back to Email Test Page</a>
    </p>
</body>
</html> 