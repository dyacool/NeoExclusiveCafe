<?php
/**
 * Email Configuration for NeoExclusiveCafe
 * 
 * Gmail SMTP Setup Instructions:
 * 1. Go to your Google Account settings (https://myaccount.google.com/)
 * 2. Enable 2-Step Verification under Security if not already enabled
 * 3. After enabling 2-Step Verification, go to "App passwords"
 * 4. Select "Mail" as the app and "Windows Computer" as the device
 * 5. Click "Generate" to get a 16-character password
 * 6. Copy the generated password and paste it in the smtp_pass field below
 * 
 * Note: Do not use your regular Gmail password. You must use an App Password.
 */

// Email configuration
$email_config = [
    // SMTP Configuration
    'smtp_enabled' => true,
    
    // Gmail SMTP settings
    'smtp_host' => 'smtp.gmail.com',
    'smtp_user' => 'noreplyneoexclusive@gmail.com',
    'smtp_pass' => 'cgfc ktij ytbo wlgu', // Your Gmail app password
    'smtp_secure' => 'tls',
    'smtp_port' => 587,
    
    // Email sender information
    'from_email' => 'noreplyneoexclusive@gmail.com',
    'from_name' => 'Neo Exclusive Cafe Orders',
    'reply_email' => 'noreplyneoexclusive@gmail.com',
    
    // Admin email (where order notifications will be sent)
    'admin_email' => 'ainepascua4@gmail.com'
]; 