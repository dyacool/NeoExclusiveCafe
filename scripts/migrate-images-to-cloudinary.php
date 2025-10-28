<?php
require_once __DIR__ . '/../config/cloudinary-config.php';
require_once __DIR__ . '/../backend/pages/admin-includes/database.php';
require_once __DIR__ . '/../backend/includes/cloudinary-helper.php';

class ImageMigration {
    private $cloudinary;
    private $conn;
    private $migrationLog = [];
    private $stats = [
        'product' => ['success' => 0, 'failed' => 0],
        'carousel' => ['success' => 0, 'failed' => 0],
        'payment' => ['success' => 0, 'failed' => 0],
        'refund' => ['success' => 0, 'failed' => 0],
        'general' => ['success' => 0, 'failed' => 0],
        'admin' => ['success' => 0, 'failed' => 0]
    ];
    
    public function __construct() {
        $this->cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        $this->conn = $GLOBALS['conn'];
    }
    
    /**
     * Migrate product images from product_images table
     */
    public function migrateProductImages() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "MIGRATING PRODUCT IMAGES\n";
        echo str_repeat("=", 80) . "\n\n";
        
        // Get all product images that haven't been migrated yet
        $sql = "SELECT pi.id, pi.product_id, pi.image_url, pi.is_primary, p.name as product_name
                FROM product_images pi
                JOIN products p ON pi.product_id = p.id
                WHERE (pi.cloud_url IS NULL OR pi.cloud_url = '')
                AND pi.is_removed = 0
                ORDER BY pi.product_id, pi.is_primary DESC";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            $this->log("ERROR: Failed to query product images: " . $this->conn->error, 'product');
            return;
        }
        
        $totalImages = $result->num_rows;
        echo "Found {$totalImages} product images to migrate\n\n";
        
        $current = 0;
        while ($image = $result->fetch_assoc()) {
            $current++;
            $this->migrateProductImage($image, $current, $totalImages);
        }
        
        echo "\n✅ Product images migration complete!\n";
        echo "Success: {$this->stats['product']['success']}, Failed: {$this->stats['product']['failed']}\n";
    }
    
    private function migrateProductImage($image, $current, $total) {
        $imageId = $image['id'];
        $productId = $image['product_id'];
        $imageUrl = $image['image_url'];
        $isPrimary = $image['is_primary'];
        $productName = $image['product_name'];
        
        echo "[{$current}/{$total}] Migrating: {$productName} - " . ($isPrimary ? 'PRIMARY' : 'ADDITIONAL') . "\n";
        
        // Construct local path - images are in assets folder
        $localPath = __DIR__ . '/../assets/' . $imageUrl;
        
        // Validate file
        $validation = validateImageFile($localPath);
        if (!$validation['valid']) {
            $this->log("SKIP: {$imageUrl} - {$validation['error']}", 'product');
            $this->stats['product']['failed']++;
            echo "  ⚠️  SKIPPED: {$validation['error']}\n";
            return;
        }
        
        // Generate public ID
        $filename = basename($imageUrl);
        $publicId = generatePublicId($filename, 'product_' . $productId);
        
        // Upload to Cloudinary
        $result = uploadToCloudinary($localPath, 'neocafe/products', $publicId);
        
        if ($result['success']) {
            // Update database
            $updateSql = "UPDATE product_images 
                         SET cloud_url = ?, 
                             cloud_public_id = ?, 
                             cloud_provider = 'cloudinary'
                         WHERE id = ?";
            
            $stmt = $this->conn->prepare($updateSql);
            if ($stmt) {
                $stmt->bind_param("ssi", $result['url'], $result['public_id'], $imageId);
                if ($stmt->execute()) {
                    $this->log("SUCCESS: Migrated {$imageUrl} -> {$result['url']}", 'product');
                    $this->stats['product']['success']++;
                    echo "  ✅ SUCCESS: {$result['url']}\n";
                    
                    // Log to migration table
                    logMigration($this->conn, $imageUrl, $result['url'], $result['public_id'], 'product', 'success');
                } else {
                    $this->log("ERROR: Failed to update database for {$imageUrl}", 'product');
                    $this->stats['product']['failed']++;
                    echo "  ❌ FAILED: Database update failed\n";
                }
                $stmt->close();
            }
        } else {
            $this->log("ERROR: Failed to upload {$imageUrl}: {$result['error']}", 'product');
            $this->stats['product']['failed']++;
            echo "  ❌ FAILED: {$result['error']}\n";
            
            // Log failure
            logMigration($this->conn, $imageUrl, '', '', 'product', 'failed', $result['error']);
        }
    }
    
    /**
     * Migrate carousel images
     */
    public function migrateCarouselImages() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "MIGRATING CAROUSEL IMAGES\n";
        echo str_repeat("=", 80) . "\n\n";
        
        $sql = "SELECT id, image_url, title
                FROM carousel_images
                WHERE (cloud_url IS NULL OR cloud_url = '')
                AND is_active = 1
                ORDER BY display_order";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            $this->log("ERROR: Failed to query carousel images: " . $this->conn->error, 'carousel');
            return;
        }
        
        $totalImages = $result->num_rows;
        echo "Found {$totalImages} carousel images to migrate\n\n";
        
        $current = 0;
        while ($image = $result->fetch_assoc()) {
            $current++;
            $this->migrateCarouselImage($image, $current, $totalImages);
        }
        
        echo "\n✅ Carousel images migration complete!\n";
        echo "Success: {$this->stats['carousel']['success']}, Failed: {$this->stats['carousel']['failed']}\n";
    }
    
    private function migrateCarouselImage($image, $current, $total) {
        $imageId = $image['id'];
        $imageUrl = $image['image_url'];
        $title = $image['title'];
        
        echo "[{$current}/{$total}] Migrating carousel: {$title}\n";
        
        // Construct local path - images are in assets folder
        $localPath = __DIR__ . '/../assets/' . $imageUrl;
        
        $validation = validateImageFile($localPath);
        if (!$validation['valid']) {
            $this->log("SKIP: {$imageUrl} - {$validation['error']}", 'carousel');
            $this->stats['carousel']['failed']++;
            echo "  ⚠️  SKIPPED: {$validation['error']}\n";
            return;
        }
        
        $filename = basename($imageUrl);
        $publicId = generatePublicId($filename, 'carousel');
        
        $result = uploadToCloudinary($localPath, 'neocafe/carousel', $publicId);
        
        if ($result['success']) {
            $updateSql = "UPDATE carousel_images 
                         SET cloud_url = ?, 
                             cloud_public_id = ?, 
                             cloud_provider = 'cloudinary'
                         WHERE id = ?";
            
            $stmt = $this->conn->prepare($updateSql);
            if ($stmt) {
                $stmt->bind_param("ssi", $result['url'], $result['public_id'], $imageId);
                if ($stmt->execute()) {
                    $this->log("SUCCESS: Migrated {$imageUrl} -> {$result['url']}", 'carousel');
                    $this->stats['carousel']['success']++;
                    echo "  ✅ SUCCESS: {$result['url']}\n";
                    
                    logMigration($this->conn, $imageUrl, $result['url'], $result['public_id'], 'carousel', 'success');
                } else {
                    $this->log("ERROR: Failed to update database for {$imageUrl}", 'carousel');
                    $this->stats['carousel']['failed']++;
                    echo "  ❌ FAILED: Database update failed\n";
                }
                $stmt->close();
            }
        } else {
            $this->log("ERROR: Failed to upload {$imageUrl}: {$result['error']}", 'carousel');
            $this->stats['carousel']['failed']++;
            echo "  ❌ FAILED: {$result['error']}\n";
            
            logMigration($this->conn, $imageUrl, '', '', 'carousel', 'failed', $result['error']);
        }
    }
    
    /**
     * Migrate general images from assets/images directory
     */
    public function migrateGeneralImages() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "MIGRATING GENERAL IMAGES\n";
        echo str_repeat("=", 80) . "\n\n";
        
        $imagesDir = __DIR__ . '/../assets/images/';
        
        if (!is_dir($imagesDir)) {
            echo "⚠️  Directory not found: {$imagesDir}\n";
            return;
        }
        
        $files = glob($imagesDir . '*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
        $totalImages = count($files);
        
        echo "Found {$totalImages} general images to migrate\n\n";
        
        $current = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                $current++;
                $this->migrateGeneralImage($file, $current, $totalImages);
            }
        }
        
        echo "\n✅ General images migration complete!\n";
        echo "Success: {$this->stats['general']['success']}, Failed: {$this->stats['general']['failed']}\n";
    }
    
    private function migrateGeneralImage($filePath, $current, $total) {
        $filename = basename($filePath);
        
        echo "[{$current}/{$total}] Migrating: {$filename}\n";
        
        $validation = validateImageFile($filePath);
        if (!$validation['valid']) {
            $this->log("SKIP: {$filename} - {$validation['error']}", 'general');
            $this->stats['general']['failed']++;
            echo "  ⚠️  SKIPPED: {$validation['error']}\n";
            return;
        }
        
        $publicId = generatePublicId($filename, 'general');
        $result = uploadToCloudinary($filePath, 'neocafe/assets', $publicId);
        
        if ($result['success']) {
            $this->log("SUCCESS: Uploaded {$filename} -> {$result['url']}", 'general');
            $this->stats['general']['success']++;
            echo "  ✅ SUCCESS: {$result['url']}\n";
            
            $relativePath = 'assets/images/' . $filename;
            logMigration($this->conn, $relativePath, $result['url'], $result['public_id'], 'general', 'success');
        } else {
            $this->log("ERROR: Failed to upload {$filename}: {$result['error']}", 'general');
            $this->stats['general']['failed']++;
            echo "  ❌ FAILED: {$result['error']}\n";
            
            $relativePath = 'assets/images/' . $filename;
            logMigration($this->conn, $relativePath, '', '', 'general', 'failed', $result['error']);
        }
    }
    
    /**
     * Migrate payment and refund proof images
     */
    public function migratePaymentProofs() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "MIGRATING PAYMENT & REFUND PROOFS\n";
        echo str_repeat("=", 80) . "\n\n";
        
        $dirs = [
            'payment' => __DIR__ . '/../assets/bulk_payments/',
            'refund' => __DIR__ . '/../assets/refund-proofs/'
        ];
        
        foreach ($dirs as $type => $dir) {
            if (!is_dir($dir)) {
                echo "⚠️  Directory not found: {$dir}\n";
                continue;
            }
            
            $files = glob($dir . '*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
            $totalImages = count($files);
            
            echo "\nFound {$totalImages} {$type} proof images to migrate\n\n";
            
            $current = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    $current++;
                    $this->migratePaymentProof($file, $type, $current, $totalImages);
                }
            }
        }
        
        echo "\n✅ Payment proofs migration complete!\n";
        echo "Payment Success: {$this->stats['payment']['success']}, Failed: {$this->stats['payment']['failed']}\n";
        echo "Refund Success: {$this->stats['refund']['success']}, Failed: {$this->stats['refund']['failed']}\n";
    }
    
    private function migratePaymentProof($filePath, $type, $current, $total) {
        $filename = basename($filePath);
        
        echo "[{$current}/{$total}] Migrating {$type} proof: {$filename}\n";
        
        $validation = validateImageFile($filePath);
        if (!$validation['valid']) {
            $this->log("SKIP: {$filename} - {$validation['error']}", $type);
            $this->stats[$type]['failed']++;
            echo "  ⚠️  SKIPPED: {$validation['error']}\n";
            return;
        }
        
        $publicId = generatePublicId($filename, $type);
        $folder = $type === 'payment' ? 'neocafe/bulk_payments' : 'neocafe/refund_proofs';
        
        $result = uploadToCloudinary($filePath, $folder, $publicId);
        
        if ($result['success']) {
            $this->log("SUCCESS: Uploaded {$filename} -> {$result['url']}", $type);
            $this->stats[$type]['success']++;
            echo "  ✅ SUCCESS: {$result['url']}\n";
            
            $relativePath = ($type === 'payment' ? 'assets/bulk_payments/' : 'assets/refund-proofs/') . $filename;
            logMigration($this->conn, $relativePath, $result['url'], $result['public_id'], $type, 'success');
        } else {
            $this->log("ERROR: Failed to upload {$filename}: {$result['error']}", $type);
            $this->stats[$type]['failed']++;
            echo "  ❌ FAILED: {$result['error']}\n";
            
            $relativePath = ($type === 'payment' ? 'assets/bulk_payments/' : 'assets/refund-proofs/') . $filename;
            logMigration($this->conn, $relativePath, '', '', $type, 'failed', $result['error']);
        }
    }
    
    private function log($message, $type = 'general') {
        $timestamp = date('Y-m-d H:i:s');
        $this->migrationLog[] = "[{$timestamp}] [{$type}] {$message}";
    }
    
    public function generateReport() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "MIGRATION SUMMARY\n";
        echo str_repeat("=", 80) . "\n\n";
        
        $totalSuccess = 0;
        $totalFailed = 0;
        
        foreach ($this->stats as $type => $counts) {
            $total = $counts['success'] + $counts['failed'];
            if ($total > 0) {
                echo ucfirst($type) . " Images:\n";
                echo "  Total: {$total}\n";
                echo "  ✅ Success: {$counts['success']}\n";
                echo "  ❌ Failed: {$counts['failed']}\n\n";
                
                $totalSuccess += $counts['success'];
                $totalFailed += $counts['failed'];
            }
        }
        
        echo str_repeat("-", 80) . "\n";
        echo "OVERALL TOTALS:\n";
        echo "  Total Images: " . ($totalSuccess + $totalFailed) . "\n";
        echo "  ✅ Successfully Migrated: {$totalSuccess}\n";
        echo "  ❌ Failed: {$totalFailed}\n";
        echo str_repeat("=", 80) . "\n\n";
        
        // Save detailed log
        $reportFile = __DIR__ . '/migration-report-' . date('Y-m-d-His') . '.txt';
        file_put_contents($reportFile, implode("\n", $this->migrationLog));
        echo "📄 Detailed log saved to: {$reportFile}\n\n";
    }
}

// Main execution
echo "\n";
echo str_repeat("=", 80) . "\n";
echo "CLOUDINARY IMAGE MIGRATION\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n";

try {
    $migration = new ImageMigration();
    
    // Run migrations
    $migration->migrateProductImages();
    $migration->migrateCarouselImages();
    $migration->migrateGeneralImages();
    $migration->migratePaymentProofs();
    
    // Generate report
    $migration->generateReport();
    
    echo "✅ Migration completed successfully!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
