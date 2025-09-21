<?php
/**
 * Unsubscribe from Email Notifications - NeoExclusiveCafe
 */

// Use flexible path resolution
$databasePath = __DIR__ . '/../../../backend/pages/admin-includes/database.php';

if (!file_exists($databasePath)) {
    // Try alternative path from root directory
    $databasePath = dirname(__DIR__, 3) . '/backend/pages/admin-includes/database.php';
}

require_once $databasePath;

$message = "";
$messageType = "";
$email = "";
$unsubscribed = false;

// Handle unsubscribe request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            throw new Exception("Please enter your email address.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Check if user exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $userId = $user['id'];
            
            // Create email preferences table if it doesn't exist
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
            
            // Disable all promotional emails
            $stmt = $db->prepare("
                INSERT INTO user_email_preferences 
                (user_id, promotion_emails, announcement_emails, event_emails, reminder_emails) 
                VALUES (?, 0, 0, 0, 0)
                ON DUPLICATE KEY UPDATE
                promotion_emails = 0,
                announcement_emails = 0,
                event_emails = 0,
                reminder_emails = 0,
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                $message = "You have been successfully unsubscribed from promotional emails. You will still receive important order and account notifications.";
                $messageType = "success";
                $unsubscribed = true;
            } else {
                throw new Exception("Failed to unsubscribe. Please try again.");
            }
            
            $stmt->close();
            
        } else {
            $message = "Email address not found in our system. Please check your email address and try again.";
            $messageType = "error";
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
        error_log("Unsubscribe error: " . $e->getMessage());
    }
}

// Handle GET request with email parameter (for unsubscribe links in emails)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email'])) {
    $email = trim($_GET['email']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - NeoExclusiveCafe</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
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
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
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
        .alternative-actions {
            text-align: center;
            margin-top: 20px;
        }
        .alternative-actions a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .alternative-actions a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Unsubscribe</h1>
            <p>Manage your email preferences</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$unsubscribed): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="Enter your email address" required>
                    </div>
                    
                    <button type="submit" class="btn">Unsubscribe from Promotional Emails</button>
                </form>
            <?php endif; ?>
            
            <div class="info-section">
                <h4>📋 What happens when you unsubscribe?</h4>
                <p><strong>You will stop receiving:</strong></p>
                <ul>
                    <li>Promotional offers and discounts</li>
                    <li>Marketing announcements</li>
                    <li>Event notifications</li>
                    <li>General reminders</li>
                </ul>
                
                <p><strong>You will still receive:</strong></p>
                <ul>
                    <li>Order confirmations and updates</li>
                    <li>Account security notifications</li>
                    <li>Important service announcements</li>
                </ul>
            </div>
            
            <div class="alternative-actions">
                <p>Looking for more control?</p>
                <a href="email-preferences.php">Manage Email Preferences</a> |
                <a href="login.php">Login to Your Account</a>
            </div>
        </div>
    </div>
</body>
</html>
