<?php
require_once 'class-notif.php'; // Include the Notification class

// Use flexible path resolution
$databasePath = __DIR__ . '/../../../backend/pages/admin-includes/database.php';
$mailerPath = __DIR__ . '/../../../backend/pages/admin-includes/mailer.php';

if (!file_exists($databasePath)) {
    // Try alternative path from root directory
    $databasePath = dirname(__DIR__, 3) . '/backend/pages/admin-includes/database.php';
    $mailerPath = dirname(__DIR__, 3) . '/backend/pages/admin-includes/mailer.php';
}

require_once $databasePath; // Include the database connection
require_once $mailerPath; // Include the mailer for email functionality
require_once __DIR__ . '/../../../includes/session-manager.php';
require_once 'email-queue.php'; // Include the email queue system

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle notification details request
if (isset($_GET['action']) && $_GET['action'] === 'details') {
    header('Content-Type: application/json');
    
    if (!SessionManager::isUserLoggedIn()) {
        echo json_encode(["status" => "error", "message" => "User not logged in"]);
        exit();
    }
    
    $notificationId = $_GET['id'] ?? null;
    
    if (!$notificationId) {
        echo json_encode(["status" => "error", "message" => "Notification ID is required"]);
        exit();
    }
    
    try {
        $userId = SessionManager::getUserId();
        $notification = new Notification($conn);
        $notificationDetails = $notification->getNotificationDetails($notificationId, $userId);
        
        if (!$notificationDetails) {
            echo json_encode(["status" => "error", "message" => "Notification not found"]);
            exit();
        }
        
        echo json_encode(["status" => "success", "notification" => $notificationDetails]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to fetch notification details"]);
        error_log("Database error: " . $e->getMessage());
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $message = trim($_POST['message']); // Trim whitespace from the message
        $notificationType = isset($_POST['notification_type']) ? trim($_POST['notification_type']) : 'promotion';

        // Validate the message
        if (empty($message)) {
            throw new Exception("Message cannot be empty.");
        }

        // Validate notification type
        $allowedTypes = ['promotion', 'announcement', 'event', 'reminder'];
        if (!in_array($notificationType, $allowedTypes)) {
            $notificationType = 'promotion'; // Default fallback
        }

        // Create an instance of the Notification class
        $notification = new Notification($db); // Pass the database connection

        // Fetch all users from the database with their email preferences
        $stmt = $db->prepare("
            SELECT u.id, u.email, 
                   COALESCE(ep.promotion_emails, 1) as promotion_emails,
                   COALESCE(ep.announcement_emails, 1) as announcement_emails,
                   COALESCE(ep.event_emails, 1) as event_emails,
                   COALESCE(ep.reminder_emails, 1) as reminder_emails
            FROM users u
            LEFT JOIN user_email_preferences ep ON u.id = ep.user_id
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($users)) {
            throw new Exception("No users found.");
        }

        // Track success and failure counts
        $successCount = 0;
        $failureCount = 0;
        $emailQueueCount = 0;

        // Initialize email queue
        $emailQueue = new EmailQueue($db);
        
        // Prepare email data for queue
        $emailData = [];
        $subject = "NeoExclusiveCafe - " . ucfirst($notificationType) . " Notification";
        $htmlBody = createNotificationEmailTemplate($message, $notificationType);

        // Loop through each user and prepare notifications
        foreach ($users as $user) {
            $userId = $user['id'];
            $userEmail = $user['email'];

            try {
            // Insert the notification into the database
                $notification->create($userId, $notificationType, $message);
                $successCount++;

                // Check if user wants to receive this type of email
                $emailPreferenceKey = $notificationType . '_emails';
                $wantsEmail = isset($user[$emailPreferenceKey]) ? $user[$emailPreferenceKey] : 1;

                // Add email to queue if valid and user wants to receive it
                if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL) && $wantsEmail) {
                    $emailData[] = [
                        'email' => $userEmail,
                        'subject' => $subject,
                        'body' => $htmlBody,
                        'type' => $notificationType
                    ];
                } else if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL) && !$wantsEmail) {
                    error_log("User ID: $userId has disabled $notificationType emails");
            } else {
                    error_log("Invalid or missing email for user ID: $userId ($userEmail)");
                }
            } catch (Exception $e) {
                $failureCount++;
                error_log("Failed to create notification for user ID: $userId - " . $e->getMessage());
            }
        }

        // Add emails to queue and process
        if (!empty($emailData)) {
            $emailQueueCount = $emailQueue->addToQueue($emailData);
            
            // Process the queue in batches
            $processedEmails = $emailQueue->processQueue(10); // Process up to 10 batches
        }

        // Provide detailed feedback
        $totalUsers = count($users);
        $queueStats = $emailQueue->getQueueStats();
        
        echo "<div style='padding: 20px; background-color: #f0f8ff; border: 1px solid #4CAF50; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>Notification Results:</h3>";
        echo "<p><strong>Total Users:</strong> $totalUsers</p>";
        echo "<p><strong>Database Notifications:</strong> $successCount successful, $failureCount failed</p>";
        echo "<p><strong>Emails Queued:</strong> $emailQueueCount</p>";
        
        if (isset($processedEmails)) {
            echo "<p><strong>Emails Processed:</strong> $processedEmails</p>";
        }
        
        echo "<div style='margin-top: 15px; padding: 10px; background-color: #e8f4fd; border-radius: 5px;'>";
        echo "<h4>Email Queue Status (Last 24 hours):</h4>";
        echo "<p>Pending: {$queueStats['pending']} | Sent: {$queueStats['sent']} | Failed: {$queueStats['failed']}</p>";
        echo "</div>";
        
        if ($successCount > 0) {
            echo "<p style='color: #4CAF50; margin-top: 15px;'><strong>✓</strong> Notifications have been queued and processed successfully!</p>";
        }
        
        if ($queueStats['failed'] > 0) {
            echo "<p style='color: #ff6b6b;'><strong>Note:</strong> Some emails failed to send. Check the error logs for details.</p>";
        }
        
        echo "</div>";
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo "Failed to send notifications: " . htmlspecialchars($e->getMessage());
    }
}

