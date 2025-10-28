# Design Document: Cloudinary Image Migration

## Overview

This design outlines the technical approach for migrating all local images to Cloudinary and updating the application to use cloud-based image storage. The implementation will be done in phases to ensure data integrity and minimize downtime.

## Architecture

### Phase 1: Setup & Configuration
1. Install Cloudinary PHP SDK
2. Configure environment variables
3. Create Cloudinary helper class

### Phase 2: Database Preparation
1. Add Cloudinary URL columns to relevant tables
2. Create image mapping table for tracking migration

### Phase 3: Migration Scripts
1. Product images migration
2. General assets migration
3. Payment/refund proofs migration

### Phase 4: Application Updates
1. Update image display logic
2. Update upload handlers
3. Add Cloudinary transformations

### Phase 5: Verification & Cleanup
1. Verify all images migrated
2. Update .gitignore
3. Document for team

## Components and Interfaces

### 1. Cloudinary Configuration

**File:** `config/cloudinary-config.php`

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Cloudinary;

class CloudinaryConfig {
    private static $instance = null;
    private $cloudinary;
    
    private function __construct() {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
                'api_key' => getenv('CLOUDINARY_API_KEY'),
                'api_secret' => getenv('CLOUDINARY_API_SECRET')
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getCloudinary() {
        return $this->cloudinary;
    }
}
```

### 2. Environment Variables

**File:** `.env` (create if doesn't exist)

```
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name
```

### 3. Migration Script

**File:** `scripts/migrate-images-to-cloudinary.php`

```php
<?php
require_once __DIR__ . '/../config/cloudinary-config.php';
require_once __DIR__ . '/../backend/pages/admin-includes/database.php';

class ImageMigration {
    private $cloudinary;
    private $conn;
    private $migrationLog = [];
    
    public function __construct() {
        $this->cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        $this->conn = $GLOBALS['conn']; // Use existing connection
    }
    
    public function migrateProductImages() {
        echo "Starting product images migration...\n";
        
        $sql = "SELECT id, name, image_path, additional_images FROM products WHERE deleted_at IS NULL";
        $result = $this->conn->query($sql);
        
        while ($product = $result->fetch_assoc()) {
            $this->migrateProductImage($product);
        }
    }
    
    private function migrateProductImage($product) {
        // Upload primary image
        if (!empty($product['image_path'])) {
            $localPath = __DIR__ . '/../' . $product['image_path'];
            if (file_exists($localPath)) {
                try {
                    $result = $this->cloudinary->uploadApi()->upload($localPath, [
                        'folder' => 'neocafe/products',
                        'public_id' => 'product_' . $product['id'] . '_primary',
                        'overwrite' => true,
                        'resource_type' => 'image'
                    ]);
                    
                    // Update database
                    $cloudinaryUrl = $result['secure_url'];
                    $updateSql = "UPDATE products SET cloudinary_url = ? WHERE id = ?";
                    $stmt = $this->conn->prepare($updateSql);
                    $stmt->bind_param("si", $cloudinaryUrl, $product['id']);
                    $stmt->execute();
                    
                    $this->log("SUCCESS: Migrated product {$product['id']} - {$product['name']}");
                } catch (Exception $e) {
                    $this->log("ERROR: Failed to migrate product {$product['id']}: " . $e->getMessage());
                }
            }
        }
    }
    
    public function migrateGeneralImages() {
        echo "Starting general images migration...\n";
        
        $imagesDir = __DIR__ . '/../assets/images/';
        $files = glob($imagesDir . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $this->uploadGeneralImage($file);
            }
        }
    }
    
    private function uploadGeneralImage($filePath) {
        $filename = basename($filePath);
        
        try {
            $result = $this->cloudinary->uploadApi()->upload($filePath, [
                'folder' => 'neocafe/assets',
                'public_id' => pathinfo($filename, PATHINFO_FILENAME),
                'overwrite' => true
            ]);
            
            $this->log("SUCCESS: Uploaded {$filename} to Cloudinary");
        } catch (Exception $e) {
            $this->log("ERROR: Failed to upload {$filename}: " . $e->getMessage());
        }
    }
    
