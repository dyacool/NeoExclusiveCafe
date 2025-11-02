<?php
/**
 * Cloudinary Moderation Helper
 * 
 * Helper class for managing Cloudinary content moderation functionality
 * 
 * @package NeoCafe
 * @version 1.0.0
 */

class CloudinaryModerationHelper {
    
    private $config;
    private $conn;
    
    /**
     * Constructor
     * 
     * @param mysqli $conn Database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadConfig();
    }
    
    /**
     * Load moderation configuration
     */
    private function loadConfig() {
        $configPath = __DIR__ . '/../../config/cloudinary-moderation-config.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            throw new Exception("Cloudinary moderation configuration file not found");
        }
    }
    
    /**
     * Check if moderation is enabled
     * 
     * @return bool
     */
    public function isModerationEnabled() {
        return $this->config['moderation']['enabled'] ?? false;
    }
    
    /**
     * Get moderation provider
     * 
     * @return string
     */
    public function getModerationProvider() {
        return $this->config['moderation']['provider'] ?? 'aws_rek';
    }
    
    /**
     * Get auto-reject threshold
     * 
     * @return float
     */
    public function getAutoRejectThreshold() {
        return $this->config['moderation']['auto_reject_threshold'] ?? 0.8;
    }
    
    /**
     * Get webhook URL
     * 
     * @return string
     */
    public function getWebhookUrl() {
        return $this->config['moderation']['webhook_url'] ?? '';
    }
    
    /**
     * Check if a category is enabled for detection
     * 
     * @param string $category Category name
     * @return bool
     */
    public function isCategoryEnabled($category) {
        return $this->config['moderation']['categories'][$category] ?? false;
    }
    
    /**
     * Get error message for a specific rejection reason
     * 
     * @param string $reason Rejection reason
     * @return string
     */
    public function getErrorMessage($reason) {
        return $this->config['error_messages'][$reason] ?? $this->config['error_messages']['default'];
    }
    
    /**
     * Log moderation result to database
     * 
     * @param string $publicId Cloudinary public ID
     * @param string $status Moderation status (approved, rejected, pending)
     * @param string $kind Moderation provider kind
     * @param array $responseData Full moderation response
     * @return bool Success status
     */
    public function logModerationResult($publicId, $status, $kind, $responseData) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO image_moderation_log (public_id, status, kind, response_data, created_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            
            $responseJson = json_encode($responseData);
            $stmt->bind_param("ssss", $publicId, $status, $kind, $responseJson);
            
            $result = $stmt->execute();
            $stmt->close();
            
            // Also log to file if enabled
            if ($this->config['moderation']['log_all_moderations']) {
                $this->logToFile($publicId, $status, $kind, $responseData);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to log moderation result: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log moderation event to file
     * 
     * @param string $publicId Cloudinary public ID
     * @param string $status Moderation status
     * @param string $kind Moderation provider
     * @param array $responseData Response data
     */
    private function logToFile($publicId, $status, $kind, $responseData) {
        $logFile = $this->config['moderation']['log_file_path'];
        $logDir = dirname($logFile);
        
        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] Public ID: %s | Status: %s | Provider: %s | Data: %s\n",
            $timestamp,
            $publicId,
            $status,
            $kind,
            json_encode($responseData)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Get moderation status for a public ID
     * 
     * @param string $publicId Cloudinary public ID
     * @return array|null Moderation data or null if not found
     */
    public function getModerationStatus($publicId) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT status, kind, response_data, created_at 
                 FROM image_moderation_log 
                 WHERE public_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 1"
            );
            
            $stmt->bind_param("s", $publicId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $row['response_data'] = json_decode($row['response_data'], true);
                $stmt->close();
                return $row;
            }
            
            $stmt->close();
            return null;
        } catch (Exception $e) {
            error_log("Failed to get moderation status: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update temp_uploaded_images with moderation status
     * 
     * @param string $publicId Cloudinary public ID
     * @param string $status Moderation status
     * @return bool Success status
     */
    public function updateTempImageStatus($publicId, $status) {
        try {
            $stmt = $this->conn->prepare(
                "UPDATE temp_uploaded_images 
                 SET moderation_status = ?, moderation_checked_at = NOW() 
                 WHERE public_id = ?"
            );
            
            $stmt->bind_param("ss", $status, $publicId);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to update temp image status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if admin notification should be sent
     * 
     * @return bool
     */
    public function shouldNotifyAdmin() {
        return $this->config['moderation']['notify_admin_on_rejection'] ?? false;
    }
    
    /**
     * Get admin email for notifications
     * 
     * @return string
     */
    public function getAdminEmail() {
        return $this->config['moderation']['admin_email'] ?? '';
    }
    
    /**
     * Get admin notification subject
     * 
     * @return string
     */
    public function getAdminNotificationSubject() {
        return $this->config['moderation']['admin_notification_subject'] ?? 'Image Rejected by Content Moderation';
    }
    
    /**
     * Check if rejected images should be auto-deleted
     * 
     * @return bool
     */
    public function shouldAutoDeleteRejected() {
        return $this->config['moderation']['auto_delete_rejected_images'] ?? true;
    }
    
    /**
     * Check if in test mode
     * 
     * @return bool
     */
    public function isTestMode() {
        return $this->config['moderation']['test_mode'] ?? false;
    }
    
    /**
     * Cleanup old moderation logs
     * 
     * @return int Number of deleted records
     */
    public function cleanupOldLogs() {
        $days = $this->config['moderation']['cleanup_old_logs_days'] ?? 30;
        
        try {
            $stmt = $this->conn->prepare(
                "DELETE FROM image_moderation_log 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            return $affected;
        } catch (Exception $e) {
            error_log("Failed to cleanup old logs: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get moderation statistics
     * 
     * @param int $days Number of days to look back
     * @return array Statistics data
     */
    public function getModerationStats($days = 30) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT 
                    status,
                    COUNT(*) as count,
                    DATE(created_at) as date
                 FROM image_moderation_log 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY status, DATE(created_at)
                 ORDER BY date DESC"
            );
            
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stats = [];
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
            
            $stmt->close();
            return $stats;
        } catch (Exception $e) {
            error_log("Failed to get moderation stats: " . $e->getMessage());
            return [];
        }
    }
}
