<?php
/**
 * Migration: Add Cloudinary support to pod_orders table
 * Purpose: Store Cloudinary public_id for delivery proof images
 * Date: 2025-11-02
 */

require_once __DIR__ . '/../../../backend/pages/admin-includes/database.php';

echo "Starting migration: Add Cloudinary support to pod_orders table...\n";

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Check if cloudinary_public_id column already exists
    $check_sql = "SHOW COLUMNS FROM `pod_orders` LIKE 'cloudinary_public_id'";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) == 0) {
        echo "Adding cloudinary_public_id column...\n";
        
        // Add cloudinary_public_id column
        $alter_sql = "ALTER TABLE `pod_orders` 
                      ADD COLUMN `cloudinary_public_id` VARCHAR(255) NULL 
                      COMMENT 'Cloudinary public ID for the proof image' 
                      AFTER `proof_image_path`";
        
        if (!mysqli_query($conn, $alter_sql)) {
            throw new Exception("Failed to add cloudinary_public_id column: " . mysqli_error($conn));
        }
        
        echo "✓ cloudinary_public_id column added successfully\n";
        
        // Add index for cloudinary_public_id
        echo "Adding index for cloudinary_public_id...\n";
        $index_sql = "ALTER TABLE `pod_orders` 
                      ADD INDEX `idx_cloudinary_public_id` (`cloudinary_public_id`)";
        
        if (!mysqli_query($conn, $index_sql)) {
            throw new Exception("Failed to add index: " . mysqli_error($conn));
        }
        
        echo "✓ Index added successfully\n";
        
        // Update proof_image_path column to allow longer URLs
        echo "Updating proof_image_path column...\n";
        $modify_sql = "ALTER TABLE `pod_orders` 
                       MODIFY COLUMN `proof_image_path` VARCHAR(500) NOT NULL 
                       COMMENT 'Cloudinary URL or relative path to proof image'";
        
        if (!mysqli_query($conn, $modify_sql)) {
            throw new Exception("Failed to modify proof_image_path column: " . mysqli_error($conn));
        }
        
        echo "✓ proof_image_path column updated successfully\n";
        
    } else {
        echo "⚠ cloudinary_public_id column already exists, skipping...\n";
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo "\n✅ Migration completed successfully!\n";
    echo "The pod_orders table now supports Cloudinary image storage.\n";
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

mysqli_close($conn);
?>