    public function migratePaymentProofs() {
        echo "Starting payment proofs migration...\n";
        
        $dirs = [
            'bulk_payments' => __DIR__ . '/../assets/bulk_payments/',
            'refund_proofs' => __DIR__ . '/../assets/refund-proofs/'
        ];
        
        foreach ($dirs as $type => $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $this->uploadPaymentProof($file, $type);
                    }
                }
            }
        }
    }
    
    private function uploadPaymentProof($filePath, $type) {
        $filename = basename($filePath);
        
        try {
            $result = $this->cloudinary->uploadApi()->upload($filePath, [
                'folder' => "neocafe/{$type}",
                'public_id' => pathinfo($filename, PATHINFO_FILENAME),
                'overwrite' => true
            ]);
            
            $this->log("SUCCESS: Uploaded {$type}/{$filename} to Cloudinary");
        } catch (Exception $e) {
            $this->log("ERROR: Failed to upload {$type}/{$filename}: " . $e->getMessage());
        }
    }
    
    private function log($message) {
        $this->migrationLog[] = $message;
        echo $message . "\n";
    }
    
    public function generateReport() {
        $reportFile = __DIR__ . '/migration-report-' . date('Y-m-d-His') . '.txt';
        file_put_contents($reportFile, implode("\n", $this->migrationLog));
        echo "\nMigration report saved to: {$reportFile}\n";
    }
}

// Run migration
$migration = new ImageMigration();
$migration->migrateProductImages();
$migration->migrateGeneralImages();
$migration->migratePaymentProofs();
$migration->generateReport();
```

### 4. Database Schema Updates

```sql
-- Add Cloudinary URL columns
ALTER TABLE products ADD COLUMN cloudinary_url VARCHAR(500) AFTER image_path;
ALTER TABLE products ADD COLUMN cloudinary_additional_images TEXT AFTER additional_images;

-- Create image mapping table for tracking
CREATE TABLE IF NOT EXISTS image_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    local_path VARCHAR(500) NOT NULL,
    cloudinary_url VARCHAR(500) NOT NULL,
    cloudinary_public_id VARCHAR(255) NOT NULL,
    migration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed') DEFAULT 'success',
    error_message TEXT,
    INDEX idx_local_path (local_path),
    INDEX idx_cloudinary_public_id (cloudinary_public_id)
);
```

### 5. Helper Functions

**File:** `backend/includes/cloudinary-helper.php`

```php
<?php
require_once __DIR__ . '/../../config/cloudinary-config.php';

function uploadToCloudinary($filePath, $folder = 'neocafe', $publicId = null) {
    try {
        $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        
        $options = [
            'folder' => $folder,
            'overwrite' => true,
            'resource_type' => 'auto'
        ];
        
        if ($publicId) {
            $options['public_id'] = $publicId;
        }
        
        $result = $cloudinary->uploadApi()->upload($filePath, $options);
        
        return [
            'success' => true,
            'url' => $result['secure_url'],
            'public_id' => $result['public_id']
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function getCloudinaryUrl($publicId, $transformations = []) {
    $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
    return $cloudinary->image($publicId)->toUrl();
}

function deleteFromCloudinary($publicId) {
    try {
        $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
        $result = $cloudinary->uploadApi()->destroy($publicId);
        return $result['result'] === 'ok';
    } catch (Exception $e) {
        error_log("Failed to delete from Cloudinary: " . $e->getMessage());
        return false;
    }
}
```

## Implementation Plan

### Step 1: Install Cloudinary SDK
```bash
composer require cloudinary/cloudinary_php
```

### Step 2: Set Environment Variables
Create `.env` file or add to existing configuration

### Step 3: Run Database Migrations
Execute SQL to add Cloudinary columns

### Step 4: Run Migration Script
```bash
php scripts/migrate-images-to-cloudinary.php
```

### Step 5: Update Application Code
- Update product display pages
- Update upload handlers
- Update admin panels

### Step 6: Verify Migration
- Check all images load correctly
- Verify database URLs
- Test new uploads

### Step 7: Cleanup
- Remove local image files (after backup)
- Update .gitignore
- Document changes

## Error Handling

1. **Upload Failures**: Log and continue with next image
2. **Database Update Failures**: Rollback and retry
3. **Missing Files**: Log and skip
4. **API Rate Limits**: Implement retry logic with exponential backoff
5. **Network Errors**: Retry up to 3 times

## Testing Strategy

1. **Test with sample images first**
2. **Verify URLs are accessible**
3. **Check database updates**
4. **Test image transformations**
5. **Verify upload functionality**
6. **Test error scenarios**

## Performance Considerations

- Batch uploads in groups of 50
- Use async uploads where possible
- Implement progress tracking
- Add timeout handling
- Cache Cloudinary URLs

## Security Considerations

- Store API credentials in environment variables
- Use secure HTTPS URLs
- Implement access control for sensitive images
- Validate file types before upload
- Sanitize filenames
