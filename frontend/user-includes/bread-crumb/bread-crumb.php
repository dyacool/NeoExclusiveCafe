<?php
/**
 * Dynamic Breadcrumb Navigation Component
 * Automatically generates breadcrumb navigation based on current URL path
 * 
 * Usage: <?php include __DIR__ . "/path/to/breadcrumb.php"; ?>
 */

// Get current URL information
$current_url = $_SERVER['REQUEST_URI'];
$current_script = $_SERVER['SCRIPT_NAME'];
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Extract additional context from URL path for better detection
$url_path = parse_url($current_url, PHP_URL_PATH);
$path_segments = array_filter(explode('/', $url_path));

// Define route mappings for proper breadcrumb generation
$route_mappings = [
    // Home routes
    'user-dashboard' => ['Home', '/frontend/pages/home/user-dashboard.php'],
    'index' => ['Home', '/'],
    
    // Product routes  
    'product-dashboard' => ['Products', '/frontend/pages/products/product-dashboard.php'],
    'weekly-product' => ['For Delivery', '/frontend/pages/products/weekly-product.php'],
    'user-products' => ['For Pick Up', '/frontend/pages/products/user-products.php'],
    'product' => ['Product Details', ''],
    'product-details' => ['Product Details', ''],
    
    // Bulk order routes
    'bulk-form' => ['Bulk Order', '/frontend/pages/bulk/bulk-form.php'],
    'bulk-order-details' => ['Order Details', ''],
    'bulk-order-success' => ['Order Success', ''],
    'bulk-confirmation' => ['Order Confirmation', ''],
    
    // Blog routes
    'blog-list' => ['Neo Cafe\' Corner', '/frontend/pages/blog/blog-list.php'],
    'create-blog' => ['Create Post', '/frontend/pages/blog/create-blog.php'],
    'view-blog-admin' => ['View Post', ''],
    'view-blog' => ['View Post', ''],
    'blog-post' => ['Blog Post', ''],
    'blog-detail' => ['Blog Post', ''],
    
    // About routes
    'about-page' => ['About', '/frontend/pages/about/about-page.php'],
    'about' => ['About', '/frontend/pages/about/about-page.php'],
    
    // Profile routes
    'profile' => ['Profile', '/frontend/pages/profile/profile.php'],
    'account-settings' => ['Account Settings', '/frontend/pages/profile/account-settings.php'],
    'my-orders' => ['My Orders', '/frontend/pages/profile/my-orders.php'],
    'saved-posts' => ['Saved Posts', '/frontend/pages/profile/saved-posts.php'],
    'user-blog-post' => ['My Testimonials', '/frontend/pages/blog/user-blog-post.php'],
    
    // Cart routes
    'cart' => ['Shopping Cart', '/frontend/pages/cart/cart.php'],
    'shopping-cart-preorder' => ['Pre-Order Cart', '/frontend/pages/cart/shopping-cart-preorder.php'],
    'shopping-cart-sameday' => ['Same-Day Cart', '/frontend/pages/cart/shopping-cart-sameday.php'],
    'checkout' => ['Checkout', '/frontend/pages/cart/checkout.php'],
    'availtoday-checkout' => ['Checkout', '/frontend/pages/cart/availtoday-checkout.php'],

    'order-confirmation' => ['Order Confirmation', ''],
    'order-details' => ['Order Details', ''],
    
    // Terms and Privacy routes
    'terms-and-condition' => ['Terms & Conditions', '/frontend/pages/terms/terms-and-condition.php'],
    'privacy-policy' => ['Privacy Policy', '/frontend/pages/privacy-policy/privacy-policy.php'],
    
    // Search routes
    'search-results' => ['Search Results', '/frontend/search/search-results.php'],
    
    // Auth routes
    'login-signup' => ['Login', '/frontend/login/user/login-signup.php'],
    'forgot-password' => ['Forgot Password', ''],
    'reset-password' => ['Reset Password', ''],
];

