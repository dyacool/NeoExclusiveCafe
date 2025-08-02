<?php
/**
 * Dummy Mailer for NeoExclusiveCafe
 * 
 * This file provides a simple way to log emails instead of sending them,
 * which is useful for development or when the email system isn't configured yet.
 */

// Include necessary files
require_once __DIR__ . "/mailer.php";

// Override the sendEmail function for testing
function sendDummyEmail($to, $subject, $body, $isHTML = true) {
    try {
        // Ensure mailer functions are loaded first
        if (!function_exists('getEmailConfig')) {
            require_once __DIR__ . "/mailer.php";
        }
        
        // Create logs directory if it doesn't exist
        $logDir = $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/logs";
        if (!file_exists($logDir)) {
            if (!mkdir($logDir, 0777, true)) {
                error_log("Failed to create logs directory at $logDir");
            }
        }
        
        // Create the email log file
        $logFile = $logDir . "/email_log.html";
        
        // Format the email for logging
        $timestamp = date('Y-m-d H:i:s');
        $emailLog = "
        <div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>
            <h3>Email Sent at $timestamp</h3>
            <p><strong>To:</strong> $to</p>
            <p><strong>Subject:</strong> $subject</p>
            <div style='margin-top: 10px; padding: 10px; border: 1px solid #eee; background-color: #f9f9f9;'>
                <h4>Email Body:</h4>
                $body
            </div>
        </div>
        <hr>
        ";
        
        // Append to log file
        if (!file_exists($logFile)) {
            $header = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>NeoExclusiveCafe Email Log</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #333; }
                    .info { background-color: #e0f7fa; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <h1>NeoExclusiveCafe Email Log</h1>
                <div class='info'>
                    <p>This file contains emails that would have been sent by the system.</p>
                    <p>The actual email sending is disabled for development purposes.</p>
                </div>
            ";
            file_put_contents($logFile, $header);
        }
        
        // Append the email log
        file_put_contents($logFile, $emailLog, FILE_APPEND);
        
        // Log to error log as well
        error_log("Dummy email sent - To: $to, Subject: $subject");
        
        // Return success
        return true;
    } catch (Exception $e) {
        error_log("Error in dummy email: " . $e->getMessage());
        return false;
    }
}

// Function to redirect to the email log file
function viewEmailLog() {
    $logFile = $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/logs/email_log.html";
    if (file_exists($logFile)) {
        header("Location: /NeoExclusiveCafe/logs/email_log.html");
        exit;
    } else {
        echo "No email log file exists yet.";
    }
}
?> 