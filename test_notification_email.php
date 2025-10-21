<?php
/**
 * Test Script for Notification Email System
 * This script tests the improved notification email functionality
 */

require_once 'backend/pages/admin-includes/database.php';
require_once 'backend/pages/admin-includes/mailer.php';
require_once 'frontend/pages/notifications/email-queue.php';

echo "<h1>NeoExclusiveCafe - Email Notification Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
    pre { background-color: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Test 1: Email Configuration
echo "<div class='test-section info'>";
echo "<h2>Test 1: Email Configuration</h2>";
try {
    $config = getEmailConfig();
    echo "<p><strong>✓</strong> Email configuration loaded successfully</p>";
    echo "<pre>" . print_r($config, true) . "</pre>";
} catch (Exception $e) {
    echo "<p><strong>✗</strong> Error loading email configuration: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: PHPMailer Instance Creation
echo "<div class='test-section info'>";
echo "<h2>Test 2: PHPMailer Instance Creation</h2>";
try {
    $mail = createPHPMailer();
    echo "<p><strong>✓</strong> PHPMailer instance created successfully</p>";
} catch (Exception $e) {
    echo "<p><strong>✗</strong> Error creating PHPMailer instance: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Email Queue Table Creation
echo "<div class='test-section info'>";
echo "<h2>Test 3: Email Queue Table</h2>";
try {
    if (isset($db)) {
        $emailQueue = new EmailQueue($db);
        echo "<p><strong>✓</strong> Email queue system initialized successfully</p>";
        
        $stats = $emailQueue->getQueueStats();
        echo "<p>Queue Statistics:</p>";
        echo "<pre>" . print_r($stats, true) . "</pre>";
    } else {
        echo "<p><strong>✗</strong> Database connection not available for EmailQueue</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>✗</strong> Error with email queue: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Test Email Sending
echo "<div class='test-section info'>";
echo "<h2>Test 4: Test Email Sending</h2>";
try {
    $testEmail = "ainepascua4@gmail.com"; // Admin email for testing
    $testSubject = "NeoExclusiveCafe - Email System Test";
    $testBody = "
    <html>
    <body>
        <h2>Email System Test</h2>
        <p>This is a test email to verify that the notification email system is working correctly.</p>
        <p>Test performed at: " . date('Y-m-d H:i:s') . "</p>
        <p>If you receive this email, the system is working properly!</p>
    </body>
    </html>
    ";
    
    echo "<p>Attempting to send test email to: $testEmail</p>";
    
    $result = sendEmail($testEmail, $testSubject, $testBody, true);
    
    if ($result) {
        echo "<p><strong>✓</strong> Test email sent successfully!</p>";
    } else {
        echo "<p><strong>✗</strong> Test email failed to send</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>✗</strong> Error sending test email: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Email Template Generation
echo "<div class='test-section info'>";
echo "<h2>Test 5: Email Template Generation</h2>";
try {
    // Include the notification functions
    require_once 'frontend/pages/notifications/notif.php';
    
    $testMessage = "This is a test notification message to verify the email template generation.";
    $testType = "promotion";
    
    $template = createNotificationEmailTemplate($testMessage, $testType);
    
    echo "<p><strong>✓</strong> Email template generated successfully</p>";
    echo "<p>Template length: " . strlen($template) . " characters</p>";
    
    // Show a preview (first 500 characters)
    echo "<h3>Template Preview:</h3>";
    echo "<pre>" . htmlspecialchars(substr($template, 0, 500)) . "...</pre>";
    
} catch (Exception $e) {
    echo "<p><strong>✗</strong> Error generating email template: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 6: Database Connection and User Data
echo "<div class='test-section info'>";
echo "<h2>Test 6: Database Connection and User Data</h2>";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as user_count FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $userCount = $result->fetch_assoc()['user_count'];
    
    echo "<p><strong>✓</strong> Database connection successful</p>";
    echo "<p>Total users in database: $userCount</p>";
    
    // Check if email preferences table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'user_email_preferences'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p><strong>✓</strong> Email preferences table exists</p>";
        
        $stmt = $db->prepare("SELECT COUNT(*) as pref_count FROM user_email_preferences");
        $stmt->execute();
        $result = $stmt->get_result();
        $prefCount = $result->fetch_assoc()['pref_count'];
        echo "<p>Users with email preferences: $prefCount</p>";
    } else {
        echo "<p><strong>!</strong> Email preferences table does not exist (will be created when needed)</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>✗</strong> Database error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 7: Email Queue Processing
echo "<div class='test-section info'>";
echo "<h2>Test 7: Email Queue Processing</h2>";
try {
    if (isset($db)) {
        $emailQueue = new EmailQueue($db);
        
        // Add a test email to the queue
        $testEmails = [
            [
                'email' => 'ainepascua4@gmail.com',
                'subject' => 'Queue Test Email',
                'body' => '<html><body><h2>Queue Test</h2><p>This email was processed through the queue system.</p></body></html>',
                'type' => 'test'
            ]
        ];
        
        $addedCount = $emailQueue->addToQueue($testEmails);
        echo "<p><strong>✓</strong> Added $addedCount test email(s) to queue</p>";
        
        // Process the queue
        $processedCount = $emailQueue->processQueue(1);
        echo "<p><strong>✓</strong> Processed $processedCount email(s) from queue</p>";
        
        // Get updated stats
        $stats = $emailQueue->getQueueStats();
        echo "<p>Updated Queue Statistics:</p>";
        echo "<pre>" . print_r($stats, true) . "</pre>";
    } else {
        echo "<p><strong>✗</strong> Database connection not available for EmailQueue processing</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>✗</strong> Email queue processing error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='test-section success'>";
echo "<h2>Test Summary</h2>";
echo "<p><strong>✓</strong> All email notification system components have been tested.</p>";
echo "<p>If all tests passed, your email notification system is ready to use!</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Access the notification system at: <a href='frontend/pages/notifications/notif.php'>Send Notifications</a></li>";
echo "<li>Test email preferences at: <a href='frontend/pages/users/email-preferences.php'>Email Preferences</a></li>";
echo "<li>Test unsubscribe at: <a href='frontend/pages/users/unsubscribe.php'>Unsubscribe</a></li>";
echo "</ul>";
echo "</div>";

echo "<div class='test-section info'>";
echo "<h2>Email Log</h2>";
echo "<p>Check the email log for detailed information about email sending:</p>";
echo "<p><a href='logs/email_log.html' target='_blank'>View Email Log</a></p>";
echo "</div>";
?>
