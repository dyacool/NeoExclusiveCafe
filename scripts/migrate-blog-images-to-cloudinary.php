<?php
/**
 * Script to migrate existing blog images (both admin and user) to Cloudinary
 * Run this script once to migrate all existing local blog images to cloud storage
 */

require_once __DIR__ . '/../config/cloudinary-config.php';
require_once __DIR__ . '/../config/database-config.php';
require_once __DIR__ . '/../backend/includes/cloudinary-helper.php';

// Get database connection
$conn = getDatabaseConnection();

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "BLOG IMAGES MIGRATION TO CLOUDINARY\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n\n";

$stats = [
    'admin_blog' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
    'user_blog' => ['success' => 0, 'failed' => 0, 'skipped' => 0]
];

/**
 * Migrate admin blog images
 */
function migrateAdminBlogImages($conn, &$stats) {
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "MIGRATING ADMIN BLOG IMAGES\n";
    echo str_repeat("-", 80) . "\n\n";
    
    // Get all admin blog posts with local images that haven't been migrated
    $sql = "SELECT adblog_id, title, image_path 
            FROM blog_posts 
            WHERE image_path IS NOT NULL 
            AND image_path != '' 
            AND (cloud_url IS NULL OR cloud_url = '')
            ORDER BY adblog_id";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo "ERROR: Failed to query admin blog posts: " . mysqli_error($conn) . "\n";
        return;
    }
    
    $totalPosts = mysqli_num_rows($result);
    echo "Found {$totalPosts} admin blog posts with local images to migrate\n\n";
    
    $current = 0;
    while ($post = mysqli_fetch_assoc($result)) {
        $current++;
        $postId = $post['adblog_id'];
        $title = $post['title'];
        $imagePath = $post['image_path'];
        
        echo "[{$current}/{$totalPosts}] Migrating: {$title}\n";
        echo "  Image: {$imagePath}\n";
        
        // Construct local path
        $localPath = __DIR__ . '/../assets/uploaded-images-admin/' . $imagePath;
        
        // Check if file exists
        if (!file_exists($localPath)) {
            echo "  ⚠️  SKIPPED: File not found\n\n";
            $stats['admin_blog']['skipped']++;
            continue;
        }
        
        // Validate file
        $validation = validateImageFile($localPath);
        if (!$validation['valid']) {
            echo "  ⚠️  SKIPPED: {$validation['error']}\n\n";
            $stats['admin_blog']['skipped']++;
            continue;
        }
        
        // Generate public ID
        $publicId = 'admin_blog_' . $postId . '_' . uniqid();
        
        // Upload to Cloudinary
        $result = uploadToCloudinary($localPath, 'neocafe/admin_blog', $publicId);
        
        if ($result['success']) {
            // Update database
            $updateSql = "UPDATE blog_posts 
                         SET cloud_url = ?, 
                             cloud_public_id = ?, 
                             cloud_provider = 'cloudinary'
                         WHERE adblog_id = ?";
            
            $stmt = mysqli_prepare($conn, $updateSql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssi", $result['url'], $result['public_id'], $postId);
                if (mysqli_stmt_execute($stmt)) {
                    echo "  ✅ SUCCESS: {$result['url']}\n\n";
                    $stats['admin_blog']['success']++;
                } else {
                    echo "  ❌ FAILED: Database update failed\n\n";
                    $stats['admin_blog']['failed']++;
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "  ❌ FAILED: Could not prepare database statement\n\n";
                $stats['admin_blog']['failed']++;
            }
        } else {
            echo "  ❌ FAILED: {$result['error']}\n\n";
            $stats['admin_blog']['failed']++;
        }
    }
    
    echo "✅ Admin blog images migration complete!\n";
    echo "Success: {$stats['admin_blog']['success']}, Failed: {$stats['admin_blog']['failed']}, Skipped: {$stats['admin_blog']['skipped']}\n";
}

/**
 * Migrate user blog images
 */