// Define parent-child relationships for nested navigation
$hierarchy = [
    // Product hierarchy
    'products-categories' => ['user-dashboard'],
    'weekly-product' => ['user-dashboard', 'products-categories'],
    'user-products' => ['user-dashboard', 'products-categories'],
    'product' => ['user-dashboard', 'products-categories'],
    'product-details' => ['user-dashboard'], // Dynamic parent handled separately
    
    // Bulk order hierarchy
    'bulk-form' => ['user-dashboard'],
    'bulk-order-details' => ['user-dashboard', 'profile'],
    'bulk-order-success' => ['user-dashboard', 'bulk-form'],
    'bulk-confirmation' => ['user-dashboard', 'bulk-form'],
    
    // Blog hierarchy
    'blog-dashboard' => ['user-dashboard'],
    'blog-list' => ['user-dashboard', 'blog-dashboard'],
    'user-blog' => ['user-dashboard'],
    'create-blog' => ['user-dashboard'],
    'edit-blog' => ['user-dashboard', 'user-blog-post'],
    'view-blog-admin' => ['user-dashboard',  'blog-list'],
    'view-blog' => ['user-dashboard', 'user-blog'],
    'blog-post' => ['user-dashboard', 'blog-dashboard'],
    'blog-detail' => ['user-dashboard', 'blog-dashboard'],
    
    // About hierarchy
    'about-page' => ['user-dashboard'],
    'about' => ['user-dashboard'],
    
    // Profile hierarchy
    'profile' => ['user-dashboard'],
    'account-settings' => ['user-dashboard', 'profile'],
    'my-orders' => ['user-dashboard', 'profile'],
    'saved-posts' => ['user-dashboard', 'profile'],
    'user-blog-post' => ['user-dashboard', 'profile'],
    
    // Cart hierarchy
    'cart' => ['user-dashboard'],
    'shopping-cart-preorder' => ['user-dashboard'],
    'shopping-cart-sameday' => ['user-dashboard'],
    'checkout' => ['user-dashboard', 'cart'],
    'availtoday-checkout' => ['user-dashboard', 'cart'],
    'order-confirmation' => ['user-dashboard', 'cart'],
    'order-details' => ['user-dashboard', 'profile'],
    
    // Legal pages hierarchy
    'terms-and-condition' => ['user-dashboard'],
    'privacy-policy' => ['user-dashboard'],
    
    // Search hierarchy
    'search-results' => ['user-dashboard'],
    
    // Auth hierarchy (minimal breadcrumb)
    'forgot-password' => ['login-signup'],
    'reset-password' => ['login-signup'],
];

/**
 * Detect page context from URL path for better breadcrumb accuracy
 */
function detectPageContext($path_segments, $current_page) {
    $context = [];
    
    // Check if we're in a specific section
    foreach ($path_segments as $segment) {
        switch ($segment) {
            case 'products':
                $context[] = 'products';
                break;
            case 'blog':
                $context[] = 'blog';
                break;
            case 'profile':
                $context[] = 'profile';
                break;
            case 'cart':
                $context[] = 'cart';
                break;
            case 'bulk':
                $context[] = 'bulk';
                break;
            case 'about':
                $context[] = 'about';
                break;
        }
    }
    
    return $context;
}

/**
 * Generate breadcrumb trail based on current page and context
 */
