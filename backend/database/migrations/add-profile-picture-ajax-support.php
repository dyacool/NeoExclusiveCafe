<?php
/**
 * Database Migration: Add Profile Picture AJAX Image Management Support
 * 
 * This migration:
 * 1. Adds Cloudinary-specific columns to users table
 * 2. Adds necessary indexes for performance
 * 3. Verifies temp_uploaded_images table exists
 */

require_once __DIR__ . '/../../pages/admin-includes/database.php';

echo "=== Database Migration: Profile Picture AJAX Image Management Support ===\n\n";

// Track success/failure
$success = true;
$errors = [];

// Step 1: Add Cloudinary columns to users table
echo "Step 1: Adding Cloudinary columns to users table...\n";

// Check if columns already exist
$result = $conn->query("DESCRIBE users");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}

try {
    // Check if cloud_url exists
    if (!in_array('cloud_url', $existingColumns)) {
        $sql = "ALTER TABLE users ADD COLUMN cloud_url TEXT NULL AFTER profile_image";
        if ($conn->query($sql)) {
            echo "✓ cloud_url column added\n";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "✓ cloud_url column already exists\n";
    }
    
    // Check if cloud_public_id exists
    if (!in_array('cloud_public_id', $existingColumns)) {
        $sql = "ALTER TABLE users ADD COLUMN cloud_public_id VARCHAR(255) NULL AFTER cloud_url";
        if ($conn->query($sql)) {
            echo "✓ cloud_public_id column added\n";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "✓ cloud_public_id column already exists\n";
    }
    
    // Check if cloud_provider exists
    if (!in_array('cloud_provider', $existingColumns)) {
        $sql = "ALTER TABLE users ADD COLUMN cloud_provider VARCHAR(50) DEFAULT 'cloudinary' AFTER cloud_public_id";
        if ($conn->query($sql)) {
            echo "✓ cloud_provider column added\n";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "✓ cloud_provider column already exists\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    $errors[] = "Failed to add Cloudinary columns: " . $e->getMessage();
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
    $success = false;
}

// Step 2: Add indexes for performance
echo "Step 2: Adding performance indexes...\n";

// Check existing indexes
$result = $conn->query("SHOW INDEX FROM users");
$existingIndexes = [];
while ($row = $result->fetch_assoc()) {
    $existingIndexes[] = $row['Key_name'];
}

try {
    // Add cloud_public_id index if it doesn't exist
    if (!in_array('idx_cloud_public_id', $existingIndexes)) {
        $sql = "CREATE INDEX idx_cloud_public_id ON users(cloud_public_id)";
        if ($conn->query($sql)) {
            echo "✓ idx_cloud_public_id index created\n";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "✓ idx_cloud_public_id index already exists\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    $errors[] = "Failed to create indexes: " . $e->getMessage();
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
    $success = false;
}

// Step 3: Verify temp_uploaded_images table exists
echo "Step 3: Verifying temp_uploaded_images table exists...\n";
try {
    $result = $conn->query("SHOW TABLES LIKE 'temp_uploaded_images'");
    if ($result->num_rows > 0) {
        echo "✓ temp_uploaded_images table exists (reused from product/carousel images)\n\n";
    } else {
        echo "✗ temp_uploaded_images table does not exist\n";
        echo "  Please run the product image migration first (add-ajax-image-support.php)\n\n";
        $success = false;
    }
} catch (Exception $e) {
    echo "✗ Failed to verify temp_uploaded_images table: " . $e->getMessage() . "\n\n";
    $success = false;
}

// Step 4: Verify the changes
echo "Step 4: Verifying database changes...\n";

// Check users table structure
try {
    $result = $conn->query("DESCRIBE users");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    
    if (isset($columns['cloud_url'])) {
        echo "✓ cloud_url column exists\n";
    } else {
        echo "✗ cloud_url column missing\n";
        $success = false;
    }
    
    if (isset($columns['cloud_public_id'])) {
        echo "✓ cloud_public_id column exists\n";
    } else {
        echo "✗ cloud_public_id column missing\n";
        $success = false;
    }
    
    if (isset($columns['cloud_provider'])) {
        echo "✓ cloud_provider column exists\n";
    } else {
        echo "✗ cloud_provider column missing\n";
        $success = false;
    }
} catch (Exception $e) {
    echo "✗ Failed to verify users table: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";

// Check indexes
try {
    $result = $conn->query("SHOW INDEX FROM users");
    $indexes = [];
    while ($row = $result->fetch_assoc()) {
        $indexes[] = $row['Key_name'];
    }
    
    if (in_array('idx_cloud_public_id', $indexes)) {
        echo "✓ Index 'idx_cloud_public_id' exists\n";
    } else {
        echo "✗ Index 'idx_cloud_public_id' missing\n";
    }
} catch (Exception $e) {
    echo "✗ Failed to verify indexes: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";

// Final summary
echo "=== Migration Summary ===\n";
if ($success) {
    echo "✓ Migration completed successfully!\n";
    echo "\nDatabase is ready for profile picture AJAX image management.\n";
    echo "\nNext steps:\n";
    echo "  1. Admin profile pictures will be stored in: Home/assets/public/admin-profile-images/\n";
    echo "  2. Customer profile pictures will be stored in: Home/assets/public/profile-images/\n";
    echo "  3. Profile pictures will use COALESCE(cloud_url, profile_image) for display\n";
} else {
    echo "✗ Migration completed with errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\nPlease fix the errors and run the migration again.\n";
}

$conn->close();
?>
