<?php
require_once __DIR__ . '/frontend/user-includes/database.php';

function sanitizeFilename($filename) {
    // Remove special characters and replace spaces with underscores
    $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Remove multiple consecutive underscores
    $clean = preg_replace('/_+/', '_', $clean);
    return $clean;
}

// First, let's check what images actually exist
$query = "SELECT pi.*, p.name as product_name 
          FROM neoexclusivecafe_crud.product_images pi 
          JOIN neoexclusivecafe_crud.products p ON pi.product_id = p.id";
$result = mysqli_query($conn, $query);

echo "Checking existing files and updating database...\n";

while ($row = mysqli_fetch_assoc($result)) {
    $current_path = $row['image_url'];
    
    // Remove any leading slashes and normalize the path
    $current_path = ltrim($current_path, '/');
    
    // Try different possible paths
    $possible_paths = [
        __DIR__ . '/assets/' . $current_path,  // Current path in DB
        __DIR__ . '/' . $current_path,         // Direct path
        __DIR__ . '/assets/product-images/' . basename($current_path), // Just filename in product-images
        str_replace('/product-images/', '/assets/product-images/', __DIR__ . '/' . $current_path) // Full corrected path
    ];
    
    $found_path = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $found_path = $path;
            break;
        }
    }
    
    if ($found_path) {
        // File exists, let's sanitize its name and move it to the correct location
        $new_filename = sanitizeFilename(basename($current_path));
        
        // Create a sanitized product folder name from the product name
        $product_folder = sanitizeFilename($row['product_name']);
        
        // Construct the new relative path
        $new_relative_path = 'assets/product-images/' . $product_folder . '/' . $new_filename;
        $new_full_path = __DIR__ . '/' . $new_relative_path;
        
        // Create directory if it doesn't exist
        $dir = dirname($new_full_path);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        
        // Move/rename file if needed
        if ($found_path !== $new_full_path) {
            if (rename($found_path, $new_full_path)) {
                // Update database with new path
                $update_query = "UPDATE neoexclusivecafe_crud.product_images SET image_url = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "si", $new_relative_path, $row['id']);
                mysqli_stmt_execute($stmt);
                
                echo "Updated: {$current_path} -> {$new_relative_path}\n";
            } else {
                echo "Failed to move: {$found_path} -> {$new_full_path}\n";
            }
        }
    } else {
        echo "File not found in any location: {$current_path}\n";
        
        // Remove the record if file doesn't exist
        $delete_query = "DELETE FROM neoexclusivecafe_crud.product_images WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $row['id']);
        mysqli_stmt_execute($stmt);
        echo "Removed database record for missing file.\n";
    }
}

// Now let's clean up any products that have no images
$cleanup_query = "DELETE FROM neoexclusivecafe_crud.products WHERE id NOT IN (SELECT DISTINCT product_id FROM neoexclusivecafe_crud.product_images)";
mysqli_query($conn, $cleanup_query);

echo "Done processing files.\n";
?> 