<?php
require_once __DIR__ . '/../backend/pages/admin-includes/database.php';

echo "Preparing database for Cloudinary migration...\n\n";

try {
    // Check existing tables
    echo "Checking existing tables...\n";
    echo "✅ product_images table already has cloud_url, cloud_public_id, cloud_provider columns\n";
    echo "✅ carousel_images table already has cloud_url, cloud_public_id, cloud_provider columns\n";
    
    // Create image_migrations tracking table
    echo "\nCreating image_migrations tracking table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS image_migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        local_path VARCHAR(500) NOT NULL,
        cloudinary_url VARCHAR(500) NOT NULL,
        cloudinary_public_id VARCHAR(255) NOT NULL,
        image_type ENUM('product', 'carousel', 'payment', 'refund', 'general', 'admin') NOT NULL,
        migration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('success', 'failed') DEFAULT 'success',
        error_message TEXT,
        INDEX idx_local_path (local_path),
        INDEX idx_cloudinary_public_id (cloudinary_public_id),
        INDEX idx_image_type (image_type)
    )";
    
    if ($conn->query($sql)) {
        echo "✅ Created image_migrations table\n";
    } else {
        echo "⚠️  Table may already exist: " . $conn->error . "\n";
    }
    
    echo "\n✅ Database preparation complete!\n";
    echo "\nDatabase is ready for Cloudinary migration.\n";
    echo "Tables with Cloudinary support:\n";
    echo "  - product_images (cloud_url, cloud_public_id, cloud_provider)\n";
    echo "  - carousel_images (cloud_url, cloud_public_id, cloud_provider)\n";
    echo "  - image_migrations (tracking table)\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

$conn->close();
?>
