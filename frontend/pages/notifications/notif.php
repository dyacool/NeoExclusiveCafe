<?php
require_once 'class-notif.php'; // Include the Notification class
require_once '../../user-includes/database.php'; // Include the database connection

// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle notification details request
if (isset($_GET['action']) && $_GET['action'] === 'details') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(["status" => "error", "message" => "User not logged in"]);
        exit();
    }
    
    $notificationId = $_GET['id'] ?? null;
    
    if (!$notificationId) {
        echo json_encode(["status" => "error", "message" => "Notification ID is required"]);
        exit();
    }
    
    try {
        $userId = $_SESSION['user_id'];
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

        // Validate the message
        if (empty($message)) {
            throw new Exception("Message cannot be empty.");
        }

        // Create an instance of the Notification class
        $notification = new Notification($db); // Pass the database connection

        // Fetch all users from the database
        $stmt = $db->prepare("SELECT id, email FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($users)) {
            throw new Exception("No users found.");
        }

        // Loop through each user and send notifications
        foreach ($users as $user) {
            $userId = $user['id'];
            $userEmail = $user['email'];
            $type = 'promotion';

            // Insert the notification into the database
            $notification->create($userId, $type, $message);

            // Send the email notification
            if ($userEmail) {
                sendEmailNotification($userEmail, $message);
            } else {
                error_log("Email not found for user ID: $userId");
            }
        }

        echo "Notifications sent successfully to all users!";
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo "Failed to send notifications: " . htmlspecialchars($e->getMessage());
    }
}

// Function to send email notifications
function sendEmailNotification($toEmail, $message) {
    // Simple email sending logic
    $subject = "Notification";
    $headers = "From: no-reply@neoexclusivecafe.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!mail($toEmail, $subject, $message, $headers)) {
        throw new Exception("Failed to send email to $toEmail.");
    }
}
?>

<form method="POST">
    <textarea name="message" placeholder="Enter Promotion Details"></textarea>
    <button type="submit">Send Notification</button>
</form>
