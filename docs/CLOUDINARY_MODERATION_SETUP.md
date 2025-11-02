# Cloudinary Content Moderation Setup Guide

## Overview

This guide explains how to set up and configure Cloudinary's automatic content moderation for the NeoCafe product image upload system.

## Prerequisites

- Cloudinary account with API credentials
- AWS Rekognition add-on enabled in Cloudinary (or alternative moderation provider)
- Database tables created (run migration: `001_create_image_moderation_tables.sql`)

## Configuration

### 1. Environment Variables

Ensure your `.env` file contains the following Cloudinary credentials:

```env
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

### 2. Moderation Configuration

The moderation settings are configured in `config/cloudinary-moderation-config.php`:

```php
'moderation' => [
    'enabled' => true,                    // Enable/disable moderation
    'provider' => 'aws_rek',              // Moderation provider
    'auto_reject_threshold' => 0.8,       // 80% confidence threshold
    'notify_admin_on_rejection' => true,  // Send email notifications
    'admin_email' => 'admin@neocafe.cafe' // Admin notification email
]
```

### 3. Enable AWS Rekognition in Cloudinary

1. Log in to your [Cloudinary Console](https://cloudinary.com/console)
2. Navigate to **Settings** → **Add-ons**
3. Find **AWS Rekognition Moderation**
4. Click **Enable** or **Subscribe**
5. Configure the add-on settings:
   - Enable automatic moderation
   - Set confidence thresholds
   - Configure categories to detect

### 4. Configure Webhook (Optional but Recommended)

For asynchronous moderation results:

1. In Cloudinary Console, go to **Settings** → **Notifications**
2. Add a new notification URL:
   ```
   https://neocafe.cafe/backend/api/moderation-webhook.php
   ```
3. Select event type: **Moderation**
4. Save the webhook configuration

## Features

### Automatic Content Detection

The system automatically detects and rejects images containing:

- ✅ Explicit nudity
- ✅ Suggestive content
- ✅ Violence and gore
- ✅ Drugs
- ✅ Tobacco
- ✅ Hate symbols
- ✅ Offensive gestures
- ❌ Alcohol (disabled - bakery products may contain alcohol)

### Rejection Workflow

When an image is flagged as inappropriate:

1. **Automatic Deletion**: Image is deleted from Cloudinary
2. **Database Logging**: Rejection is logged to `image_moderation_log` table
3. **Admin Notification**: Email sent to configured admin email
4. **User Feedback**: Error message displayed to user

### Moderation Status Tracking

All moderation events are tracked in the database:

- **Approved**: Image passed moderation checks
- **Rejected**: Image failed moderation (confidence > 80%)
- **Pending**: Moderation in progress

## Usage

### In Code

```php
// Initialize helper
require_once __DIR__ . '/backend/includes/cloudinary-moderation-helper.php';
$moderationHelper = new CloudinaryModerationHelper($conn);

// Check if moderation is enabled
if ($moderationHelper->isModerationEnabled()) {
    // Get moderation status for an image
    $status = $moderationHelper->getModerationStatus($publicId);
    
    // Log moderation result
    $moderationHelper->logModerationResult($publicId, 'approved', 'aws_rek', $responseData);
}
```

### Upload with Moderation

When uploading images via Cloudinary API, include the moderation parameter:

```php
$uploadResult = $cloudinary->uploadApi()->upload($filePath, [
    'folder' => 'neocafe/products',
    'moderation' => 'aws_rek',
    'notification_url' => 'https://neocafe.cafe/backend/api/moderation-webhook.php'
]);
```

## Testing

### Test Mode

Enable test mode in configuration to test without actually rejecting images:

```php
'test_mode' => true,
'test_mode_always_approve' => true
```

### Manual Testing

1. Upload a test image through the add-product form
2. Check the `image_moderation_log` table for the result
3. Verify admin email notification (if rejection occurs)
4. Check logs at `logs/moderation.log`

## Monitoring

### View Moderation Statistics

```php
$moderationHelper = new CloudinaryModerationHelper($conn);
$stats = $moderationHelper->getModerationStats(30); // Last 30 days
```

### Database Queries

```sql
-- View recent moderation events
SELECT * FROM image_moderation_log 
ORDER BY created_at DESC 
LIMIT 50;

-- Count rejections by date
SELECT DATE(created_at) as date, COUNT(*) as rejections
FROM image_moderation_log
WHERE status = 'rejected'
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- View rejection reasons
SELECT response_data->>'$.ModerationLabels' as reasons, COUNT(*) as count
FROM image_moderation_log
WHERE status = 'rejected'
GROUP BY reasons;
```

### Cleanup Old Logs

Automatically cleanup logs older than 30 days:

```php
$moderationHelper = new CloudinaryModerationHelper($conn);
$deleted = $moderationHelper->cleanupOldLogs();
echo "Deleted $deleted old log entries";
```

## Troubleshooting

### Issue: Moderation not working

**Solution:**
1. Verify AWS Rekognition add-on is enabled in Cloudinary
2. Check that `moderation => 'aws_rek'` is included in upload parameters
3. Verify webhook URL is correctly configured
4. Check error logs: `logs/moderation.log` and `logs/php_errors.log`

### Issue: Webhook not receiving callbacks

**Solution:**
1. Verify webhook URL is publicly accessible (not localhost)
2. Check Cloudinary webhook configuration in console
3. Test webhook endpoint manually
4. Verify SSL certificate is valid (HTTPS required)

### Issue: Images not being deleted after rejection

**Solution:**
1. Check `auto_delete_rejected_images` setting in config
2. Verify Cloudinary API credentials have delete permissions
3. Check error logs for deletion failures

### Issue: Admin notifications not sending

**Solution:**
1. Verify `notify_admin_on_rejection` is enabled
2. Check admin email address in configuration
3. Verify mailer.php is configured correctly
4. Check email logs for delivery failures

## Security Considerations

1. **Webhook Security**: Verify webhook signatures from Cloudinary
2. **API Credentials**: Never commit `.env` file to version control
3. **Rate Limiting**: Implement rate limiting on webhook endpoint
4. **Input Validation**: Always validate and sanitize webhook payloads

## Cost Considerations

- AWS Rekognition charges per image analyzed
- Typical cost: ~$0.001 per image
- Monitor usage in Cloudinary dashboard
- Consider implementing daily/monthly limits

## Support

For issues or questions:
- Cloudinary Support: https://support.cloudinary.com
- AWS Rekognition Docs: https://docs.aws.amazon.com/rekognition/
- NeoCafe Internal: Contact development team

## Changelog

- **v1.0.0** (2025-11-02): Initial implementation
  - AWS Rekognition integration
  - Automatic rejection workflow
  - Admin notifications
  - Database logging
