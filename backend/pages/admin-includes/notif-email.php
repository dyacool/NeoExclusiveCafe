<?php
require_once '../../php/includes/mailer.php';
require_once '../../php/includes/database.php';

function storeNotification($userId, $message) {
    global $db; 
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $message]);

    // Fetch user email from database
    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userEmail = $stmt->fetchColumn();

    // Send email notification
    if ($userEmail) {
        sendEmailNotification($userEmail, $message);
    }
}

function sendEmailNotification($toEmail, $message) {
    // Example email sending logic
    $subject = "Notification";
    $headers = "From: no-reply@neoexclusivecafe.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($toEmail, $subject, $message, $headers);
}

// Example usage
storeNotification(1, "You have a new order update!");
?>