<?php
/**
 * Admin Breadcrumb Navigation System
 * Automatically generates breadcrumbs based on current page location
 * 
 * Usage: Include this file in any admin page after the navigation
 * <?php include '../admin-includes/breadcrumbs/admin-breadcrumb.php'; ?>
 * 
 * Or specify custom breadcrumbs:
 * <?php 
 * $custom_breadcrumbs = [
 *     ['title' => 'Products', 'url' => '../products/view-products.php'],
 *     ['title' => 'Edit Product', 'url' => '']
 * ];
 * include '../admin-includes/breadcrumbs/admin-breadcrumb.php'; 
 * ?>
 */

// Get the current file path
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$breadcrumb_map = [
    // Dashboard
    'dashboard' => [
        'dashboard.php' => ['Dashboard']
    ],
    
    // Content Management
    'homepage' => [
        'dashboard-images.php' => ['Content Management', 'Dashboard Images'],
        'dashboard-content.php' => ['Content Management', 'Dashboard Content'],
        'homepage-content.php' => ['Content Management', 'Dashboard Content']
    ],
    
    'services' => [
        'services.php' => ['Content Management', 'Services'],
        'manage-services.php' => ['Content Management', 'Services']
    ],
    
    'promotions' => [
        'promotions.php' => ['Content Management', 'Promotions'],
        'manage-promotions.php' => ['Content Management', 'Promotions']
    ],
    
    'chatbot' => [
        'chatbot-knowledge.php' => ['Content Management', 'Chatbot Knowledge'],
        'manage-chatbot.php' => ['Content Management', 'Chatbot Knowledge']
    ],
    
    'delivery' => [
        'delivery-locations.php' => ['Content Management', 'Delivery Locations'],
        'manage-locations.php' => ['Content Management', 'Delivery Locations']
    ],
    
    'categories' => [
        'product-categories.php' => ['Content Management', 'Product Categories'],
        'manage-categories.php' => ['Content Management', 'Product Categories']
    ],
    
    'about' => [
        'about-page.php' => ['Content Management', 'About Page'],
        'edit-about.php' => ['Content Management', 'About Page']
    ],
    
    'terms' => [
        'terms-conditions.php' => ['Content Management', 'Terms & Conditions'],
        'edit-terms.php' => ['Content Management', 'Terms & Conditions']
    ],
    
    'privacy' => [
        'privacy-policy.php' => ['Content Management', 'Privacy Policy'],
        'edit-privacy.php' => ['Content Management', 'Privacy Policy']
    ],
    
    'footer' => [
        'footer.php' => ['Content Management', 'Footer'],
        'edit-footer.php' => ['Content Management', 'Footer'],
        'manage-footer.php' => ['Content Management', 'Footer']
    ],
    
    // Blog
    'blog' => [
        'admin-blog-createpost.php' => ['Blog', 'Create Post'],
        'admin-blog-editpost.php' => ['Blog', 'Blog Details'],
        'admin-blog-lists.php' => ['Blog', 'Blog Details']
    ],
    
    // Refund Request
    'refund' => [
        'refund-request-lists.php' => ['Refund Request', 'Refund Details'],
        'refund-details.php' => ['Refund Request', 'Refund Details']
    ],
    
    // Bulk Order List
    'bulks' => [
        'bulk-order-lists.php' => ['Bulk Order List', 'Bulk Order Details'],
        'bulk-order.php' => ['Bulk Order List', 'Bulk Order Details']
    ],
    
    // Order List
    'orders' => [
        'view-orders.php' => ['Order List', 'Order Details'],
        'order-details.php' => ['Order List', 'Order Details']
    ],
    
    // Product List
    'products' => [
        'view-products.php' => ['Product List', 'Add Product'],
        'add-product.php' => ['Product List', 'Add Product'],
        'edit-product.php' => ['Product List', 'Add Product']
    ],
    
    // Profile
    'profile' => [
        'admin-profile.php' => ['Profile'],
        'edit-profile.php' => ['Profile', 'Edit Profile'],
        'reset-password.php' => ['Profile', 'Reset Password'],
        'admin-account.php' => ['Profile', 'Admin Account']
    ],
    
    'activity-logs' => [
        'activity-logs.php' => ['Profile', 'Activity Logs']
    ],
    
    'archives' => [
        'archive.php' => ['Profile', 'Archive']
    ]
];

// Build breadcrumb trail
$breadcrumb_trail = [];

// Check if custom breadcrumbs are provided
if (isset($custom_breadcrumbs) && is_array($custom_breadcrumbs)) {
    $breadcrumb_trail = $custom_breadcrumbs;
} else {
    // Auto-generate breadcrumbs based on current location
    
    // Add breadcrumbs from map
    if (isset($breadcrumb_map[$current_dir][$current_file])) {
        $crumbs = $breadcrumb_map[$current_dir][$current_file];
        
        foreach ($crumbs as $index => $crumb) {
            // Last item should have no URL (current page)
            if ($index === count($crumbs) - 1) {
                $breadcrumb_trail[] = [
                    'title' => $crumb,
                    'url' => ''
                ];
            } else {
                // First item in section - link to main page
                $main_page = '';
                switch ($current_dir) {
                    case 'homepage':
                    case 'services':
                    case 'promotions':
                    case 'chatbot':
                    case 'delivery':
                    case 'categories':
                    case 'about':
                    case 'terms':
                    case 'privacy':
                    case 'footer':
                        $main_page = '../homepage/dashboard-content.php';
                        break;
                    case 'blog':
                        $main_page = '../blog/admin-blog-createpost.php';
                        break;
                    case 'refund':
                        $main_page = '../refund/refund-request-lists.php';
                        break;
                    case 'bulks':
                        $main_page = '../bulks/bulk-order-lists.php';
                        break;
                    case 'orders':
                        $main_page = '../orders/view-orders.php';
                        break;
                    case 'products':
                        $main_page = '../products/view-products.php';
                        break;
                    case 'profile':
                    case 'activity-logs':
                    case 'archives':
                        $main_page = '../account/admin-profile.php';
                        break;
                }
                
                $breadcrumb_trail[] = [
                    'title' => $crumb,
                    'url' => $main_page
                ];
            }
        }
    } else {
        // Fallback for unmapped pages
        $page_title = str_replace(['-', '_', '.php'], [' ', ' ', ''], $current_file);
        $page_title = ucwords($page_title);
        
        $breadcrumb_trail[] = [
            'title' => $page_title,
            'url' => ''
        ];
    }
}

// If we only have one item (current page), don't show breadcrumb
if (count($breadcrumb_trail) <= 1 && $current_file === 'dashboard.php') {
    return;
}
?>

<link rel="stylesheet" href="../admin-includes/breadcrumbs/admin-breadcrumb.css">
<div class="admin-breadcrumb-container">
    <nav class="admin-breadcrumb-nav" aria-label="Breadcrumb navigation">
        <ol class="admin-breadcrumb-list">
            <?php foreach ($breadcrumb_trail as $index => $crumb): ?>
                <?php $is_last = ($index === count($breadcrumb_trail) - 1); ?>
                
                <li class="admin-breadcrumb-item <?php echo $is_last ? 'current' : ''; ?>">
                    <?php if (!$is_last && !empty($crumb['url'])): ?>
                        <a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="admin-breadcrumb-link">
                            <span class="admin-breadcrumb-text"><?php echo htmlspecialchars($crumb['title']); ?></span>
                        </a>
                    <?php else: ?>
                        <span class="admin-breadcrumb-current" aria-current="page">
                            <span class="admin-breadcrumb-text"><?php echo htmlspecialchars($crumb['title']); ?></span>
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
