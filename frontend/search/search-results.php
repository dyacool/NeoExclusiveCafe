<?php
// Include database connection first - it handles session configuration
require_once __DIR__ . '/../../backend/pages/admin-includes/database.php';
require_once __DIR__ . "/../../includes/session-manager.php";
require_once __DIR__ . "/../user-includes/preview-mode.php";
require_once __DIR__ . "/../../backend/pages/products/todays-products-handler.php";

// $conn is now available from database.php

// Clean up past dates automatically when page loads
try {
    cleanupPastDates();
    // Also clean up today's dates if business hours have closed
    cleanupTodaysDatesAfterBusinessHours();
} catch (Exception $e) {
    error_log("Error cleaning up dates: " . $e->getMessage());
}

// Set page title and additional CSS
$page_title = "Search Results";
$additional_css = [
    "/frontend/search/search-results.css",
    "/frontend/pages/products/product-dashboard.css"
];

require_once __DIR__ . "/../user-includes/navbar/customer-navigation.php";

// Get business hours for same-day availability check
$business_hours = null;
if ($conn) {
    $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $business_hours_result = $conn->query($business_hours_query);
    if ($business_hours_result && $business_hours_result->num_rows > 0) {
        $business_hours = $business_hours_result->fetch_assoc();
    }
}

// Get the search query
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Initialize results array
$products = [];
$admin_blogs = [];
$testimonials = [];