function generateBreadcrumb($current_page, $route_mappings, $hierarchy, $context = []) {
    $breadcrumb = [];
    
    // Always start with Home if not already on home page
    if ($current_page !== 'user-dashboard' && $current_page !== 'index') {
        $breadcrumb[] = ['Home', '/frontend/pages/home/user-dashboard.php', false];
    }
    
    // Special handling for create-blog page - link back to testimonials section
    if ($current_page === 'create-blog') {
        $breadcrumb[] = ['Testimonials', '/frontend/pages/profile/user-profile.php#testimonials', false];
    } elseif ($current_page === 'edit-blog') {
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        if (strpos($referrer, 'user-blog-post.php') !== false) {
            // Came from My Posts page
            $breadcrumb[] = ['My Posts', '/frontend/pages/blog/user-blog-post.php', false];
        } else {
            // Default or came from Customer Testimonials
            $breadcrumb[] = ['Customer Testimonials', '/frontend/pages/blog/user-blog.php', false];
        }
    } elseif ($current_page === 'product-details') {
        // Dynamic back location for product details based on referrer
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        if (strpos($referrer, 'search-results.php') !== false) {
            // Came from search results
            $breadcrumb[] = ['Search Results', '/frontend/search/search-results.php', false];
        } elseif (strpos($referrer, 'product-dashboard.php') !== false) {
            // Came from product dashboard with category
            if (isset($_GET['category'])) {
                // Get category name from database
                $category_slug = $_GET['category'];
                $cat_query = "SELECT name FROM categories WHERE slug = ? AND is_active = 1";
                $cat_stmt = $conn->prepare($cat_query);
                $cat_stmt->bind_param("s", $category_slug);
                $cat_stmt->execute();
                $cat_result = $cat_stmt->get_result();
                
                if ($cat_result->num_rows > 0) {
                    $cat_row = $cat_result->fetch_assoc();
                    $breadcrumb[] = [$cat_row['name'], '/frontend/pages/products/product-dashboard.php?category=' . urlencode($category_slug), false];
                } else {
                    $breadcrumb[] = ['Products', '/frontend/pages/products/product-dashboard.php', false];
                }
            } else {
                $breadcrumb[] = ['Products', '/frontend/pages/products/product-dashboard.php', false];
            }
        } else {
            // Default to user dashboard (came from home or other location)
            // No additional breadcrumb item needed, just Home -> Product Details
        }
    } else {
        // Add contextual parents based on URL structure
        if (in_array('products', $context) && $current_page !== 'products-categories' && $current_page !== 'product-dashboard') {
            $breadcrumb[] = ['Products', '/frontend/pages/products/products-categories.php', false];
        }
        
        // Add parent pages based on hierarchy
        if (isset($hierarchy[$current_page])) {
            foreach ($hierarchy[$current_page] as $parent) {
                if ($parent !== 'user-dashboard' && isset($route_mappings[$parent])) {
                    // Avoid duplicates
                    $parent_title = $route_mappings[$parent][0];
                    $already_added = false;
                    foreach ($breadcrumb as $item) {
                        if ($item[0] === $parent_title) {
                            $already_added = true;
                            break;
                        }
                    }
                    
                    if (!$already_added) {
                        $breadcrumb[] = [$route_mappings[$parent][0], $route_mappings[$parent][1], false];
                    }
                }
            }
        }
    }
    
    // Add current page (not clickable)
    if (isset($route_mappings[$current_page])) {
        $breadcrumb[] = [$route_mappings[$current_page][0], '', true];
    } else {
        // Fallback for unknown pages
        $page_title = ucfirst(str_replace(['-', '_'], ' ', $current_page));
        $breadcrumb[] = [$page_title, '', true];
    }
    
    return $breadcrumb;
}

// Detect page context
$page_context = detectPageContext($path_segments, $current_page);

// Generate breadcrumb for current page
$breadcrumb_items = generateBreadcrumb($current_page, $route_mappings, $hierarchy, $page_context);

// Only show breadcrumb if there are items and not on home page
if (!empty($breadcrumb_items) && $current_page !== 'user-dashboard' && $current_page !== 'index'):
?>

<link rel="stylesheet" href="/frontend/user-includes/bread-crumb/breadcrumb.css">

<div class="breadcrumb-container">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb navigation" role="navigation">
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
                <?php foreach ($breadcrumb_items as $index => $item): ?>
                    <li class="breadcrumb-item <?php echo $item[2] ? 'current' : ''; ?>" 
                        itemprop="itemListElement" 
                        itemscope 
                        itemtype="https://schema.org/ListItem">
                        
                        <?php if (!$item[2] && !empty($item[1])): // Not current page and has URL ?>
                            <a href="<?php echo htmlspecialchars($item[1]); ?>" 
                               class="breadcrumb-link"
                               itemprop="item"
                               title="Navigate to <?php echo htmlspecialchars($item[0]); ?>"
                               aria-label="Go to <?php echo htmlspecialchars($item[0]); ?>">
                                <span class="breadcrumb-text" itemprop="name">
                                    <?php echo htmlspecialchars($item[0]); ?>
                                </span>
                            </a>
                            <meta itemprop="position" content="<?php echo $index + 1; ?>">
                        <?php else: // Current page ?>
                            <span class="breadcrumb-current" 
                                  aria-current="page" 
                                  itemprop="item">
                                <span class="breadcrumb-text" itemprop="name">
                                    <?php echo htmlspecialchars($item[0]); ?>
                                </span>
                            </span>
                            <meta itemprop="position" content="<?php echo $index + 1; ?>">
                        <?php endif; ?>
                        
                        <?php if ($index < count($breadcrumb_items) - 1): // Not the last item ?>
                            <span class="breadcrumb-separator" aria-hidden="true">
                                <svg class="separator-icon" 
                                     width="20" height="20" 
                                     viewBox="0 0 24 24" 
                                     fill="none" 
                                     stroke="currentColor" 
                                     stroke-width="2" 
                                     stroke-linecap="round" 
                                     stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
            
        </div>
    </nav>
</div>

<?php endif; ?>