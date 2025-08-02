<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

// Function to fix path
function fixPath($path) {
    // Remove any leading ../
    $path = preg_replace('/^\.\.\/+/', '', $path);
    
    // Remove any leading ./
    $path = preg_replace('/^\.\/+/', '', $path);
    
    // Remove any leading frontend/
    $path = preg_replace('/^frontend\//', '', $path);
    
    // Ensure path starts with /
    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }
    
    return $path;
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Get all image paths
    $sql = "SELECT id, image_url FROM product_images WHERE image_url IS NOT NULL";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception("Error fetching images: " . mysqli_error($conn));
    }

    $update_stmt = mysqli_prepare($conn, "UPDATE product_images SET image_url = ? WHERE id = ?");
    if (!$update_stmt) {
        throw new Exception("Error preparing update statement: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $fixed_path = fixPath($row['image_url']);
        
        mysqli_stmt_bind_param($update_stmt, "si", $fixed_path, $row['id']);
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Error updating path: " . mysqli_stmt_error($update_stmt));
        }
    }

    // Commit transaction
    mysqli_commit($conn);
    echo "Successfully updated image paths.";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage();
}

// Close connections
mysqli_stmt_close($update_stmt);
mysqli_close($conn);
?> 