// Only search if a query was provided
if (!empty($search_query)) {
    // Create search term with wildcards for partial word matching
    $search_param = "%" . $search_query . "%";
    
    // Check if user is searching for generic "product" or "products" - show all products
    $show_all_products = strtolower($search_query) === 'product' || strtolower($search_query) === 'products';
    
    // Search in products table
    try {
        if ($show_all_products) {
            // Show all products if searching for "product" or "products"
            $product_sql = "SELECT 
                                p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id,
                                ps.name AS status_name, 
                                COALESCE(pi.cloud_url, pi.image_url) as image_url,
                                p.quantity, p.show_when_unavailable, p.hide_when_unavailable,
                                p.availtoday_status_id, ats.name AS availtoday_status_name,
                                c.name AS category_name,
                                GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ', ') as todays_product_dates,
                                GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ', ') as regular_today_dates,
                                qpd.quantity as sameday_stock_today
                            FROM products p
                            LEFT JOIN product_statuses ps ON p.status_id = ps.id
                            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                            LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                            LEFT JOIN categories c ON p.category_id = c.id
                            LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
                            LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
                            LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
                            WHERE p.deleted_at IS NULL 
                            AND p.id > 0 
                            AND p.status_id IN (1, 2, 3, 4)
                            GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id, ps.name, pi.cloud_url, pi.image_url, p.quantity, p.show_when_unavailable, p.hide_when_unavailable, p.availtoday_status_id, ats.name, c.name, qpd.quantity
                            ORDER BY p.is_featured DESC, p.name ASC
                            LIMIT 100";
            
            $result = $conn->query($product_sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
        } else {
            // Normal search
            $product_sql = "SELECT 
                                p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id,
                                ps.name AS status_name, 
                                COALESCE(pi.cloud_url, pi.image_url) as image_url,
                                p.quantity, p.show_when_unavailable, p.hide_when_unavailable,
                                p.availtoday_status_id, ats.name AS availtoday_status_name,
                                c.name AS category_name,
                                GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ', ') as todays_product_dates,
                                GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ', ') as regular_today_dates,
                                qpd.quantity as sameday_stock_today
                            FROM products p
                            LEFT JOIN product_statuses ps ON p.status_id = ps.id
                            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                            LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                            LEFT JOIN categories c ON p.category_id = c.id
                            LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
                            LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
                            LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
                            WHERE p.name LIKE ?
                            AND p.deleted_at IS NULL 
                            AND p.id > 0 
                            AND p.status_id IN (1, 2, 3, 4)
                            GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id, ps.name, pi.cloud_url, pi.image_url, p.quantity, p.show_when_unavailable, p.hide_when_unavailable, p.availtoday_status_id, ats.name, c.name, qpd.quantity
                            ORDER BY p.is_featured DESC, p.name ASC
                            LIMIT 20";
            
            $stmt = $conn->prepare($product_sql);
            if ($stmt) {
                $stmt->bind_param("s", $search_param);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        // Silently handle errors
    }
    
    // Search in admin blog posts
    try {
        $admin_blog_sql = "SELECT adblog_id, title, description, image_path, cloud_url, author, created_at 
                          FROM blog_posts 
                          WHERE title LIKE ? OR description LIKE ?
                          ORDER BY created_at DESC 
                          LIMIT 10";
        
        $stmt = $conn->prepare($admin_blog_sql);
        if ($stmt) {
            $stmt->bind_param("ss", $search_param, $search_param);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $admin_blogs[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently handle errors
    }
    
    // Search in customer testimonials
    try {
        $testimonial_sql = "SELECT id, customer_name, testimonial_text, rating, image_path, cloud_url, created_at 
                           FROM customer_testimonials 
                           WHERE customer_name LIKE ? OR testimonial_text LIKE ?
                           ORDER BY created_at DESC 
                           LIMIT 10";
        
        $stmt = $conn->prepare($testimonial_sql);
        if ($stmt) {
            $stmt->bind_param("ss", $search_param, $search_param);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $testimonials[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently handle errors
    }
}

// Function to determine product availability (consistent with product-dashboard.php)
function determineProductAvailability($product_row, $today_date) {
    // Initialize result structure with default values
    // All products start with no capabilities and are assumed available for display
    $result = [
        'has_preorder' => false,      // Can customer pre-order this product?
        'has_sameday' => false,       // Can customer order for same-day delivery?
        'is_unavailable' => false,    // Is product completely unavailable?
        'unavailable_reason' => '',   // Why is it unavailable (if applicable)?
        'should_display' => true      // Should we show this product on the page?
    ];
    
    // Extract and normalize product data from database row
    // Use null coalescing operator (??) to provide safe defaults for missing values
    $status_id = $product_row['status_id'];                                      // Product status: 1=Regular, 2=Featured, 3=Limited, 4=Same-Day Only
    $preorder_stock = $product_row['quantity'] ?? 0;                            // Stock available for pre-orders
    $sameday_stock = $product_row['sameday_stock_today'] ?? 0;                  // Stock available for same-day orders today
    $has_availtoday_config = !empty($product_row['availtoday_status_id']);      // Does product have same-day configuration?
    $todays_dates = $product_row['todays_product_dates'] ? explode(', ', $product_row['todays_product_dates']) : [];  // Dates for status 4 products
    $regular_dates = $product_row['regular_today_dates'] ? explode(', ', $product_row['regular_today_dates']) : [];   // Dates for status 1/2/3 products with same-day
    $show_when_unavailable = (bool)($product_row['show_when_unavailable'] ?? 0); // Force show even when unavailable?
    $hide_when_unavailable = (bool)($product_row['hide_when_unavailable'] ?? 0); // Force hide when unavailable?
    
    // ============================================================================
    // STEP 1: Determine product capabilities
    // ============================================================================
    // This section determines WHAT the product CAN do (pre-order, same-day, or both)
    // We check capabilities independently before determining overall availability
    
    // Pre-order capability check:
    // Products with status 1/2/3 can be pre-ordered if they have stock
    // Status 4 products are same-day only and cannot be pre-ordered
    $result['has_preorder'] = in_array($status_id, [1, 2, 3]) && $preorder_stock > 0;
    
    // Same-day capability check for pure same-day products (status 4):
    // These products ONLY support same-day ordering
    // Requirements: stock available AND today's date must be in the configured dates list
    if ($status_id == 4) {
        $has_valid_date = in_array($today_date, $todays_dates);  // Is today in the todays_products_dates table?
        $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
    }
    
    // Same-day capability check for dual-capability products (status 1/2/3 with availtoday config):
    // These products support BOTH pre-order AND same-day ordering
    // Requirements: has availtoday_status_id set, stock available, AND today's date in regular_products_today_dates
    if (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
        $has_valid_date = in_array($today_date, $regular_dates);  // Is today in the regular_products_today_dates table?
        $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
    }
    
    // ============================================================================
    // STEP 2: Determine availability
    // ============================================================================
    // Now that we know the product's capabilities, determine if it's available
    // A product is unavailable ONLY if it has NO capabilities at all
    
    // Unavailability logic:
    // If product has neither pre-order nor same-day capability, it's unavailable
    $result['is_unavailable'] = !$result['has_preorder'] && !$result['has_sameday'];
    
    if ($result['is_unavailable']) {
        // Determine the specific reason for unavailability based on product type
        // This helps customers understand WHY they can't order the product
        
        if ($status_id == 4) {
            // Same-day only product (status 4) unavailability reasons:
            if ($sameday_stock <= 0) {
                $result['unavailable_reason'] = 'Out of Stock';        // No stock available
            } else {
                $result['unavailable_reason'] = 'Not Available Today'; // Has stock but not configured for today
            }
        } elseif (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
            // Dual-capability product (status 1/2/3 with same-day config) unavailability:
            // If we reach here, both pre-order and same-day stock are depleted
            $result['unavailable_reason'] = 'Out of Stock';
        } else {
            // Pre-order only product (status 1/2/3 without same-day config) unavailability:
            // No pre-order stock available
            $result['unavailable_reason'] = 'Out of Stock';
        }
    }
    
    // ============================================================================
    // STEP 3: Apply visibility rules
    // ============================================================================
    // Determine if the product should be displayed on the page
    // Visibility rules only apply to unavailable products
    
    if ($result['is_unavailable']) {
        // Visibility priority hierarchy:
        // 1. hide_when_unavailable flag (highest priority - always hide)
        // 2. show_when_unavailable flag (medium priority - always show)
        // 3. Default behavior (lowest priority - show in search results)
        
        if ($hide_when_unavailable) {
            // Explicit hide flag set - never show this product when unavailable
            $result['should_display'] = false;
        } elseif ($show_when_unavailable) {
            // Explicit show flag set - always show this product even when unavailable
            $result['should_display'] = true;
        } else {
            // Default behavior for search: show unavailable products (different from product-dashboard)
            $result['should_display'] = true;
        }
    } else {
        // Available products are always displayed regardless of visibility flags
        // Visibility flags only control unavailable product display
        $result['should_display'] = true;
    }
    
    return $result;
}

// Include the header/navigation
require_once __DIR__ . "/../user-includes/navbar/customer-navigation.php";
?>

<meta name="business-opening-time" content="<?php echo $business_hours['opening_time'] ?? '08:00'; ?>">
<meta name="business-closing-time" content="<?php echo $business_hours['closing_time'] ?? '21:00'; ?>">

<link rel="stylesheet" href="/frontend/search/search-results.css">
<link rel="stylesheet" href="/frontend/pages/products/product-dashboard.css">

<!-- Search Box Section - STICKY AT TOP -->
<div class="search-box-section">
    <div class="search-box-container">
        <div class="search-input-wrapper">
            <input type="text" 
                   id="pageSearchInput" 
                   class="page-search-input" 
                   placeholder="Search for products, blogs..." 
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   onkeyup="handleSearchInput()"
                   onkeypress="handleSearchKeyPress(event)">
            <button class="search-btn" onclick="performSearch()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </button>
        </div>
        <div id="suggestionsContainer" class="suggestions-container" style="display: none;"></div>
    </div>
</div>

<div class="wrapper">
    <div id="confirmationPopup" class="confirmation-popup"></div>
    <div class="search-results-container fade-in">
        <h1 class="search-title">Search Results for: "<?php echo htmlspecialchars($search_query); ?>"</h1>
        
        <?php if (empty($search_query)): ?>
            <div class="no-results">
                <p>Please enter a search term to find products and blog posts.</p>
            </div>
        <?php elseif (empty($products) && empty($admin_blogs) && empty($testimonials)): ?>
            <div class="no-results">
                <p>No results found for "<?php echo htmlspecialchars($search_query); ?>".</p>
                <p>Try different keywords or check your spelling.</p>
            </div>
        <?php else: ?>
            <!-- Product Results -->
            <?php if (!empty($products)): 
                // Sort products: Available first, then unavailable
                $today_date = date('Y-m-d');
                
                // Custom sort: Priority hierarchy - Available > Featured > Unavailable
                usort($products, function($a, $b) use ($today_date) {
                    // Use consistent availability determination
                    $a_availability = determineProductAvailability($a, $today_date);
                    $b_availability = determineProductAvailability($b, $today_date);
                    
                    $a_unavailable = $a_availability['is_unavailable'];
                    $b_unavailable = $b_availability['is_unavailable'];
                    
                    // Check if product A is available today
                    $a_available_today = false;
                    if (!empty($a['todays_product_dates'])) {
                        $a_dates = explode(', ', $a['todays_product_dates']);
                        $a_available_today = in_array($today_date, $a_dates);
                    }
                    if (!$a_available_today && !empty($a['regular_today_dates'])) {
                        $a_dates = explode(', ', $a['regular_today_dates']);
                        $a_available_today = in_array($today_date, $a_dates);
                    }
                    
                    // Check if product B is available today
                    $b_available_today = false;
                    if (!empty($b['todays_product_dates'])) {
                        $b_dates = explode(', ', $b['todays_product_dates']);
                        $b_available_today = in_array($today_date, $b_dates);
                    }
                    if (!$b_available_today && !empty($b['regular_today_dates'])) {
                        $b_dates = explode(', ', $b['regular_today_dates']);
                        $b_available_today = in_array($today_date, $b_dates);
                    }
                    
                    // Priority 0: Available products go first (unavailable products go to the end)
                    if ($a_unavailable && !$b_unavailable) return 1;
                    if (!$a_unavailable && $b_unavailable) return -1;
                    
                    // Priority 1: Available today (highest priority for available products)
                    if ($a_available_today && !$b_available_today) return -1;
                    if (!$a_available_today && $b_available_today) return 1;
                    
                    // Priority 2: Featured products
                    if ($a['is_featured'] && !$b['is_featured']) return -1;
                    if (!$a['is_featured'] && $b['is_featured']) return 1;
                    
                    // Priority 3: Alphabetical by name
                    return strcmp($a['name'], $b['name']);
                });
            ?>
                <div class="result-section">
                    <h2>Products (<?php echo count($products); ?>)</h2>
                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($products as $row): 
                            // Get all images for this product (prioritize Cloudinary URLs)
                            $images_sql = "SELECT COALESCE(cloud_url, image_url) as image_url FROM product_images WHERE product_id = ?";
                            $images_stmt = $conn->prepare($images_sql);
                            $images_stmt->bind_param("i", $row['id']);
                            $images_stmt->execute();
                            $images_result = $images_stmt->get_result();
                            $images = [];
                            while ($image = $images_result->fetch_assoc()) {
                                $images[] = $image['image_url'];
                            }
                            
                            $productData = [
                                'id' => $row['id'],
                                'name' => $row['name'],
                                'price' => $row['price'],
                                'description' => $row['description'],
                                'status' => $row['status_name'],
                                'status_id' => $row['status_id'],
                                'images' => $images,
                                'is_featured' => (bool)$row['is_featured'],
                                'quantity' => $row['quantity'],
                                'sameday_stock_today' => $row['sameday_stock_today'] ?? 0,
                                'show_when_unavailable' => (bool)$row['show_when_unavailable'],
                                'hide_when_unavailable' => (bool)($row['hide_when_unavailable'] ?? 0),
                                'availtoday_status_id' => $row['availtoday_status_id'],
                                'availtoday_status_name' => $row['availtoday_status_name'],
                                'todays_product_dates' => $row['todays_product_dates'] ? explode(', ', $row['todays_product_dates']) : [],
                                'regular_today_dates' => $row['regular_today_dates'] ? explode(', ', $row['regular_today_dates']) : []
                            ];
                            
                            $featuredClass = $row['is_featured'] ? 'featured-product' : '';
                            $statusClass = strtolower(str_replace(' ', '-', $row['status_name']));
                            
                            $productDataJson = htmlspecialchars(json_encode($productData), ENT_QUOTES, 'UTF-8');
                            
                            // Determine product availability using consistent logic
                            $availability = determineProductAvailability($row, $today_date);
                            $is_unavailable = $availability['is_unavailable'];
                            $unavailable_reason = $availability['unavailable_reason'];
                            
                            // Check if product is available TODAY
                            $is_available_today = false;
                            $today_date = date('Y-m-d');
                            
                            // Check todays_product_dates (for status 4 products)
                            if (!empty($row['todays_product_dates'])) {
                                $todays_dates = explode(', ', $row['todays_product_dates']);
                                if (in_array($today_date, $todays_dates)) {
                                    $is_available_today = true;
                                }
                            }
                            
                            // Check regular_today_dates (for status 1/2/3 products with same-day option)
                            if (!$is_available_today && !empty($row['regular_today_dates'])) {
                                $regular_dates = explode(', ', $row['regular_today_dates']);
                                if (in_array($today_date, $regular_dates)) {
                                    $is_available_today = true;
                                }
                            }
                            
                            // Add unavailable class if product is unavailable
                            $unavailableClass = $is_unavailable ? 'unavailable-product' : '';
                            
                            echo "<div class='product-card {$featuredClass} {$unavailableClass}' data-status='" . htmlspecialchars($row['status_name']) . "' 
                                  data-product='" . $productDataJson . "' 
                                  data-unavailable='" . ($is_unavailable ? 'true' : 'false') . "'
                                  onclick='openProductModalFromData(this)'>";
                            
                            // Display badges: Show capabilities based on product configuration
                            if ($is_unavailable) {
                                // Unavailable badge (highest priority, exclusive)
                                echo "<div class='unavailable-badge-left'>" . htmlspecialchars($unavailable_reason) . "</div>";
                            } else {
                                // Determine product capabilities
                                $has_preorder = in_array($row['status_id'], [1, 2, 3]) && $row['quantity'] > 0;
                                $has_sameday = ($row['status_id'] == 4) || (!empty($row['availtoday_status_id']) && $is_available_today);
                                
                                // Show badges based on capabilities
                                if ($has_sameday && $has_preorder) {
                                    // Product has BOTH capabilities
                                    echo "<div class='today-badge-left'>Same Day & Pre-Order</div>";
                                } elseif ($has_sameday) {
                                    // Same-day only
                                    echo "<div class='today-badge-left'>Same Day Order</div>";
                                } elseif ($has_preorder) {
                                    // Pre-order only
                                    echo "<div class='preorder-badge-left'>Pre-Order</div>";
                                }
                                // Note: Featured badge is handled by CSS class 'featured-product' and image overlay
                            }
                            
                            echo "    <div class='product-image'>";
                            
                            // Image overlays
                            if ($is_unavailable) {
                                // Unavailable overlay (exclusive)
                                echo "<div class='unavailable-overlay'>
                                        <span class='unavailable-text'>UNAVAILABLE</span>
                                        <span class='unavailable-reason'>" . htmlspecialchars($unavailable_reason) . "</span>
                                      </div>";
                            } 
                            
                            // Handle both Cloudinary URLs and local paths
                            $image_src = $row['image_url'] ?: 'images/no-image.jpg';
                            if (strpos($image_src, 'http://') === 0 || strpos($image_src, 'https://') === 0) {
                                // It's a full URL (Cloudinary)
                                $image_path = $image_src;
                            } else {
                                // It's a local path
                                $image_path = '../../../assets/' . $image_src;
                            }
                            
                            echo "    <img src='" . htmlspecialchars($image_path) . "' alt='" . htmlspecialchars($row['name']) . "'>
                                    </div>
                                    <div class='product-info'>
                                        <h3 class='productname'>" . htmlspecialchars($row['name']) . "</h3>
                                        <p class='price'>₱" . number_format($row['price'], 2) . "</p>";
                            
                            // First row: availtoday status badge and stock
                            echo "<div class='info-row-1'>";
                            if (!empty($row['availtoday_status_name'])) {
                                echo "<span class='availtoday-badge'>" . htmlspecialchars($row['availtoday_status_name']) . "</span>";
                            }
                            echo "<p class='stock'>Stock: " . $row['quantity'] . "</p>
                                  </div>";
                            
                            // Second row: status badge and available dates
                            echo "<div class='info-row-2'>
                                    <span class='status-badge status-{$statusClass}'>" . htmlspecialchars($row['status_name']) . "</span>";
                            
                            // Display available dates if product has them
                            $available_dates = $row['status_id'] == 4 ? $row['todays_product_dates'] : $row['regular_today_dates'];
                            if (!empty($available_dates)) {
                                // Format dates for display (e.g., "8/27, 8/28, 8/29")
                                $dates_array = explode(', ', $available_dates);
                                $formatted_dates = [];
                                foreach ($dates_array as $date) {
                                    $dateObj = DateTime::createFromFormat('Y-m-d', trim($date));
                                    if ($dateObj) {
                                        $formatted_dates[] = $dateObj->format('n/j'); // Format as M/D (e.g., 8/27)
                                    }
                                }
                                echo "<p class='available-dates'>Available Dates: " . htmlspecialchars(implode(', ', $formatted_dates)) . "</p>";
                            }
                            
                            echo "</div>";
                            
                            // Add to Cart button - disabled if unavailable
                            if ($is_unavailable) {
                                echo "<button class='add-to-cart unavailable-btn' disabled>Unavailable</button>";
                            } else {
                                echo "<button class='add-to-cart' onclick='event.stopPropagation(); addToCart(" . $row['id'] . ", this)'>Add to Cart</button>";
                            }
                            
                            echo "    </div>
                            </div>";
                        endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            

            <!-- Admin Blog Results -->
            <?php if (!empty($admin_blogs)): ?>
                <div class="result-section">
                    <h2>Neo Cafe's Corner (<?php echo count($admin_blogs); ?>)</h2>
                    <div class="instagram-feed">
                        <?php foreach ($admin_blogs as $post): ?>
                            <div class="instagram-post">
                                <a href="../pages/blog/view-blog-admin.php?id=<?php echo $post['adblog_id']; ?>" class="post-link">
                                <div class="post-header">
                                    <div class="user-info">
                                        <span class="username"><?php echo htmlspecialchars($post['author']); ?></span>
                                        <div class="post-date">
                                            <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($post['cloud_url']) || !empty($post['image_path'])): ?>
                                    <div class="post-image">
                                        <img src="<?php echo !empty($post['cloud_url']) ? htmlspecialchars($post['cloud_url']) : '/assets/uploaded-images-admin/' . htmlspecialchars($post['image_path']); ?>" 
                                            alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-content">
                                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <p class="post-description"><?php echo substr(htmlspecialchars($post['description']), 0, 100) . '...'; ?></p>
                                </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Customer Testimonials Results -->
            <?php if (!empty($testimonials)): ?>
                <div class="result-section">
                    <h2>Customer Testimonials (<?php echo count($testimonials); ?>)</h2>
                    <div class="testimonials-grid">
                        <?php foreach ($testimonials as $testimonial): ?>
                            <div class="testimonial-card">
                                <?php if (!empty($testimonial['cloud_url']) || !empty($testimonial['image_path'])): ?>
                                    <div class="testimonial-image">
                                        <img src="<?php echo !empty($testimonial['cloud_url']) ? htmlspecialchars($testimonial['cloud_url']) : '/assets/uploaded-images-testimonials/' . htmlspecialchars($testimonial['image_path']); ?>" 
                                            alt="<?php echo htmlspecialchars($testimonial['customer_name']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="testimonial-content">
                                    <h3 class="testimonial-name"><?php echo htmlspecialchars($testimonial['customer_name']); ?></h3>
                                    
                                    <?php if (!empty($testimonial['rating'])): ?>
                                        <div class="testimonial-rating">
                                            <?php 
                                            $rating = (int)$testimonial['rating'];
                                            for ($i = 0; $i < 5; $i++) {
                                                echo $i < $rating ? '⭐' : '☆';
                                            }
                                            ?>
                                            <span class="rating-text"><?php echo $rating; ?>/5</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p class="testimonial-text"><?php echo htmlspecialchars($testimonial['testimonial_text']); ?></p>
                                    <div class="testimonial-date"><?php echo date('M d, Y', strtotime($testimonial['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Quantity Modal (appears before adding to cart) -->
<div id="quantityModal" class="modal" style="display: none;">
    <div class="modal-content quantity-modal-content fade-in-pop">
        <span class="close" onclick="closeQuantityModal()">&times;</span>
        <div class="quantity-modal-body">
            <h2 id="quantityModalProductName">Product Name</h2>
            <p class="quantity-modal-price" id="quantityModalPrice"></p>
            
            <!-- Order Type Selector (shown only if product has both pre-order and same day order) -->
            <div id="orderTypeSelector" class="order-type-selector" style="display: none;">
                <label>Order Type:</label>
                <div class="order-type-buttons">
                    <label class="order-type-radio">
                        <input type="radio" name="orderType" value="preorder" checked onclick="selectOrderType('preorder')">
                        <span>Pre-Order</span>
                    </label>
                    <label class="order-type-radio">
                        <input type="radio" name="orderType" value="sameday" onclick="selectOrderType('sameday')">
                        <span>Same Day Order</span>
                    </label>
                </div>
            </div>
            
            <p class="quantity-modal-stock" id="quantityModalStock">Stock: 0</p>
            <p class="quantity-modal-date" id="quantityModalDate" style="display: none; font-size: 13px; color: #666; margin-top: 5px;">For: Today</p>
            
            <div class="quantity-modal-controls">
                <label>Quantity:</label>
                <div class="quantity-controls">
                    <button type="button" onclick="updateQuantityModalValue(-1)">-</button>
                    <input type="number" id="quantityModalInput" value="1" min="1" onchange="validateQuantityModalInput()">
                    <button type="button" onclick="updateQuantityModalValue(1)">+</button>
                </div>
            </div>
            
            <div class="quantity-modal-actions">
                <button class="btn-cancel" onclick="closeQuantityModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div id="productModal" class="modal" style="display: none;">
    <div class="modal-content fade-in-pop">
        <span class="close" onclick="closeProductModal()">&times;</span>
        <div class="modal-body">
            <div class="product-images">
                <div class="main-image">
                    <img id="modalMainImage" src="../../../assets/images/no-image.jpg" alt="Product Image">
                </div>
                <div class="thumbnail-container" id="thumbnailContainer">
                    <!-- Thumbnails will be added here dynamically -->
                </div>
            </div>
            <div class="product-details">
                <h2 class="modal-title" id="modalProductName"></h2>
                <p class="modal-price" id="modalProductPrice"></p>
                <div class="prdct-qty">
                    <span class="status-badge" id="modalProductStatus"></span>
                    <p class="stock" id="modalProductStock"></p>
                    <p class="available-days" id="modalProductAvailableDays" style="display: none;"></p>
                </div>
                <h3>Description:</h3>
                <div class="description" id="modalProductDescription"></div>
                <button class="add-to-cart" id="modalAddToCart" onclick="addToCartFromModal()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Check if user is logged in
    const isLoggedIn = <?= SessionManager::isUserLoggedIn() ? 'true' : 'false' ?>;
    const loginUrl = '/frontend/login/user/login-signup.php';
    
    // Search suggestions functionality
    let suggestionsTimeout;
    
    async function handleSearchInput() {
        const searchInput = document.getElementById('pageSearchInput');
        const suggestionsContainer = document.getElementById('suggestionsContainer');
        const query = searchInput.value.trim();
        
        // Clear previous timeout
        clearTimeout(suggestionsTimeout);
        
        if (query.length < 2) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        // Debounce the search
        suggestionsTimeout = setTimeout(() => {
            fetchSuggestions(query);
        }, 300);
    }
    
    async function fetchSuggestions(query) {
        const suggestionsContainer = document.getElementById('suggestionsContainer');
        
        try {
            const response = await fetch(`/frontend/search/get-suggestions.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.suggestions && data.suggestions.length > 0) {
                displaySuggestions(data.suggestions, query);
                suggestionsContainer.style.display = 'block';
            } else {
                suggestionsContainer.style.display = 'none';
            }
        } catch (error) {
            console.error('Error fetching suggestions:', error);
            suggestionsContainer.style.display = 'none';
        }
    }
    
    function displaySuggestions(suggestions, query) {
        const suggestionsContainer = document.getElementById('suggestionsContainer');
        suggestionsContainer.innerHTML = '';
        
        suggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            
            const icon = document.createElement('div');
            icon.className = 'suggestion-icon';
            icon.innerHTML = suggestion.type === 'product' 
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h12a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path><path d="M9 11h6"></path></svg>'
                : suggestion.type === 'blog'
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><path d="M12 7v6m3-3H9"></path></svg>';
            
            const text = document.createElement('div');
            text.className = 'suggestion-text';
            
            const title = document.createElement('div');
            title.className = 'suggestion-title';
            title.textContent = suggestion.name;
            
            const type = document.createElement('div');
            type.className = 'suggestion-type';
            type.textContent = suggestion.type === 'product' 
                ? 'Product' 
                : suggestion.type === 'blog'
                ? 'Blog Post'
                : 'Testimonial';
            
            text.appendChild(title);
            text.appendChild(type);
            
            item.appendChild(icon);
            item.appendChild(text);
            item.onclick = () => searchForSuggestion(suggestion.name);
            
            suggestionsContainer.appendChild(item);
        });
    }
    
    function searchForSuggestion(term) {
        document.getElementById('pageSearchInput').value = term;
        performSearch();
    }
    
    function handleSearchKeyPress(event) {
        if (event.key === 'Enter') {
            performSearch();
        }
    }
    
    function performSearch() {
        const query = document.getElementById('pageSearchInput').value.trim();
        if (query.length > 0) {
            window.location.href = `?query=${encodeURIComponent(query)}`;
        }
    }
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        const searchBox = document.querySelector('.search-box-container');
        const suggestionsContainer = document.getElementById('suggestionsContainer');
        
        if (!searchBox.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
    
    // Function to check login and redirect if needed
    function checkLoginAndRedirect() {
        if (!isLoggedIn) {
            alert('Please login to add items to cart');
            window.location.href = loginUrl;
            return false;
        }
        return true;
    }
    
    let currentProductModalData = null;
    let pendingCartProduct = null;

    function showConfirmation(message, isError = false) {
        const popup = document.getElementById('confirmationPopup');
        popup.textContent = message;
        popup.className = 'confirmation-popup' + (isError ? ' error' : ' success');
        popup.classList.add('show');
        
        setTimeout(() => {
            popup.classList.remove('show');
            popup.classList.add('hide');
            setTimeout(() => {
                popup.classList.remove('hide');
            }, 300);
        }, 3000);
    }

    function openProductModal(product) {
        try {
            if (!product || typeof product !== 'object') {
                console.error('Invalid product data:', product);
                return;
            }

            currentProductModalData = product; // Store for later use
            const modal = document.getElementById('productModal');
            const mainImage = document.getElementById('modalMainImage');
            const thumbnails = document.getElementById('thumbnailContainer');
            const productName = document.getElementById('modalProductName');
            const productPrice = document.getElementById('modalProductPrice');
            const productStatus = document.getElementById('modalProductStatus');
            const productDescription = document.getElementById('modalProductDescription');
            const productStock = document.getElementById('modalProductStock');
            const productAvailableDays = document.getElementById('modalProductAvailableDays');
            const addToCartBtn = document.getElementById('modalAddToCart');

            // Set main content
            productName.textContent = product.name || 'Unknown Product';
            productPrice.textContent = '₱' + (parseFloat(product.price) || 0).toFixed(2);
            productStatus.textContent = product.status || 'Available Today';
            productStatus.className = 'status-badge status-' + (product.status || '').toLowerCase().replace(' ', '-');
            productDescription.textContent = product.description || 'No description available';
            productStock.textContent = 'Stock: ' + (product.quantity || 0);

            // Handle available dates in modal
            const availableDates = product.status_id == 4 ? product.todays_product_dates : product.regular_today_dates;
            if (availableDates && availableDates.length > 0) {
                // Format dates for display (e.g., "8/27, 8/28, 8/29")
                const formattedDates = availableDates.map(date => {
                    const dateObj = new Date(date + 'T00:00:00'); // Add time to avoid timezone issues
                    return (dateObj.getMonth() + 1) + '/' + dateObj.getDate(); // Format as M/D
                });
                productAvailableDays.textContent = 'Available: ' + formattedDates.join(', ');
                productAvailableDays.style.display = 'block';
            } else {
                productAvailableDays.style.display = 'none';
            }

            // Set up images - handle both Cloudinary URLs and local paths
            if (product.images && Array.isArray(product.images) && product.images.length > 0) {
                // Helper function to get proper image path
                const getImagePath = (img) => {
                    if (img.startsWith('http://') || img.startsWith('https://')) {
                        return img; // It's a full URL (Cloudinary)
                    }
                    return '/assets/' + img; // It's a local path
                };
                
                mainImage.src = getImagePath(product.images[0]);
                thumbnails.innerHTML = '';
                product.images.forEach((image, index) => {
                    if (image) {
                        const thumb = document.createElement('img');
                        thumb.src = getImagePath(image);
                        thumb.alt = `${product.name || 'Product'} view ${index + 1}`;
                        thumb.onclick = () => mainImage.src = thumb.src;
                        thumbnails.appendChild(thumb);
                    }
                });
            } else {
                mainImage.src = '/assets/images/no-image.jpg';
                thumbnails.innerHTML = '';
            }

            // Check if product is unavailable using consistent logic
            let isUnavailable = false;
            let unavailableReason = '';
            
            const preorderStock = product.quantity || 0;
            const samedayStock = product.sameday_stock_today || 0;
            const hasAvailtoday = product.availtoday_status_id != null && product.availtoday_status_id != '';
            const todaysProductDates = product.todays_product_dates || [];
            const regularTodayDates = product.regular_today_dates || [];
            const todayDate = new Date().toISOString().split('T')[0]; // Get today's date in Y-m-d format
            
            // Step 1: Determine product capabilities
            let hasPreorder = false;
            let hasSameday = false;
            
            // Pre-order capability
            if ([1, 2, 3].includes(product.status_id)) {
                hasPreorder = preorderStock > 0;
            }
            
            // Same-day capability for status 4 (Same Day Only)
            if (product.status_id == 4) {
                const hasValidDate = todaysProductDates.includes(todayDate);
                hasSameday = (samedayStock > 0) && hasValidDate;
            }
            
            // Same-day capability for dual products (status 1/2/3 with availtoday)
            if ([1, 2, 3].includes(product.status_id) && hasAvailtoday) {
                const hasValidDate = regularTodayDates.includes(todayDate);
                hasSameday = (samedayStock > 0) && hasValidDate;
            }
            
            // Step 2: Determine availability
            isUnavailable = !hasPreorder && !hasSameday;
            
            if (isUnavailable) {
                if (product.status_id == 4) {
                    if (samedayStock <= 0) {
                        unavailableReason = 'Out of Stock';
                    } else {
                        unavailableReason = 'Not Available Today';
                    }
                } else if ([1, 2, 3].includes(product.status_id) && hasAvailtoday) {
                    unavailableReason = 'Out of Stock';
                } else {
                    unavailableReason = 'Out of Stock';
                }
            }
            
            // Set up Add to Cart button
            if (isUnavailable) {
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = 'Unavailable - ' + unavailableReason;
                addToCartBtn.classList.add('unavailable-btn');
                addToCartBtn.onclick = null;
            } else {
                addToCartBtn.disabled = false;
                addToCartBtn.textContent = 'Add to Cart';
                addToCartBtn.classList.remove('unavailable-btn');
                addToCartBtn.onclick = () => addToCartFromModal();
            }

            modal.classList.add('show');
        } catch (error) {
            console.error('Error in openProductModal:', error);
            showConfirmation('An error occurred while opening the product details', true);
        }
    }

    function closeProductModal() {
        const modal = document.getElementById('productModal');
        modal.classList.remove('show');
    }

    // Open product modal from data attribute
    window.openProductModalFromData = function(element) {
        const productData = JSON.parse(element.getAttribute('data-product'));
        openProductModal(productData);
    };

    // Add to cart function - Opens quantity modal
    function addToCart(productId, button) {
        // Check if user is logged in
        if (!checkLoginAndRedirect()) {
            return;
        }
        
        const productCard = button.closest('.product-card');
        if (!productCard) {
            console.error('Product card not found');
            return;
        }
        
        const productData = JSON.parse(productCard.getAttribute('data-product'));
        const productName = productData.name;
        const productPrice = '₱' + parseFloat(productData.price).toFixed(2);
        const productStock = productData.quantity;
        const statusId = productData.status_id;
        const availtodayStatusId = productData.availtoday_status_id;
        
        // Store product info for later
        pendingCartProduct = {
            id: productId,
            name: productName,
            price: productPrice,
            stock: productStock,
            button: button,
            statusId: statusId,
            availtodayStatusId: availtodayStatusId,
            selectedOrderType: 'preorder' // Default
        };
        
        // Open quantity modal with order type options
        openQuantityModalWithOrderType(productName, productPrice, productStock, statusId, availtodayStatusId);
    }

    // Add to cart from modal - Opens quantity modal
    function addToCartFromModal() {
        // Check if user is logged in
        if (!checkLoginAndRedirect()) {
            return;
        }
        
        if (!currentProductModalData) {
            console.error('No product data available');
            return;
        }
        
        const productName = currentProductModalData.name;
        const productPrice = '₱' + parseFloat(currentProductModalData.price).toFixed(2);
        const productStock = currentProductModalData.quantity;
        const statusId = currentProductModalData.status_id;
        const availtodayStatusId = currentProductModalData.availtoday_status_id;
        
        // Store product info for later
        pendingCartProduct = {
            id: currentProductModalData.id,
            name: productName,
            price: productPrice,
            stock: productStock,
            button: null,
            statusId: statusId,
            availtodayStatusId: availtodayStatusId,
            selectedOrderType: 'preorder' // Default
        };
        
        // Close product modal first
        closeProductModal();
        
        // Open quantity modal with order type options
        openQuantityModalWithOrderType(productName, productPrice, productStock, statusId, availtodayStatusId);
    }

    // Check if same-day option should be available
    async function checkSameDayAvailability(productId) {
        try {
            const response = await fetch(`../pages/products/get-sdo-quantity.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                const todayQuantity = data.quantity || 0;
                const hasDateToday = data.has_date_today || false;
                return hasDateToday && todayQuantity > 0;
            }
            return false;
        } catch (error) {
            console.error('Error checking same-day availability:', error);
            return false;
        }
    }

    // Check if business is currently closed
    function isBusinessClosed() {
        const currentTime = new Date();
        const currentHours = String(currentTime.getHours()).padStart(2, '0');
        const currentMinutes = String(currentTime.getMinutes()).padStart(2, '0');
        const currentTimeStr = currentHours + ':' + currentMinutes;
        
        // Get business hours from page meta tags
        const openingTime = document.querySelector('meta[name="business-opening-time"]')?.getAttribute('content') || '08:00';
        const closingTime = document.querySelector('meta[name="business-closing-time"]')?.getAttribute('content') || '21:00';
        
        // If closing time < opening time (e.g., 21:00 vs 08:00), it wraps past midnight
        // Handle wrapping: if current time >= opening time OR < closing time (from previous day), business is open
        if (closingTime < openingTime) {
            // Business hours wrap past midnight (e.g., 22:00 to 02:00)
            return !(currentTimeStr >= openingTime || currentTimeStr < closingTime);
        } else {
            // Normal business hours (e.g., 08:00 to 21:00)
            return !(currentTimeStr >= openingTime && currentTimeStr < closingTime);
        }
    }

    // Open quantity modal with order type selection
    async function openQuantityModalWithOrderType(productName, productPrice, productStock, statusId, availtodayStatusId) {
        const modal = document.getElementById('quantityModal');
        document.getElementById('quantityModalProductName').textContent = productName;
        document.getElementById('quantityModalPrice').textContent = productPrice;
        
        const quantityInput = document.getElementById('quantityModalInput');
        const orderTypeSelector = document.getElementById('orderTypeSelector');
        const dateDisplay = document.getElementById('quantityModalDate');
        const samedayRadio = document.querySelector('input[name="orderType"][value="sameday"]');
        const preorderRadio = document.querySelector('input[name="orderType"][value="preorder"]');
        
        // Check if business is currently closed
        const businessClosed = isBusinessClosed();
        
        // Show modal first
        modal.style.display = 'flex';
        
        // Show loading state
        setQuantityModalLoading(true);
        
        // Determine which order types to show based on product configuration and stock availability
        // Status 1, 2, 3 = Pre-order products
        // Status 4 = Same Day Order products
        
        if ((statusId == 1 || statusId == 2 || statusId == 3) && availtodayStatusId) {
            // Product has BOTH pre-order and same day order capability
            // Fetch both stock quantities to determine what to show
            
            const preorderQty = await fetchPreOrderQuantityValue(pendingCartProduct.id);
            const samedayQty = await fetchTodayQuantityValue(pendingCartProduct.id);
            
            // Determine which options are available based on stock
            const hasPreorderStock = preorderQty > 0;
            const hasSamedayStock = samedayQty > 0;
            
            if (hasPreorderStock && hasSamedayStock && !businessClosed) {
                // BOTH have stock AND business is open: Show order type selector
                orderTypeSelector.style.display = 'block';
                samedayRadio.disabled = false;
                preorderRadio.disabled = false;
                dateDisplay.style.display = 'none';
                
                // Default to pre-order
                await fetchPreOrderQuantity(pendingCartProduct.id);
                selectOrderType('preorder');
            } else if (hasSamedayStock && !hasPreorderStock && !businessClosed) {
                // ONLY same-day has stock AND business is open: Show same-day only
                orderTypeSelector.style.display = 'none';
                dateDisplay.style.display = 'block';
                dateDisplay.textContent = 'For: Today';
                pendingCartProduct.selectedOrderType = 'sameday';
                
                await fetchTodayQuantity(pendingCartProduct.id);
            } else if (hasPreorderStock && !hasSamedayStock) {
                // ONLY pre-order has stock: Show pre-order only
                orderTypeSelector.style.display = 'none';
                dateDisplay.style.display = 'none';
                pendingCartProduct.selectedOrderType = 'preorder';
                
                await fetchPreOrderQuantity(pendingCartProduct.id);
            } else if (businessClosed && hasPreorderStock) {
                // Business is closed BUT pre-order has stock: Show pre-order only with same-day disabled
                orderTypeSelector.style.display = 'block';
                samedayRadio.disabled = true;
                preorderRadio.disabled = false;
                preorderRadio.checked = true;
                samedayRadio.checked = false;
                dateDisplay.style.display = 'none';
                pendingCartProduct.selectedOrderType = 'preorder';
                
                await fetchPreOrderQuantity(pendingCartProduct.id);
            } else {
                // NEITHER has stock or no valid combination: Default to pre-order view
                orderTypeSelector.style.display = 'none';
                dateDisplay.style.display = 'none';
                pendingCartProduct.selectedOrderType = 'preorder';
                
                await fetchPreOrderQuantity(pendingCartProduct.id);
            }
        } else if (statusId == 4) {
            // Same Day Order ONLY
            orderTypeSelector.style.display = 'block';
            samedayRadio.disabled = businessClosed;
            preorderRadio.disabled = true;
            
            if (businessClosed) {
                // Business is closed - disable same-day option
                samedayRadio.checked = false;
                dateDisplay.style.display = 'none';
                document.getElementById('quantityModalStock').textContent = 'Same-day orders not available now';
            } else {
                // Business is open - enable same-day
                samedayRadio.checked = true;
                dateDisplay.style.display = 'block';
                dateDisplay.textContent = 'For: Today';
                pendingCartProduct.selectedOrderType = 'sameday';
                
                await fetchTodayQuantity(pendingCartProduct.id);
            }
        } else {
            // Pre-order ONLY (status 1, 2, or 3 without availtoday_status_id)
            orderTypeSelector.style.display = 'none';
            dateDisplay.style.display = 'none';
            pendingCartProduct.selectedOrderType = 'preorder';
            
            await fetchPreOrderQuantity(pendingCartProduct.id);
        }
        
        // Remove loading state
        setQuantityModalLoading(false);
    }

    function setQuantityModalLoading(isLoading) {
        const modal = document.getElementById('quantityModal');
        const modalContent = modal.querySelector('.modal-content');
        const quantityInput = document.getElementById('quantityModalInput');
        const buttons = modal.querySelectorAll('button');
        
        if (isLoading) {
            // Add loading overlay
            if (!modal.querySelector('.modal-loading-overlay')) {
                const overlay = document.createElement('div');
                overlay.className = 'modal-loading-overlay';
                overlay.innerHTML = '<div class="loading-spinner"></div>';
                overlay.style.cssText = 'position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; z-index: 10; border-radius: 8px;';
                modalContent.style.position = 'relative';
                modalContent.appendChild(overlay);
            }
            
            // Disable inputs and buttons
            quantityInput.disabled = true;
            buttons.forEach(btn => btn.disabled = true);
        } else {
            // Remove loading overlay
            const overlay = modal.querySelector('.modal-loading-overlay');
            if (overlay) {
                overlay.remove();
            }
            
            // Enable inputs and buttons
            quantityInput.disabled = false;
            buttons.forEach(btn => btn.disabled = false);
        }
    }

    // Select order type
    async function selectOrderType(type) {
        // Update radio button selection
        const radioButtons = document.querySelectorAll('input[name="orderType"]');
        radioButtons.forEach(radio => {
            radio.checked = (radio.value === type);
        });
        
        const dateDisplay = document.getElementById('quantityModalDate');
        const stockDisplay = document.getElementById('quantityModalStock');
        
        if (type === 'sameday') {
            dateDisplay.style.display = 'block';
            dateDisplay.textContent = 'For: Today';
            
            // Fetch today's specific quantity from quantity_per_day_sdo table
            if (pendingCartProduct) {
                setQuantityModalLoading(true);
                await fetchTodayQuantity(pendingCartProduct.id);
                setQuantityModalLoading(false);
            }
        } else {
            dateDisplay.style.display = 'none';
            
            if (pendingCartProduct) {
                setQuantityModalLoading(true);
                await fetchPreOrderQuantity(pendingCartProduct.id);
                setQuantityModalLoading(false);
            }
        }
        
        if (pendingCartProduct) {
            pendingCartProduct.selectedOrderType = type;
        }
    }

    // Fetch pre-order quantity value only (no UI update) - for checking availability
    async function fetchPreOrderQuantityValue(productId) {
        try {
            const response = await fetch(`../pages/products/get-preorder-quantity.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                return data.quantity || 0;
            }
            return 0;
        } catch (error) {
            console.error('Error fetching pre-order quantity:', error);
            return 0;
        }
    }
    
    // Fetch today's quantity value only (no UI update) - for checking availability
    async function fetchTodayQuantityValue(productId) {
        try {
            const response = await fetch(`../pages/products/get-sdo-quantity.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                const todayQuantity = data.quantity || 0;
                const hasDateToday = data.has_date_today || false;
                
                // Only return quantity if date is configured for today
                return hasDateToday ? todayQuantity : 0;
            }
            return 0;
        } catch (error) {
            console.error('Error fetching today quantity:', error);
            return 0;
        }
    }
    
    // Fetch pre-order quantity from products.quantity (with UI update)
    async function fetchPreOrderQuantity(productId) {
        const stockDisplay = document.getElementById('quantityModalStock');
        const confirmBtn = document.querySelector('.btn-confirm');
        stockDisplay.textContent = 'Loading...';
        
        try {
            const response = await fetch(`../pages/products/get-preorder-quantity.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                const totalStock = data.quantity || 0;
                const cartQuantity = data.cart_quantity || 0;
                const availableToAdd = Math.max(0, totalStock - cartQuantity);
                
                stockDisplay.textContent = `Stock: ${totalStock}${cartQuantity > 0 ? ` (${cartQuantity} in cart, ${availableToAdd} available)` : ''}`;
                
                const quantityInput = document.getElementById('quantityModalInput');
                quantityInput.max = availableToAdd;
                quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, Math.max(availableToAdd, 1));
                
                // Update pendingCartProduct with fresh stock value
                if (pendingCartProduct) {
                    pendingCartProduct.stock = availableToAdd;
                }
                
                // Disable/enable Add to Cart button based on available stock
                if (availableToAdd === 0) {
                    stockDisplay.textContent = cartQuantity > 0 
                        ? `Stock: ${totalStock} (All ${cartQuantity} already in cart)` 
                        : 'Stock: 0 (Out of stock)';
                    confirmBtn.disabled = true;
                    quantityInput.disabled = true;
                } else {
                    confirmBtn.disabled = false;
                    quantityInput.disabled = false;
                }
            } else {
                console.error('Error fetching pre-order quantity:', data.error);
                stockDisplay.textContent = 'Stock: 0';
                confirmBtn.disabled = true;
                quantityInput.disabled = true;
            }
        } catch (error) {
            console.error('Error:', error);
            stockDisplay.textContent = 'Stock: 0';
            confirmBtn.disabled = true;
            quantityInput.disabled = true;
        }
    }

    // Fetch today's specific quantity for same day orders
    async function fetchTodayQuantity(productId) {
        const stockDisplay = document.getElementById('quantityModalStock');
        const confirmBtn = document.querySelector('.btn-confirm');
        stockDisplay.textContent = 'Loading...';
        
        try {
            const response = await fetch(`../pages/products/get-sdo-quantity.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                const totalStock = data.quantity || 0;
                const cartQuantity = data.cart_quantity || 0;
                const availableToAdd = Math.max(0, totalStock - cartQuantity);
                
                stockDisplay.textContent = `Stock: ${totalStock}${cartQuantity > 0 ? ` (${cartQuantity} in cart, ${availableToAdd} available)` : ''}`;
                
                const quantityInput = document.getElementById('quantityModalInput');
                quantityInput.max = availableToAdd;
                quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, Math.max(availableToAdd, 1));
                
                // Disable/enable Add to Cart button based on available stock
                if (availableToAdd === 0) {
                    stockDisplay.textContent = cartQuantity > 0 
                        ? `Stock: ${totalStock} (All ${cartQuantity} already in cart)` 
                        : 'Stock: 0 (Not available today)';
                    confirmBtn.disabled = true;
                    quantityInput.disabled = true;
                } else {
                    confirmBtn.disabled = false;
                    quantityInput.disabled = false;
                }
            } else {
                console.error('Error fetching today quantity:', data.error);
                stockDisplay.textContent = 'Stock: 0';
                confirmBtn.disabled = true;
                quantityInput.disabled = true;
            }
        } catch (error) {
            console.error('Error:', error);
            stockDisplay.textContent = 'Stock: 0';
            confirmBtn.disabled = true;
            quantityInput.disabled = true;
        }
    }

    // Close quantity modal
    function closeQuantityModal() {
        document.getElementById('quantityModal').style.display = 'none';
        pendingCartProduct = null;
    }

    // Update quantity value
    function updateQuantityModalValue(change) {
        const input = document.getElementById('quantityModalInput');
        const currentValue = parseInt(input.value) || 1;
        const newValue = currentValue + change;
        const maxValue = parseInt(input.max);
        
        if (newValue >= 1 && newValue <= maxValue) {
            input.value = newValue;
        }
    }

    // Validate quantity input
    function validateQuantityModalInput() {
        const input = document.getElementById('quantityModalInput');
        const value = parseInt(input.value) || 1;
        const maxValue = parseInt(input.max);
        
        if (value < 1) {
            input.value = 1;
        } else if (value > maxValue) {
            input.value = maxValue;
        }
    }

    // Confirm and add to cart
    function confirmAddToCart() {
        if (!pendingCartProduct) return;
        
        const quantity = parseInt(document.getElementById('quantityModalInput').value) || 1;
        const confirmBtn = document.querySelector('.quantity-modal-actions .btn-confirm');
        
        if (!confirmBtn) return;
        
        // Store original button text
        const originalText = confirmBtn.textContent;
        
        // Disable button and show loading state
        confirmBtn.disabled = true;
        confirmBtn.style.opacity = '0.7';
        confirmBtn.style.cursor = 'not-allowed';
        confirmBtn.innerHTML = '<span class="loading-spinner-small"></span> Adding to cart...';
        
        // Determine which cart to add to based on selectedOrderType
        const orderType = pendingCartProduct.selectedOrderType || 'preorder';
        
        // Determine API endpoint and body based on order type
        let apiUrl, requestBody;
        
        if (orderType === 'sameday') {
            // Add to same-day cart (availtoday_cart table)
            apiUrl = '../pages/cart/availtoday-cart-api.php';
            requestBody = `action=add&product_id=${pendingCartProduct.id}&quantity=${quantity}`;
        } else {
            // Add to pre-order cart (cart table)
            apiUrl = '../pages/cart/add-to-cart.php';
            requestBody = `product_id=${pendingCartProduct.id}&quantity=${quantity}`;
        }
        
        // Add to appropriate cart
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success state
                confirmBtn.innerHTML = '<span class="success-icon">✓</span> Product added!';
                confirmBtn.style.backgroundColor = '#4CAF50';
                
                // Show confirmation message
                const orderTypeText = orderType === 'sameday' ? '(Same Day)' : '(Pre-order)';
                showConfirmation(`Added ${quantity} ${pendingCartProduct.name} to cart ${orderTypeText}`, false);
                
                // Close modal after short delay
                setTimeout(() => {
                    closeQuantityModal();
                    
                    // Reset button state
                    confirmBtn.disabled = false;
                    confirmBtn.style.opacity = '1';
                    confirmBtn.style.cursor = 'pointer';
                    confirmBtn.textContent = originalText;
                    confirmBtn.style.backgroundColor = '';
                }, 800);
            } else {
                console.error('Failed to add to cart:', data.message || data.error);
                showConfirmation(data.message || data.error || 'Failed to add to cart', true);
                
                // Reset button on error
                confirmBtn.disabled = false;
                confirmBtn.style.opacity = '1';
                confirmBtn.style.cursor = 'pointer';
                confirmBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            showConfirmation('Error adding to cart', true);
            
            // Reset button on error
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
            confirmBtn.style.cursor = 'pointer';
            confirmBtn.textContent = originalText;
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const productModal = document.getElementById('productModal');
        const quantityModal = document.getElementById('quantityModal');
        
        if (event.target === productModal) {
            closeProductModal();
        }
        if (event.target === quantityModal) {
            closeQuantityModal();
        }
    });
</script>

<style>
    /* Order Type Radio Buttons */

/* Order Type Radio Buttons */
.order-type-radio {
  display: inline-flex;
  align-items: center;
  text-align: left;
  cursor: pointer;
  padding: 8px 20px;
}

.order-type-radio:hover {
  border-color: #2d5016;
}

.order-type-radio input[type="radio"] {
  margin: 0 8px 0 0;
  cursor: pointer;
  width: 18px;
  height: 18px;
  accent-color: #2d5016;
  flex-shrink: 0;
  vertical-align: middle;
}

.order-type-radio span {
  line-height: 18px;
  vertical-align: middle;
}

.order-type-radio input[type="radio"]:checked + span {
  font-weight: 600;
  color: #2d5016;
}

.order-type-radio input[type="radio"]:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.order-type-radio:has(input[type="radio"]:disabled) {
  opacity: 0.5;
  cursor: not-allowed;
}

.order-type-buttons {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin: 10px 0;
}
    
    /* Loading spinner for add to cart button */
    .loading-spinner-small {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-right: 8px;
        vertical-align: middle;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Success icon */
    .success-icon {
        display: inline-block;
        margin-right: 6px;
        font-size: 16px;
        font-weight: bold;
    }
    
    /* Button disabled state */
    .btn-confirm:disabled {
        cursor: not-allowed !important;
        opacity: 0.7 !important;
    }
    
    /* Confirmation Popup */
    .confirmation-popup {
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%) translateY(-100px);
        background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(76, 175, 80, 0.3);
        z-index: 10000;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        font-weight: 500;
        font-size: 15px;
        min-width: 280px;
        text-align: center;
    }

    .confirmation-popup.success {
        background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        box-shadow: 0 8px 32px rgba(76, 175, 80, 0.3);
    }

    .confirmation-popup.error {
        background: linear-gradient(135deg, #f44336 0%, #e53935 100%);
        box-shadow: 0 8px 32px rgba(244, 67, 54, 0.3);
    }

    .confirmation-popup.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .confirmation-popup.hide {
        opacity: 0;
        transform: translateX(-50%) translateY(-100px);
    }
</style>
</script>

<?php
// Include the footer
require_once __DIR__ . "/../user-includes/user-footer.php";
?>