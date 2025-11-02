<?php
/**
 * Cleanup Test Orders Script
 * Deletes all orders and related data including:
 * - Orders
 * - Order items
 * - Order status settings
 * - Proof of delivery records and images (local + Cloudinary)
 * - Notifications
 * - Activity logs
 * - Refund records
 */

require_once __DIR__ . '/../pages/admin-includes/database.php';
require_once __DIR__ . '/../includes/cloudinary-helper.php';

echo "=== CLEANUP TEST ORDERS SCRIPT ===\n\n";
echo "⚠️  WARNING: This will delete ALL orders and related data!\n";
echo "This action cannot be undone.\n\n";

// Confirmation prompt
echo "Type 'DELETE ALL ORDERS' to confirm: ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'DELETE ALL ORDERS') {
    echo "\n❌ Cleanup cancelled. No data was deleted.\n";
    exit(0);
}

echo "\n🗑️  Starting cleanup process...\n\n";

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    $deleted_counts = [
        'orders' => 0,
        'order_items' => 0,
        'pod_orders' => 0,
        'cloudinary_images' => 0,
        'local_files' => 0,
        'notifications' => 0,
        'activity_logs' => 0,
        'refunds' => 0,
        'refund_vouchers' => 0,
        'order_status_settings' => 0
    ];
    
    // 1. Get all proof of delivery images for Cloudinary cleanup
    echo "1. Fetching proof of delivery images...\n";
    $pod_sql = "SELECT id, order_id, proof_image_path, cloudinary_public_id FROM pod_orders";
    $pod_result = mysqli_query($conn, $pod_sql);
    
    $cloudinary_ids = [];
    $local_files = [];
    
    while ($pod = mysqli_fetch_assoc($pod_result)) {
        if (!empty($pod['cloudinary_public_id'])) {
            $cloudinary_ids[] = $pod['cloudinary_public_id'];
        }
        // Check if it's a local file path
        if (!empty($pod['proof_image_path']) && strpos($pod['proof_image_path'], 'http') !== 0) {
            $local_files[] = $pod['proof_image_path'];
        }
    }
    
    echo "   Found " . count($cloudinary_ids) . " Cloudinary images\n";
    echo "   Found " . count($local_files) . " local files\n";
    
    // 2. Delete from Cloudinary
    if (!empty($cloudinary_ids)) {
        echo "\n2. Deleting images from Cloudinary...\n";
        foreach ($cloudinary_ids as $public_id) {
            $result = deleteFromCloudinary($public_id);
            if ($result['success']) {
                echo "   ✓ Deleted: $public_id\n";
                $deleted_counts['cloudinary_images']++;
            } else {
                echo "   ⚠ Failed to delete: $public_id (" . ($result['error'] ?? 'Unknown error') . ")\n";
            }
        }
    } else {
        echo "\n2. No Cloudinary images to delete\n";
    }
    
    // 3. Delete local files
    if (!empty($local_files)) {
        echo "\n3. Deleting local files...\n";
        foreach ($local_files as $file_path) {
            $full_path = __DIR__ . '/../../' . $file_path;
            if (file_exists($full_path)) {
                if (unlink($full_path)) {
                    echo "   ✓ Deleted: $file_path\n";
                    $deleted_counts['local_files']++;
                } else {
                    echo "   ⚠ Failed to delete: $file_path\n";
                }
            }
        }
    } else {
        echo "\n3. No local files to delete\n";
    }
    
    // 4. Delete refund vouchers (if table exists)
    echo "\n4. Deleting refund vouchers...\n";
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'refund_vouchers'");
    if (mysqli_num_rows($table_check) > 0) {
        $result = mysqli_query($conn, "DELETE FROM refund_vouchers");
        $deleted_counts['refund_vouchers'] = mysqli_affected_rows($conn);
        echo "   ✓ Deleted {$deleted_counts['refund_vouchers']} refund vouchers\n";
    } else {
        echo "   ⚠ refund_vouchers table does not exist\n";
    }
    
    // 5. Delete order refunds (if table exists)
    echo "\n5. Deleting order refunds...\n";
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'order_refunds'");
    if (mysqli_num_rows($table_check) > 0) {
        $result = mysqli_query($conn, "DELETE FROM order_refunds");
        $deleted_counts['refunds'] = mysqli_affected_rows($conn);
        echo "   ✓ Deleted {$deleted_counts['refunds']} refund records\n";
    } else {
        echo "   ⚠ order_refunds table does not exist\n";
    }
    
    // 6. Delete proof of delivery records
    echo "\n6. Deleting proof of delivery records...\n";
    $result = mysqli_query($conn, "DELETE FROM pod_orders");
    $deleted_counts['pod_orders'] = mysqli_affected_rows($conn);
    echo "   ✓ Deleted {$deleted_counts['pod_orders']} POD records\n";
    
    // 7. Delete notifications (if table exists)
    echo "\n7. Deleting order notifications...\n";
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
    if (mysqli_num_rows($table_check) > 0) {
        $result = mysqli_query($conn, "DELETE FROM notifications WHERE type = 'order' OR message LIKE '%order%'");
        $deleted_counts['notifications'] = mysqli_affected_rows($conn);
        echo "   ✓ Deleted {$deleted_counts['notifications']} notifications\n";
    } else {
        echo "   ⚠ notifications table does not exist\n";
    }
    
    // 8. Skip activity logs (keeping for audit trail)
    echo "\n8. Skipping activity logs (keeping for audit trail)...\n";
    echo "   ⓘ Activity logs will be preserved\n";    
    // 9. Delete order items
    echo "\n9. Deleting order items...\n";
    $result = mysqli_query($conn, "DELETE FROM order_items");
    $deleted_counts['order_items'] = mysqli_affected_rows($conn);
    echo "   ✓ Deleted {$deleted_counts['order_items']} order items\n";
    
    // 10. Delete order status settings
    echo "\n10. Deleting order status settings...\n";
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'order_status_settings'");
    if (mysqli_num_rows($table_check) > 0) {
        $result = mysqli_query($conn, "DELETE FROM order_status_settings");
        $deleted_counts['order_status_settings'] = mysqli_affected_rows($conn);
        echo "   ✓ Deleted {$deleted_counts['order_status_settings']} status settings\n";
    } else {
        echo "   ⚠ order_status_settings table does not exist\n";
    }
    
    // 11. Delete orders
    echo "\n11. Deleting orders...\n";
    $result = mysqli_query($conn, "DELETE FROM orders");
    $deleted_counts['orders'] = mysqli_affected_rows($conn);
    echo "   ✓ Deleted {$deleted_counts['orders']} orders\n";
    
    // 12. Reset auto-increment IDs
    echo "\n12. Resetting auto-increment counters...\n";
    mysqli_query($conn, "ALTER TABLE orders AUTO_INCREMENT = 1");
    mysqli_query($conn, "ALTER TABLE order_items AUTO_INCREMENT = 1");
    mysqli_query($conn, "ALTER TABLE pod_orders AUTO_INCREMENT = 1");
    echo "   ✓ Auto-increment counters reset\n";
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Summary
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ CLEANUP COMPLETED SUCCESSFULLY!\n";
    echo str_repeat("=", 50) . "\n\n";
    
    echo "Summary:\n";
    echo "  • Orders deleted: {$deleted_counts['orders']}\n";
    echo "  • Order items deleted: {$deleted_counts['order_items']}\n";
    echo "  • POD records deleted: {$deleted_counts['pod_orders']}\n";
    echo "  • Cloudinary images deleted: {$deleted_counts['cloudinary_images']}\n";
    echo "  • Local files deleted: {$deleted_counts['local_files']}\n";
    echo "  • Notifications deleted: {$deleted_counts['notifications']}\n";
    echo "  • Activity logs deleted: {$deleted_counts['activity_logs']}\n";
    echo "  • Refunds deleted: {$deleted_counts['refunds']}\n";
    echo "  • Refund vouchers deleted: {$deleted_counts['refund_vouchers']}\n";
    echo "  • Status settings deleted: {$deleted_counts['order_status_settings']}\n";
    
    echo "\n✨ Database is now clean and ready for production!\n";
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    echo "\n❌ Cleanup failed: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
    exit(1);
}

mysqli_close($conn);
?>
