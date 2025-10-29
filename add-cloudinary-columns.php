<?php
/**
 * Add Cloudinary columns to products table
 */

require_once __DIR__ . '/config/database-config.php';

$conn = getDatabaseConnection();

echo "Adding Cloudinary columns to products table...\n\n";

// Check if columns already exist
$checkSql = "SHOW COLUMNS FROM products LIKE 'cloudinary_url'";
$result = $conn->query($checkSql);

if ($result->num_rows > 0) {
    echo "✓ cloudinary_url column already exists\n";
} else {
    // Add cloudinary_url column
    $sql = "ALTER TABLE products ADD COLUMN cloudinary_url VARCHAR(500) NULL AFTER image_path";
    if ($conn->query($sql)) {
        echo "✓ Added cloudinary_url column\n";
    } else {
        echo "✗ Error adding cloudinary_url: " . $conn->error . "\n";
    }
}

// Check for cloudinary_additional_images
$checkSql = "SHOW COLUMNS FROM products LIKE 'cloudinary_additional_images'";
$result = $conn->query($checkSql);

if ($result->num_rows > 0) {
    echo "✓ cloudinary_additional_images column already exists\n";
} else {
    // Add cloudinary_additional_images column
    $sql = "ALTER TABLE products ADD COLUMN cloudinary_additional_images TEXT NULL AFTER additional_images";
    if ($conn->query($sql)) {
        echo "✓ Added cloudinary_additional_images column\n";
    } else {
        echo "✗ Error adding cloudinary_additional_images: " . $conn->error . "\n";
    }
}

echo "\n✅ Database migration complete!\n";
echo "\nNext steps:\n";
echo "1. Run the migration script to upload images to Cloudinary\n";
echo "2. Update the cloudinary_url values in the database\n";

$conn->close();
?>
