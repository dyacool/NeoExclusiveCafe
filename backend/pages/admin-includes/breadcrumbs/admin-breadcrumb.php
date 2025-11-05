<?php
/**
 * Admin Breadcrumb Navigation System
 * Simple and effective breadcrumb generation based on user system
 * 
 * Usage: Include this file in any admin page after the navigation
 * <?php include '../admin-includes/breadcrumbs/admin-breadcrumb.php'; ?>
 */

// Get current page information
$current_file = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Define simple route mappings for breadcrumbs
$route_mappings = [
    // Dashboard
    'dashboard' => ['Dashboard', ''],
    
    // Content Management
    'manage-carousel-images' => ['Dashboard Images', ''],
    'manage-carousel-settings' => ['Dashboard Content', ''],
    'admin-service-edit' => ['Services', ''],
    'promotions-settings' => ['Promotions', ''],
    'cb-knowledge-settings' => ['CB Knowledge', ''],
    'delivery-locations' => ['Delivery', ''],
    'product-categories' => ['Product Categories', ''],
    'about-settings' => ['About', ''],
    'terms-and-condition-management' => ['Terms & Conditions', ''],
    'privacy-policy-management' => ['Privacy Policy', ''],
    'footer-settings' => ['Footer', ''],

    // Blog
    'admin-blog-createpost' => ['Create Post', ''],
    'blog-details' => ['Blog Details', ''],
    
    // Refund Request
    'refund-request-lists' => ['Refund Requests', ''],
    'refund-details' => ['Refund Details', ''],
    
    // Bulk Orders
    'bulk-order-lists' => ['Bulk Orders', ''],
    'bulk-order' => ['Bulk Order Details', ''],
    
    // Orders
    'view-orders' => ['Order Details', ''],
    
    // Products
    'view-products' => ['Products', ''],
    'add-product' => ['Add Product', ''],
    'edit-product' => ['Edit Product', ''],
    
    // Transactions
    'transactions' => ['Transactions', ''],
    
    // Profile
    'admin-profile' => ['Profile', ''],
    'reset-password' => ['Reset Password', ''],
    'admin-account' => ['Admin Account', ''],
    'activity-logs' => ['Activity Logs', ''],
    'archive' => ['Archive', ''],
];

// Define parent relationships
$hierarchy = [
    // Content Management children
    'manage-carousel-images' => ['Content Management'],
    'manage-carousel-settings' => ['Content Management'],
    'admin-service-edit' => ['Content Management'],
    'promotions-settings' => ['Content Management'],
    'manage-promotions' => ['Content Management'],
    'cb-knowledge-settings' => ['Content Management'],
    'manage-chatbot' => ['Content Management'],
    'delivery-locations' => ['Content Management'],
    'manage-locations' => ['Content Management'],
    'product-categories' => ['Content Management'],
    'manage-categories' => ['Content Management'],
    'about-settings' => ['Content Management'],
    'terms-and-condition-management' => ['Content Management'],
    'privacy-policy-management' => ['Content Management'],
    'footer-settings' => ['Content Management'],
    
    // Blog children
    'admin-blog-createpost' => ['Blog'],
    'blog-details' => ['Blog'],
    
    // Order Management children
    'view-orders' => ['Orders'],
    'order-details' => ['Orders'],

    'bulk-order-lists' => ['Bulk Orders'],
    'bulk-order' => ['Bulk Orders'],

    'refund-request-lists' => ['Refund Requests'],
    'refund-details' => ['Refund Requests'],

    // Product Management children
    'view-products' => ['Product Management'],
    'add-product' => ['Product Management'],
    
    // Profile children
    'reset-password' => ['Profile'],
    'admin-account' => ['Profile'],
    'activity-logs' => ['Profile'],
    'archive' => ['Profile'],
];

/**
 * Generate breadcrumb trail
 */
function generateAdminBreadcrumb($current_file, $route_mappings, $hierarchy) {
    $breadcrumb = [];
    
    // Define URLs for parent categories
    $parent_urls = [
        'Content Management' => '../user-page-content/user-content-settings.php',
        'Blog' => '../blog/admin-blog.php',
        'Orders' => '../orders/order-list.php',
        'Bulk Orders' => '../bulks/bulk-order-lists.php',
        'Product Management' => '..products/product-list.php',
        'Refund Requests' => '../refund/refund-request-lists.php',
        'Profile' => '../account/admin-profile.php'
    ];
    
    // Add parent if exists
    if (isset($hierarchy[$current_file])) {
        foreach ($hierarchy[$current_file] as $parent) {
            $parent_url = isset($parent_urls[$parent]) ? $parent_urls[$parent] : '';
            $breadcrumb[] = ['title' => $parent, 'url' => $parent_url, 'current' => false];
        }
    }
    
    // Add current page
    if (isset($route_mappings[$current_file])) {
        $breadcrumb[] = [
            'title' => $route_mappings[$current_file][0], 
            'url' => '', 
            'current' => true
        ];
    } else {
        // Fallback for unmapped pages
        $page_title = ucfirst(str_replace(['-', '_'], ' ', $current_file));
        $breadcrumb[] = ['title' => $page_title, 'url' => '', 'current' => true];
    }
    
    return $breadcrumb;
}

// Generate breadcrumb for current page
$breadcrumb_items = generateAdminBreadcrumb($current_file, $route_mappings, $hierarchy);

// Only show breadcrumb if there are multiple items or not on dashboard
if (count($breadcrumb_items) > 1 || $current_file !== 'dashboard'):
?>

<link rel="stylesheet" href="../admin-includes/breadcrumbs/admin-breadcrumb.css">
<div class="admin-breadcrumb-container">
    <nav class="admin-breadcrumb-nav" aria-label="Breadcrumb navigation">
        <ol class="admin-breadcrumb-list">
            <?php foreach ($breadcrumb_items as $index => $item): ?>
                <?php $is_last = ($index === count($breadcrumb_items) - 1); ?>
                
                <li class="admin-breadcrumb-item <?php echo $item['current'] ? 'current' : ''; ?>">
                    <?php if (!$item['current'] && !empty($item['url'])): ?>
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" class="admin-breadcrumb-link">
                            <span class="admin-breadcrumb-text"><?php echo htmlspecialchars($item['title']); ?></span>
                        </a>
                    <?php elseif (!$item['current']): ?>
                        <span class="admin-breadcrumb-link">
                            <span class="admin-breadcrumb-text"><?php echo htmlspecialchars($item['title']); ?></span>
                        </span>
                    <?php else: ?>
                        <span class="admin-breadcrumb-current" aria-current="page">
                            <span class="admin-breadcrumb-text"><?php echo htmlspecialchars($item['title']); ?></span>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!$is_last): ?>
                        <span class="admin-breadcrumb-separator" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>

<?php endif; ?>
