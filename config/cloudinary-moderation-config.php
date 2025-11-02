<?php
/**
 * Cloudinary Content Moderation Configuration
 * 
 * This configuration file defines settings for automatic image content moderation
 * using Cloudinary's moderation add-ons (AWS Rekognition, Google Vision AI, etc.)
 * 
 * @package NeoCafe
 * @version 1.0.0
 */

return [
    'moderation' => [
        // Enable/disable moderation globally
        'enabled' => true,
        
        // Moderation provider: 'aws_rek' (AWS Rekognition), 'google_vision', 'webpurify'
        'provider' => 'aws_rek',
        
        // Auto-reject threshold (0.0 to 1.0)
        // Images with confidence scores above this threshold will be automatically rejected
        'auto_reject_threshold' => 0.8, // 80% confidence
        
        // Content categories to detect and their enabled status
        'categories' => [
            'explicit_nudity' => true,      // Detect explicit nudity
            'suggestive' => true,            // Detect suggestive content
            'violence' => true,              // Detect violent content
            'visually_disturbing' => true,   // Detect disturbing imagery
            'drugs' => true,                 // Detect drug-related content
            'alcohol' => false,              // Allow alcohol (bakery products may contain alcohol)
            'tobacco' => true,               // Detect tobacco products
            'hate_symbols' => true,          // Detect hate symbols
            'rude_gestures' => true,         // Detect offensive gestures
        ],
        
        // Notification settings
        'notify_admin_on_rejection' => true,
        'admin_email' => 'admin@neocafe.cafe',
        'admin_notification_subject' => 'NeoCafe: Image Rejected by Content Moderation',
        
        // Webhook settings
        'webhook_enabled' => true,
        'webhook_url' => 'https://neocafe.cafe/backend/api/moderation-webhook.php',
        
        // Retry settings for failed moderation checks
        'max_retry_attempts' => 3,
        'retry_delay_seconds' => 2,
        
        // Timeout settings
        'moderation_timeout_seconds' => 20, // Max time to wait for moderation result
        'polling_interval_seconds' => 2,     // How often to check for results
        
        // Logging settings
        'log_all_moderations' => true,       // Log all moderation events (approved, rejected, pending)
        'log_file_path' => __DIR__ . '/../logs/moderation.log',
        
        // Cleanup settings
        'auto_delete_rejected_images' => true,  // Automatically delete rejected images from Cloudinary
        'cleanup_old_logs_days' => 30,          // Delete moderation logs older than X days
        
        // Testing mode
        'test_mode' => false,                   // If true, don't actually reject images, just log
        'test_mode_always_approve' => false,    // If true in test mode, always approve images
    ],
    
    // Cloudinary API credentials (should be loaded from environment variables)
    'cloudinary' => [
        'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME') ?: '',
        'api_key' => getenv('CLOUDINARY_API_KEY') ?: '',
        'api_secret' => getenv('CLOUDINARY_API_SECRET') ?: '',
    ],
    
    // Error messages for different rejection reasons
    'error_messages' => [
        'explicit_nudity' => 'Image rejected: Contains explicit or inappropriate content',
        'suggestive' => 'Image rejected: Contains suggestive content',
        'violence' => 'Image rejected: Contains violent or disturbing content',
        'drugs' => 'Image rejected: Contains drug-related content',
        'tobacco' => 'Image rejected: Contains tobacco-related content',
        'hate_symbols' => 'Image rejected: Contains offensive symbols',
        'rude_gestures' => 'Image rejected: Contains offensive gestures',
        'default' => 'Image rejected: Inappropriate content detected',
    ],
];
