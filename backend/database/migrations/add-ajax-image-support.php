<?php
/**
 * Database Migration: Add AJAX Image Management Support
 * 
 * This migration:
 * 1. Makes image_url column nullable in product_images table
 * 2. Creates temp_uploaded_images table for orphan tracking
 * 3. Adds necessary indexes for performance
 */

require_once __DIR__ . '/../../pages/admin-includes/database.php';

echo "=== Database Migration: AJAX Image Management Support ===\n\n";

// Track success/failure
$success = true;
$errors = [];

// Step 1: Make image_url nullable
echo "Step 1: Making image_url column nullable...\n";
try {
    $sql = "ALTER TABLE product_images MODIFY COLUMN image_url VARCHAR(255) NULL";
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

// Step 2: Create temp_uploaded_images table
echo "Step 2: Creating temp_uploaded_images table...\n";
try {
    $sql = "CREATE TABLE IF NOT EXISTS temp_uploaded_images (
        id INT PRIMARY KEY AUTO_INCREMENT,
        public_id VARCHAR(255) NOT NULL UNIQUE,
        cloud_url TEXT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_uploaded_at (uploaded_at),
        INDEX idx_public_id (public_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql)) {
        echo "✓ temp_uploaded_images table created successfully\n\n";
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $errors[] = "Failed to create temp_uploaded_images table: " . $e->getMessage();
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
    $success = false;
}

// Step 3: Verify the changes
echo "Step 3: Verifying database changes...\n";

// Check product_images table structure
try {
    $result = $conn->query("DESCRIBE product_images");
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
    echo "✗ Failed to verify product_images table: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";

// Check temp_uploaded_images table
try {
    $result = $conn->query("SHOW TABLES LIKE 'temp_uploaded_images'");
    if ($result->num_rows > 0) {
        echo "✓ temp_uploaded_images table exists\n";
        
        // Check table structure
        $result = $conn->query("DESCRIBE temp_uploaded_images");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }
        
        $requiredColumns = ['id', 'public_id', 'cloud_url', 'uploaded_at'];
        foreach ($requiredColumns as $col) {
            if (isset($columns[$col])) {
                echo "  ✓ Column '$col' exists\n";
            } else {
                echo "  ✗ Column '$col' missing\n";
                $success = false;
            }
        }
        
        // Check indexes
        $result = $conn->query("SHOW INDEX FROM temp_uploaded_images");
        $indexes = [];
        while ($row = $result->fetch_assoc()) {
            $indexes[] = $row['Key_name'];
        }
        
        if (in_array('idx_uploaded_at', $indexes)) {
            echo "  ✓ Index 'idx_uploaded_at' exists\n";
        } else {
            echo "  ✗ Index 'idx_uploaded_at' missing\n";
        }
        
        if (in_array('idx_public_id', $indexes)) {
            echo "  ✓ Index 'idx_public_id' exists\n";
        } else {
            echo "  ✗ Index 'idx_public_id' missing\n";
        }
    } else {
        echo "✗ temp_uploaded_images table does not exist\n";
        $success = false;
    }
} catch (Exception $e) {
    echo "✗ Failed to verify temp_uploaded_images table: " . $e->getMessage() . "\n";
    $success = false;
}

echo "\n";

// Final summary
echo "=== Migration Summary ===\n";
if ($success) {
    echo "✓ Migration completed successfully!\n";
    echo "\nDatabase is ready for AJAX image management.\n";
} else {
    echo "✗ Migration completed with errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\nPlease fix the errors and run the migration again.\n";
}

$conn->close();
?>
