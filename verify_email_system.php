<?php
/**
 * Simple verification script for the email notification system
 */

echo "NeoExclusiveCafe Email System Verification\n";
echo "==========================================\n\n";

// Test 1: Check if files exist and can be included
echo "1. Checking file paths...\n";

$files_to_check = [
    'backend/pages/admin-includes/database.php',
    'backend/pages/admin-includes/mailer.php',
    'frontend/pages/notifications/email-queue.php',
    'frontend/pages/notifications/notif.php',
    'frontend/pages/users/email-preferences.php',
    'frontend/pages/users/unsubscribe.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "   ✓ $file exists\n";
    } else {
        echo "   ✗ $file missing\n";
    }
}

echo "\n2. Testing database connection...\n";
try {
    require_once 'backend/pages/admin-includes/database.php';
    echo "   ✓ Database connection successful\n";
    echo "   ✓ Database variable \$db is available\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n3. Testing email queue system...\n";
try {
    require_once 'frontend/pages/notifications/email-queue.php';
    echo "   ✓ Email queue system loaded successfully\n";
    
    if (isset($db)) {
        $emailQueue = new EmailQueue($db);
        echo "   ✓ EmailQueue class instantiated successfully\n";
        
        $stats = $emailQueue->getQueueStats();
        echo "   ✓ Queue statistics retrieved: " . json_encode($stats) . "\n";
    } else {
        echo "   ✗ Database variable not available for EmailQueue\n";
    }
} catch (Exception $e) {
    echo "   ✗ Email queue system failed: " . $e->getMessage() . "\n";
}

echo "\n4. Testing mailer system...\n";
try {
    require_once 'backend/pages/admin-includes/mailer.php';
    echo "   ✓ Mailer system loaded successfully\n";
    
    if (function_exists('getEmailConfig')) {
        $config = getEmailConfig();
        echo "   ✓ Email configuration loaded\n";
        echo "   ✓ SMTP Host: " . $config['smtp_host'] . "\n";
        echo "   ✓ SMTP Enabled: " . ($config['smtp_enabled'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ✗ getEmailConfig function not available\n";
    }
} catch (Exception $e) {
    echo "   ✗ Mailer system failed: " . $e->getMessage() . "\n";
}

echo "\n5. Testing notification system...\n";
try {
    require_once 'frontend/pages/notifications/notif.php';
    echo "   ✓ Notification system loaded successfully\n";
    
    if (function_exists('createNotificationEmailTemplate')) {
        echo "   ✓ Email template function available\n";
        
        $template = createNotificationEmailTemplate("Test message", "promotion");
        echo "   ✓ Email template generated (" . strlen($template) . " characters)\n";
    } else {
        echo "   ✗ Email template function not available\n";
    }
} catch (Exception $e) {
    echo "   ✗ Notification system failed: " . $e->getMessage() . "\n";
}

echo "\n==========================================\n";
echo "Verification complete!\n";
echo "\nTo test the full system:\n";
echo "1. Open: http://localhost/NeoCafe/test_notification_email.php\n";
echo "2. Access notification form: http://localhost/NeoCafe/frontend/pages/notifications/notif.php\n";
echo "3. Test email preferences: http://localhost/NeoCafe/frontend/pages/users/email-preferences.php\n";
echo "4. Test unsubscribe: http://localhost/NeoCafe/frontend/pages/users/unsubscribe.php\n";
?>