// Function to send email notifications using PHPMailer
function sendEmailNotification($toEmail, $message, $notificationType = 'promotion') {
    try {
        // Validate email address
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email address: $toEmail");
            return false;
        }

        // Create email subject based on notification type
        $subject = "NeoExclusiveCafe - " . ucfirst($notificationType) . " Notification";
        
        // Create HTML email body
        $htmlBody = createNotificationEmailTemplate($message, $notificationType);
        
        // Send email using the existing mailer system
        $result = sendEmail($toEmail, $subject, $htmlBody, true);
        
        if ($result) {
            error_log("Email notification sent successfully to: $toEmail");
            return true;
        } else {
            error_log("Failed to send email notification to: $toEmail");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error sending email notification to $toEmail: " . $e->getMessage());
        return false;
    }
}

// Function to create HTML email template for notifications
function createNotificationEmailTemplate($message, $notificationType) {
    $logoUrl = "http://" . $_SERVER['HTTP_HOST'] . "/NeoExclusiveCafe/assets/images/logo.png";
    $currentDate = date('F j, Y');
    
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>NeoExclusiveCafe Notification</title>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4;
            }
            .container { 
                max-width: 600px; 
                margin: 20px auto; 
                background-color: white; 
                border-radius: 10px; 
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 28px; 
                font-weight: 300;
            }
            .header p { 
                margin: 10px 0 0 0; 
                opacity: 0.9; 
                font-size: 16px;
            }
            .content { 
                padding: 30px 20px; 
            }
            .notification-type { 
                background-color: #e8f4fd; 
                border-left: 4px solid #2196F3; 
                padding: 15px; 
                margin-bottom: 20px; 
                border-radius: 0 5px 5px 0;
            }
            .notification-type h2 { 
                margin: 0 0 10px 0; 
                color: #1976D2; 
                font-size: 20px;
            }
            .message-content { 
                background-color: #f9f9f9; 
                padding: 20px; 
                border-radius: 8px; 
                margin: 20px 0; 
                border: 1px solid #e0e0e0;
                font-size: 16px;
                line-height: 1.7;
            }
            .cta-button { 
                display: inline-block; 
                padding: 15px 30px; 
                margin: 20px 0; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; 
                text-decoration: none; 
                border-radius: 25px; 
                text-align: center; 
                font-weight: bold; 
                transition: transform 0.3s ease;
            }
            .cta-button:hover { 
                transform: translateY(-2px);
            }
            .footer { 
                background-color: #f8f9fa; 
                padding: 20px; 
                text-align: center; 
                font-size: 14px; 
                color: #666; 
                border-top: 1px solid #e0e0e0;
            }
            .social-links { 
                margin: 15px 0; 
            }
            .social-links a { 
                color: #667eea; 
                text-decoration: none; 
                margin: 0 10px; 
            }
            .unsubscribe { 
                font-size: 12px; 
                color: #999; 
                margin-top: 15px;
            }
            .unsubscribe a { 
                color: #999; 
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>NeoExclusiveCafe</h1>
                <p>Your Premium Coffee Experience</p>
            </div>
            
            <div class='content'>
                <div class='notification-type'>
                    <h2>" . ucfirst($notificationType) . " Notification</h2>
                    <p>You have received a new notification from NeoExclusiveCafe</p>
                </div>
                
                <div class='message-content'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                
                <div style='text-align: center;'>
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/NeoExclusiveCafe/frontend/pages/users/dashboard.php' class='cta-button'>
                        Visit Our Cafe
                    </a>
                </div>
            </div>
            
            <div class='footer'>
                <div class='social-links'>
                    <a href='#'>Facebook</a> | 
                    <a href='#'>Instagram</a> | 
                    <a href='#'>Twitter</a>
                </div>
                <p>© 2025 NeoExclusiveCafe. All rights reserved.</p>
                <p>This email was sent to you because you are a registered member of NeoExclusiveCafe.</p>
                <div class='unsubscribe'>
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/NeoExclusiveCafe/frontend/pages/users/unsubscribe.php'>Unsubscribe</a> | 
                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/NeoExclusiveCafe/frontend/pages/users/email-preferences.php'>Email Preferences</a>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification - NeoExclusiveCafe</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .form-container {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .notification-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .notification-type {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .notification-type:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .notification-type.selected {
            border-color: #667eea;
            background-color: #e8f4fd;
        }
        .notification-type input[type="radio"] {
            display: none;
        }
        .notification-type h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 16px;
        }
        .notification-type p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .preview-section {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .preview-section h3 {
            margin-top: 0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Send Notification</h1>
            <p>Send promotional notifications to all registered users</p>
        </div>
        
        <div class="form-container">
            <form method="POST" id="notificationForm">
                <div class="form-group">
                    <label for="notification_type">Notification Type</label>
                    <div class="notification-types">
                        <label class="notification-type">
                            <input type="radio" name="notification_type" value="promotion" checked>
                            <h3>Promotion</h3>
                            <p>Special offers and discounts</p>
                        </label>
                        <label class="notification-type">
                            <input type="radio" name="notification_type" value="announcement">
                            <h3>Announcement</h3>
                            <p>Important updates and news</p>
                        </label>
                        <label class="notification-type">
                            <input type="radio" name="notification_type" value="event">
                            <h3>Event</h3>
                            <p>Upcoming events and activities</p>
                        </label>
                        <label class="notification-type">
                            <input type="radio" name="notification_type" value="reminder">
                            <h3>Reminder</h3>
                            <p>Important reminders</p>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="message">Message Content</label>
                    <textarea name="message" id="message" placeholder="Enter your notification message here..." required></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span> characters
                    </div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    Send Notification to All Users
                </button>
</form>
            
            <div class="preview-section">
                <h3>Email Preview</h3>
                <p>This is how your notification will appear in users' emails:</p>
                <div id="emailPreview" style="border: 1px solid #ddd; padding: 20px; background-color: white; border-radius: 5px;">
                    <p style="color: #666; font-style: italic;">Enter a message above to see the preview...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Character counter
        const messageTextarea = document.getElementById('message');
        const charCount = document.getElementById('charCount');
        const emailPreview = document.getElementById('emailPreview');
        const notificationTypes = document.querySelectorAll('input[name="notification_type"]');
        
        messageTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            updateEmailPreview();
        });
        
        // Notification type selection
        notificationTypes.forEach(type => {
            type.addEventListener('change', function() {
                // Remove selected class from all
                document.querySelectorAll('.notification-type').forEach(el => {
                    el.classList.remove('selected');
                });
                // Add selected class to current
                this.closest('.notification-type').classList.add('selected');
                updateEmailPreview();
            });
        });
        
        // Set initial selected state
        document.querySelector('input[name="notification_type"]:checked').closest('.notification-type').classList.add('selected');
        
        function updateEmailPreview() {
            const message = messageTextarea.value;
            const selectedType = document.querySelector('input[name="notification_type"]:checked').value;
            
            if (message.trim()) {
                emailPreview.innerHTML = `
                    <div style="font-family: Arial, sans-serif;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
                            <h2 style="margin: 0;">NeoExclusiveCafe</h2>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">Your Premium Coffee Experience</p>
                        </div>
                        <div style="padding: 20px; background-color: #f9f9f9; border-radius: 0 0 5px 5px;">
                            <div style="background-color: #e8f4fd; border-left: 4px solid #2196F3; padding: 15px; margin-bottom: 15px;">
                                <h3 style="margin: 0 0 5px 0; color: #1976D2;">${selectedType.charAt(0).toUpperCase() + selectedType.slice(1)} Notification</h3>
                                <p style="margin: 0; color: #666;">You have received a new notification from NeoExclusiveCafe</p>
                            </div>
                            <div style="background-color: white; padding: 15px; border-radius: 5px; border: 1px solid #e0e0e0;">
                                ${message.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                emailPreview.innerHTML = '<p style="color: #666; font-style: italic;">Enter a message above to see the preview...</p>';
            }
        }
        
        // Form submission handling
        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending Notifications...';
            
            // Re-enable button after 5 seconds in case of error
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Notification to All Users';
            }, 5000);
        });
    </script>
</body>
</html>