function migrateUserBlogImages($conn, &$stats) {
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "MIGRATING USER BLOG IMAGES\n";
    echo str_repeat("-", 80) . "\n\n";
    
    // Get all user blog posts with local images that haven't been migrated
    $sql = "SELECT id, user_id, title, image_path 
            FROM user_blog_post 
            WHERE image_path IS NOT NULL 
            AND image_path != '' 
            AND (cloud_url IS NULL OR cloud_url = '')
            ORDER BY id";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo "ERROR: Failed to query user blog posts: " . mysqli_error($conn) . "\n";
        return;
    }
    
    $totalPosts = mysqli_num_rows($result);
    echo "Found {$totalPosts} user blog posts with local images to migrate\n\n";
    
    $current = 0;
    while ($post = mysqli_fetch_assoc($result)) {
        $current++;
        $postId = $post['id'];
        $userId = $post['user_id'];
        $title = $post['title'];
        $imagePath = $post['image_path'];
        
        echo "[{$current}/{$totalPosts}] Migrating: {$title} (User ID: {$userId})\n";
        echo "  Image: {$imagePath}\n";
        
        // Handle different path formats
        $localPath = '';
        if (strpos($imagePath, 'assets/') === 0) {
            // Path includes 'assets/' prefix
            $localPath = __DIR__ . '/../' . $imagePath;
        } else {
            // Path is just the filename
            $localPath = __DIR__ . '/../assets/uploaded-images-users/' . basename($imagePath);
        }
        
        // Check if file exists
        if (!file_exists($localPath)) {
            echo "  ⚠️  SKIPPED: File not found at {$localPath}\n\n";
            $stats['user_blog']['skipped']++;
            continue;
        }
        
        // Validate file
        $validation = validateImageFile($localPath);
        if (!$validation['valid']) {
            echo "  ⚠️  SKIPPED: {$validation['error']}\n\n";
            $stats['user_blog']['skipped']++;
            continue;
        }
        
        // Generate public ID
        $publicId = 'user_blog_' . $userId . '_' . $postId . '_' . uniqid();
        
        // Upload to Cloudinary
        $result = uploadToCloudinary($localPath, 'neocafe/user_blog', $publicId);
        
        if ($result['success']) {
            // Update database
            $updateSql = "UPDATE user_blog_post 
                         SET cloud_url = ?, 
                             cloud_public_id = ?, 
                             cloud_provider = 'cloudinary'
                         WHERE id = ?";
            
            $stmt = mysqli_prepare($conn, $updateSql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssi", $result['url'], $result['public_id'], $postId);
                if (mysqli_stmt_execute($stmt)) {
                    echo "  ✅ SUCCESS: {$result['url']}\n\n";
                    $stats['user_blog']['success']++;
                } else {
                    echo "  ❌ FAILED: Database update failed\n\n";
                    $stats['user_blog']['failed']++;
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "  ❌ FAILED: Could not prepare database statement\n\n";
                $stats['user_blog']['failed']++;
            }
        } else {
            echo "  ❌ FAILED: {$result['error']}\n\n";
            $stats['user_blog']['failed']++;
        }
    }
    
    echo "✅ User blog images migration complete!\n";
    echo "Success: {$stats['user_blog']['success']}, Failed: {$stats['user_blog']['failed']}, Skipped: {$stats['user_blog']['skipped']}\n";
}

try {
    // Migrate admin blog images
    migrateAdminBlogImages($conn, $stats);
    
    // Migrate user blog images
    migrateUserBlogImages($conn, $stats);
    
    // Print summary
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "MIGRATION SUMMARY\n";
    echo str_repeat("=", 80) . "\n\n";
    
    echo "Admin Blog Images:\n";
    echo "  ✅ Success: {$stats['admin_blog']['success']}\n";
    echo "  ❌ Failed: {$stats['admin_blog']['failed']}\n";
    echo "  ⚠️  Skipped: {$stats['admin_blog']['skipped']}\n\n";
    
    echo "User Blog Images:\n";
    echo "  ✅ Success: {$stats['user_blog']['success']}\n";
    echo "  ❌ Failed: {$stats['user_blog']['failed']}\n";
    echo "  ⚠️  Skipped: {$stats['user_blog']['skipped']}\n\n";
    
    $totalSuccess = $stats['admin_blog']['success'] + $stats['user_blog']['success'];
    $totalFailed = $stats['admin_blog']['failed'] + $stats['user_blog']['failed'];
    $totalSkipped = $stats['admin_blog']['skipped'] + $stats['user_blog']['skipped'];
    $total = $totalSuccess + $totalFailed + $totalSkipped;
    
    echo str_repeat("-", 80) . "\n";
    echo "OVERALL TOTALS:\n";
    echo "  Total Images Processed: {$total}\n";
    echo "  ✅ Successfully Migrated: {$totalSuccess}\n";
    echo "  ❌ Failed: {$totalFailed}\n";
    echo "  ⚠️  Skipped: {$totalSkipped}\n";
    echo str_repeat("=", 80) . "\n\n";
    
    echo "✅ Blog images migration completed successfully!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

mysqli_close($conn);
?>
