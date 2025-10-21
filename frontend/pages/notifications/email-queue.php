<?php
/**
 * Email Queue System for NeoExclusiveCafe
 * Handles bulk email sending with queue management
 */

// Use flexible path resolution
$databasePath = __DIR__ . '/../../../backend/pages/admin-includes/database.php';
$mailerPath = __DIR__ . '/../../../backend/pages/admin-includes/mailer.php';

if (!file_exists($databasePath)) {
    // Try alternative path from root directory
    $databasePath = dirname(__DIR__, 3) . '/backend/pages/admin-includes/database.php';
    $mailerPath = dirname(__DIR__, 3) . '/backend/pages/admin-includes/mailer.php';
}

require_once $databasePath;
require_once $mailerPath;

class EmailQueue {
    private $db;
    private $batchSize = 10; // Number of emails to send per batch
    private $delayBetweenBatches = 2; // Seconds to wait between batches
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Add emails to the queue
     */
    public function addToQueue($emails) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_queue (recipient_email, subject, body, notification_type, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            
            $addedCount = 0;
            foreach ($emails as $email) {
                $stmt->bind_param("ssss", 
                    $email['email'], 
                    $email['subject'], 
                    $email['body'], 
                    $email['type']
                );
                
                if ($stmt->execute()) {
                    $addedCount++;
                }
            }
            
            $stmt->close();
            return $addedCount;
            
        } catch (Exception $e) {
            error_log("Error adding emails to queue: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process the email queue
     */
    public function processQueue($maxBatches = 5) {
        $processedCount = 0;
        $batchCount = 0;
        
        while ($batchCount < $maxBatches) {
            $emails = $this->getPendingEmails($this->batchSize);
            
            if (empty($emails)) {
                break; // No more emails to process
            }
            
            $batchProcessed = $this->processBatch($emails);
            $processedCount += $batchProcessed;
            $batchCount++;
            
            // Wait between batches to avoid overwhelming the SMTP server
            if ($batchCount < $maxBatches && !empty($emails)) {
                sleep($this->delayBetweenBatches);
            }
        }
        
        return $processedCount;
    }
    
    /**
     * Get pending emails from the queue
     */
    private function getPendingEmails($limit) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, recipient_email, subject, body, notification_type 
                FROM email_queue 
                WHERE status = 'pending' 
                ORDER BY created_at ASC 
                LIMIT ?
            ");
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $emails = [];
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row;
            }
            
            $stmt->close();
            return $emails;
            
        } catch (Exception $e) {
            error_log("Error getting pending emails: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process a batch of emails
     */
    private function processBatch($emails) {
        $successCount = 0;
        
        foreach ($emails as $email) {
            try {
                // Send the email
                $result = sendEmail($email['recipient_email'], $email['subject'], $email['body'], true);
                
                // Update queue status
                $status = $result ? 'sent' : 'failed';
                $this->updateEmailStatus($email['id'], $status);
                
                if ($result) {
                    $successCount++;
                }
                
            } catch (Exception $e) {
                error_log("Error processing email ID {$email['id']}: " . $e->getMessage());
                $this->updateEmailStatus($email['id'], 'failed');
            }
        }
        
        return $successCount;
    }
    
    /**
     * Update email status in the queue
     */
    private function updateEmailStatus($emailId, $status) {
        try {
            $stmt = $this->db->prepare("
                UPDATE email_queue 
                SET status = ?, processed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $status, $emailId);
            $stmt->execute();
            $stmt->close();
            
        } catch (Exception $e) {
            error_log("Error updating email status: " . $e->getMessage());
        }
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM email_queue 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY status
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stats = [
                'pending' => 0,
                'sent' => 0,
                'failed' => 0
            ];
            
            while ($row = $result->fetch_assoc()) {
                $stats[$row['status']] = $row['count'];
            }
            
            $stmt->close();
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting queue stats: " . $e->getMessage());
            return ['pending' => 0, 'sent' => 0, 'failed' => 0];
        }
    }
    
    /**
     * Clean up old processed emails (older than 7 days)
     */
    public function cleanupOldEmails() {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM email_queue 
                WHERE status IN ('sent', 'failed') 
                AND processed_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute();
            $deletedCount = $stmt->affected_rows;
            $stmt->close();
            
            return $deletedCount;
            
        } catch (Exception $e) {
            error_log("Error cleaning up old emails: " . $e->getMessage());
            return 0;
        }
    }
}

// Create email queue table if it doesn't exist
function createEmailQueueTable($db) {
    try {
        $sql = "
        CREATE TABLE IF NOT EXISTS email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_email VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            body TEXT NOT NULL,
            notification_type VARCHAR(50) DEFAULT 'promotion',
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->query($sql);
        return true;
        
    } catch (Exception $e) {
        error_log("Error creating email queue table: " . $e->getMessage());
        return false;
    }
}

// Initialize the email queue table if $db is available
if (isset($db)) {
    createEmailQueueTable($db);
}
?>
