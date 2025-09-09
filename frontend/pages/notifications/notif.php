<?php
require_once 'class-notif.php';
require_once '../../user-includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        // Validate the message
        if (empty($message)) {
            throw new Exception("Message cannot be empty.");
        }

        // Create an instance of the Notification class (mysqli)
        $notification = new Notification($conn);

        // Fetch all users from the database (only verified for order visibility rule doesn't matter here)
        $stmt = $conn->prepare("SELECT id, email FROM users");
        $stmt->execute();
        $res = $stmt->get_result();
        $users = $res->fetch_all(MYSQLI_ASSOC);

        if (empty($users)) {
            throw new Exception("No users found.");
        }

        // Loop through each user and send notifications
        foreach ($users as $user) {
            $userId = $user['id'];
            $userEmail = $user['email'];
            $type = 'system';
            $notification->create($userId, $type, 'System Message', $message, null);

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
    <textarea name="message" placeholder="Enter message for all users"></textarea>
    <button type="submit">Send Notification</button>
</form>
