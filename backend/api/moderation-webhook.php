<?php
/**
 * Cloudinary Moderation Webhook Handler
 * 
 * Receives and processes moderation callbacks from Cloudinary
 * 
 * @package NeoCafe
 * @version 1.0.0
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../includes/cloudinary-moderation-helper.php';

// Load Cloudinary configuration
require_once __DIR__ . '/../../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Admin\AdminApi;

/**
 * Log webhook event
 */
function logWebhookEvent($message, $data = []) {
    $logFile = __DIR__ . '/../../logs/webhook.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = sprintf(
        "[%s] %s | Data: %s\n",
        $timestamp,
        $message,
        json_encode($data)
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Send admin notification email
 */
function sendModerationAlert($publicId, $moderationResponse, $moderationHelper) {
    if (!$moderationHelper->shouldNotifyAdmin()) {
        return;
    }
    
    $adminEmail = $moderationHelper->getAdminEmail();
    $subject = $moderationHelper->getAdminNotificationSubject();
    
    // Extract rejection reasons
    $reasons = [];
    if (isset($moderationResponse['moderation_labels'])) {
        foreach ($moderationResponse['moderation_labels'] as $label) {
            if (isset($label['Name']) && isset($label['Confidence'])) {
                $reasons[] = $label['Name'] . ' (' . round($label['Confidence'], 2) . '% confidence)';
            }
        }
    }
    
    $reasonsText = !empty($reasons) ? implode(', ', $reasons) : 'Inappropriate content detected';
    
    // Email body
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #ef4444; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
            .content { background-color: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
            .footer { background-color: #f3f4f6; padding: 15px; border-radius: 0 0 5px 5px; font-size: 12px; color: #6b7280; }
            .alert { background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 15px 0; }
            .info-row { margin: 10px 0; }
            .label { font-weight: bold; color: #374151; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2 style='margin: 0;'>⚠️ Image Rejected by Content Moderation</h2>
            </div>
            <div class='content'>
                <div class='alert'>
                    <strong>Action Required:</strong> An uploaded product image has been automatically rejected by the content moderation system.
                </div>
                
                <div class='info-row'>
                    <span class='label'>Image ID:</span> {$publicId}
                </div>
                <div class='info-row'>
                    <span class='label'>Rejection Reason:</span> {$reasonsText}
                </div>
                <div class='info-row'>
                    <span class='label'>Timestamp:</span> " . date('Y-m-d H:i:s') . "
                </div>
                <div class='info-row'>
                    <span class='label'>Action Taken:</span> Image has been automatically deleted from Cloudinary
                </div>
                
                <p style='margin-top: 20px;'>
                    <strong>What this means:</strong><br>
                    The system detected inappropriate content in an uploaded image and automatically rejected it to maintain content quality and safety standards.
                </p>
                
                <p>
                    <strong>Next Steps:</strong><br>
                    - Review moderation logs in the admin dashboard<br>
                    - No further action required unless this is a false positive<br>
                    - Contact Cloudinary support if you believe this was incorrectly flagged
                </p>
            </div>
            <div class='footer'>
                <p style='margin: 0;'>This is an automated notification from NeoCafe Content Moderation System</p>
                <p style='margin: 5px 0 0 0;'>Do not reply to this email</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using mailer
    try {
        require_once __DIR__ . '/../pages/admin-includes/mailer.php';
        
        $result = sendEmail($adminEmail, $subject, $body, true);
        
        if ($result) {
            logWebhookEvent("Admin notification sent successfully", ['email' => $adminEmail]);
        } else {
            logWebhookEvent("Failed to send admin notification", ['email' => $adminEmail]);
        }
    } catch (Exception $e) {
        logWebhookEvent("Failed to send admin notification: " . $e->getMessage(), ['email' => $adminEmail]);
    }
}

try {
    // Get webhook payload
    $payload = file_get_contents('php://input');
    
    if (empty($payload)) {
        logWebhookEvent("Empty payload received");
        http_response_code(400);
        echo json_encode(['error' => 'Empty payload']);
        exit;
    }
    
    // Parse JSON payload
    $data = json_decode($payload, true);
    
    if (!$data) {
        logWebhookEvent("Invalid JSON payload", ['payload' => $payload]);
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    logWebhookEvent("Webhook received", ['notification_type' => $data['notification_type'] ?? 'unknown']);
    
    // Verify this is a moderation notification
    if (!isset($data['notification_type']) || $data['notification_type'] !== 'moderation') {
        logWebhookEvent("Not a moderation notification", ['type' => $data['notification_type'] ?? 'unknown']);
        http_response_code(200);
        echo json_encode(['status' => 'ignored', 'reason' => 'not_moderation']);
        exit;
    }
    
    // Extract moderation data
    if (!isset($data['public_id']) || !isset($data['moderation'])) {
        logWebhookEvent("Missing required fields", $data);
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $publicId = $data['public_id'];
    $moderationData = $data['moderation'][0] ?? null;
    
    if (!$moderationData) {
        logWebhookEvent("No moderation data found", ['public_id' => $publicId]);
        http_response_code(400);
        echo json_encode(['error' => 'No moderation data']);
        exit;
    }
    
    $moderationStatus = $moderationData['status'] ?? 'pending';
    $moderationKind = $moderationData['kind'] ?? 'unknown';
    $moderationResponse = $moderationData['response'] ?? [];
    
    logWebhookEvent("Processing moderation result", [
        'public_id' => $publicId,
        'status' => $moderationStatus,
        'kind' => $moderationKind
    ]);
    
    // Initialize moderation helper
    $moderationHelper = new CloudinaryModerationHelper($conn);
    
    // Log moderation result to database
    $logged = $moderationHelper->logModerationResult(
        $publicId,
        $moderationStatus,
        $moderationKind,
        $moderationResponse
    );
    
    if (!$logged) {
        logWebhookEvent("Failed to log moderation result", ['public_id' => $publicId]);
    }
    
    // Update temp_uploaded_images table
    $moderationHelper->updateTempImageStatus($publicId, $moderationStatus);
    
    // Handle rejection
    if ($moderationStatus === 'rejected') {
        logWebhookEvent("Image rejected", ['public_id' => $publicId]);
        
        // Delete image from Cloudinary if auto-delete is enabled
        if ($moderationHelper->shouldAutoDeleteRejected()) {
            try {
                // Configure Cloudinary
                Configuration::instance([
                    'cloud' => [
                        'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
                        'api_key' => getenv('CLOUDINARY_API_KEY'),
                        'api_secret' => getenv('CLOUDINARY_API_SECRET')
                    ],
                    'url' => [
                        'secure' => true
                    ]
                ]);
                
                $adminApi = new AdminApi();
                $result = $adminApi->deleteAssets([$publicId]);
                
                logWebhookEvent("Image deleted from Cloudinary", [
                    'public_id' => $publicId,
                    'result' => $result
                ]);
            } catch (Exception $e) {
                logWebhookEvent("Failed to delete image from Cloudinary: " . $e->getMessage(), [
                    'public_id' => $publicId
                ]);
            }
        }
        
        // Send admin notification
        sendModerationAlert($publicId, $moderationResponse, $moderationHelper);
        
        // Update temp_uploaded_images to mark as rejected
        $conn->query("UPDATE temp_uploaded_images SET moderation_status = 'rejected' WHERE public_id = '$publicId'");
        
    } elseif ($moderationStatus === 'approved') {
        logWebhookEvent("Image approved", ['public_id' => $publicId]);
        
        // Update temp_uploaded_images to mark as approved
        $conn->query("UPDATE temp_uploaded_images SET moderation_status = 'approved' WHERE public_id = '$publicId'");
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'status' => 'processed',
        'public_id' => $publicId,
        'moderation_status' => $moderationStatus,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    logWebhookEvent("Webhook processed successfully", [
        'public_id' => $publicId,
        'status' => $moderationStatus
    ]);
    
} catch (Exception $e) {
    logWebhookEvent("Webhook processing error: " . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
