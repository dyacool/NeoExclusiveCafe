<?php
/**
 * Email Preferences Management for NeoExclusiveCafe
 */

// Use flexible path resolution
$databasePath = __DIR__ . '/../../../backend/pages/admin-includes/database.php';

if (!file_exists($databasePath)) {
    // Try alternative path from root directory
    $databasePath = dirname(__DIR__, 3) . '/backend/pages/admin-includes/database.php';
}

require_once $databasePath;

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];
$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $promotionEmails = isset($_POST['promotion_emails']) ? 1 : 0;
        $announcementEmails = isset($_POST['announcement_emails']) ? 1 : 0;
        $eventEmails = isset($_POST['event_emails']) ? 1 : 0;
        $reminderEmails = isset($_POST['reminder_emails']) ? 1 : 0;
        
        // Check if email preferences table exists, create if not
        $createTableSql = "
        CREATE TABLE IF NOT EXISTS user_email_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            promotion_emails TINYINT(1) DEFAULT 1,
            announcement_emails TINYINT(1) DEFAULT 1,
            event_emails TINYINT(1) DEFAULT 1,
            reminder_emails TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->query($createTableSql);
        
        // Insert or update email preferences
        $stmt = $db->prepare("
            INSERT INTO user_email_preferences 
            (user_id, promotion_emails, announcement_emails, event_emails, reminder_emails) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            promotion_emails = VALUES(promotion_emails),
            announcement_emails = VALUES(announcement_emails),
            event_emails = VALUES(event_emails),
            reminder_emails = VALUES(reminder_emails),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->bind_param("iiiii", $userId, $promotionEmails, $announcementEmails, $eventEmails, $reminderEmails);
        
        if ($stmt->execute()) {
            $message = "Your email preferences have been updated successfully!";
            $messageType = "success";
        } else {
            throw new Exception("Failed to update email preferences.");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $message = "Error updating preferences: " . $e->getMessage();
        $messageType = "error";
        error_log("Email preferences error: " . $e->getMessage());
    }
}

// Get current email preferences
$preferences = [
    'promotion_emails' => 1,
    'announcement_emails' => 1,
    'event_emails' => 1,
    'reminder_emails' => 1
];

try {
    $stmt = $db->prepare("SELECT * FROM user_email_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $preferences = [
            'promotion_emails' => $row['promotion_emails'],
            'announcement_emails' => $row['announcement_emails'],
            'event_emails' => $row['event_emails'],
            'reminder_emails' => $row['reminder_emails']
        ];
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching email preferences: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preferences - NeoExclusiveCafe</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
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
        .content {
            padding: 30px;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .preference-group {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .preference-group h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
        }
        .preference-group p {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .checkbox-container input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        .checkbox-container label {
            font-weight: 500;
            cursor: pointer;
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
        .info-section {
            background-color: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #2196F3;
        }
        .info-section h4 {
            margin-top: 0;
            color: #1976D2;
        }
        .info-section p {
            margin-bottom: 10px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Email Preferences</h1>
            <p>Manage your notification preferences</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="preference-group">
                    <h3>📧 Promotion Emails</h3>
                    <p>Receive notifications about special offers, discounts, and promotional deals.</p>
                    <div class="checkbox-container">
                        <input type="checkbox" id="promotion_emails" name="promotion_emails" 
                               <?php echo $preferences['promotion_emails'] ? 'checked' : ''; ?>>
                        <label for="promotion_emails">Enable promotion emails</label>
                    </div>
                </div>
                
                <div class="preference-group">
                    <h3>📢 Announcement Emails</h3>
                    <p>Receive important updates and news about NeoExclusiveCafe.</p>
                    <div class="checkbox-container">
                        <input type="checkbox" id="announcement_emails" name="announcement_emails" 
                               <?php echo $preferences['announcement_emails'] ? 'checked' : ''; ?>>
                        <label for="announcement_emails">Enable announcement emails</label>
                    </div>
                </div>
                
                <div class="preference-group">
                    <h3>🎉 Event Emails</h3>
                    <p>Receive notifications about upcoming events, workshops, and special activities.</p>
                    <div class="checkbox-container">
                        <input type="checkbox" id="event_emails" name="event_emails" 
                               <?php echo $preferences['event_emails'] ? 'checked' : ''; ?>>
                        <label for="event_emails">Enable event emails</label>
                    </div>
                </div>
                
                <div class="preference-group">
                    <h3>⏰ Reminder Emails</h3>
                    <p>Receive important reminders about your orders, appointments, and account updates.</p>
                    <div class="checkbox-container">
                        <input type="checkbox" id="reminder_emails" name="reminder_emails" 
                               <?php echo $preferences['reminder_emails'] ? 'checked' : ''; ?>>
                        <label for="reminder_emails">Enable reminder emails</label>
                    </div>
                </div>
                
                <button type="submit" class="btn">Save Preferences</button>
            </form>
            
            <div class="info-section">
                <h4>📋 Important Information</h4>
                <p><strong>Order Notifications:</strong> You will always receive emails about your order status updates, regardless of these preferences.</p>
                <p><strong>Account Security:</strong> Important security-related emails will always be sent to protect your account.</p>
                <p><strong>Unsubscribe:</strong> If you want to stop receiving all promotional emails, you can <a href="unsubscribe.php">unsubscribe here</a>.</p>
                <p><strong>Contact:</strong> If you have any questions about these preferences, please contact our support team.</p>
            </div>
        </div>
    </div>
</body>
</html>
