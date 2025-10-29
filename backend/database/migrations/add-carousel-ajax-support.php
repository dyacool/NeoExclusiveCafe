<?php
/**
 * Database Migration: Add Carousel AJAX Image Management Support
 * 
 * This migration:
 * 1. Adds Cloudinary-specific columns to carousel_images table
 * 2. Makes image_url column nullable
 * 3. Adds necessary indexes for performance
 * 4. Verifies temp_uploaded_images table exists
 */

require_once __DIR__ . '/../../pages/admin-includes/database.php';

echo "=== Database Migration: Carousel AJAX Image Management Support ===\n\n";

// Track success/failure
$success = true;
$errors = [];

// Step 1: Add Cloudinary columns to carousel_images table
echo "Step 1: Adding Cloudinary columns to carousel_images table...\n";

// Check if columns already exist
$result = $conn->query("DESCRIBE carousel_images");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}

try {
    // Check if cloud_public_id exists (it's already there based on your schema)
    if (!in_array('cloud_public_id', $existingColumns)) {
        $sql = "ALTER TABLE carousel_images ADD COLUMN cloud_public_id VARCHAR(255) NULL AFTER image_url";
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
        $sql = "ALTER TABLE carousel_images ADD COLUMN cloud_provider VARCHAR(50) DEFAULT 'cloudinary' AFTER cloud_public_id";
        if ($conn->query($sql)) {
            echo "✓ cloud_provider column added\n";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "✓ cloud_provider column already exists\n";
    }
    
    // Check if cloud_url exists
    if (!in_array('cloud_url', $existingColumns)) {
        $sql = "ALTER TABLE carousel_images ADD COLUMN cloud_url TEXT NULL AFTER cloud_provider";
        if ($conn->query($sql)) {
            echo "✓ cloud_url column added\n";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "✓ cloud_url column already exists\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    $errors[] = "Failed to add Cloudinary columns: " . $e->getMessage();
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
    $success = false;
}

// Step 2: Make image_url nullable
echo "Step 2: Making image_url column nullable...\n";
try {
    $sql = "ALTER TABLE carousel_images MODIFY COLUMN image_url VARCHAR(255) NULL";
    if ($conn->query($sql)) {
        echo "✓ image_url column is now nullable\n\n";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $errors[] = "Failed to modify image_url column: " . $e->getMessage();
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
    $success = false;
}

// Step 3: Add indexes for performance
echo "Step 3: Adding performance indexes...\n";

// Check existing indexes
$result = $conn->query("SHOW INDEX FROM carousel_images");
$existingIndexes = [];
while ($row = $result->fetch_assoc()) {
    $existingIndexes[] = $row['Key_name'];
}

try {
    // Add cloud_public_id index if it doesn't exist
    if (!in_array('idx_cloud_public_id', $existingIndexes)) {
        $sql = "CREATE INDEX idx_cloud_public_id ON carousel_images(cloud_public_id)";
        if ($conn->query($sql)) {
            echo "✓ idx_cloud_public_id index created\n";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "✓ idx_cloud_public_id index already exists\n";
    }
    
    // Add display_order index if it doesn't exist
    if (!in_array('idx_display_order', $existingIndexes)) {
        $sql = "CREATE INDEX idx_display_order ON carousel_images(display_order)";
        if ($conn->query($sql)) {
            echo "✓ idx_display_order index created\n";
        } else {
            throw new Exception($conn->error);
        }
    } else {
        echo "✓ idx_display_order index already exists\n";
    }
    
    echo "\n";
} catch (Exception $e) {
    $errors[] = "Failed to create indexes: " . $e->getMessage();
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
    $success = false;
}

// Step 4: Verify temp_uploaded_images table exists
echo "Step 4: Verifying temp_uploaded_images table exists...\n";
try {
    $result = $conn->query("SHOW TABLES LIKE 'temp_uploaded_images'");
    if ($result->num_rows > 0) {
        echo "✓ temp_uploaded_images table exists (reused from product images)\n\n";
    } else {
        echo "✗ temp_uploaded_images table does not exist\n";
        echo "  Please run the product image migration first (add-ajax-image-support.php)\n\n";
        $success = false;
    }
} catch (Exception $e) {
    echo "✗ Failed to verify temp_uploaded_images table: " . $e->getMessage() . "\n\n";
    $success = false;
}

// Step 5: Verify the changes
echo "Step 5: Verifying database changes...\n";

// Check carousel_images table structure
try {
    $result = $conn->query("DESCRIBE carousel_images");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    
    if (isset($columns['image_url']) && $columns['image_url']['Null'] === 'YES') {
        echo "✓ image_url column is nullable\n";
    } else {
        echo "✗ image_url column is NOT nullable\n";
        $success = false;
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
    echo "✗ Failed to verify carousel_images table: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";

// Check indexes
try {
    $result = $conn->query("SHOW INDEX FROM carousel_images");
    $indexes = [];
    while ($row = $result->fetch_assoc()) {
        $indexes[] = $row['Key_name'];
    }
    
    if (in_array('idx_cloud_public_id', $indexes)) {
        echo "✓ Index 'idx_cloud_public_id' exists\n";
    } else {
        echo "✗ Index 'idx_cloud_public_id' missing\n";
    }
    
    if (in_array('idx_display_order', $indexes)) {
        echo "✓ Index 'idx_display_order' exists\n";
    } else {
        echo "✗ Index 'idx_display_order' missing\n";
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
    echo "\nDatabase is ready for carousel AJAX image management.\n";
} else {
    echo "✗ Migration completed with errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\nPlease fix the errors and run the migration again.\n";
}

$conn->close();
?>
