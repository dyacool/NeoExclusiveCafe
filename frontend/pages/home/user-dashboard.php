<?php
$page_title = "Dashboard";

// Include session management files first (before any HTML output)
require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../../includes/session-manager.php";
require_once __DIR__ . "/../../user-includes/user-header.php";
require_once __DIR__ . "/../../../backend/pages/products/todays-products-handler.php";

// Clean up past dates automatically when page loads
try {
    cleanupPastDates();
    // Also clean up today's dates if business hours have closed
    cleanupTodaysDatesAfterBusinessHours();
} catch (Exception $e) {
    error_log("Error cleaning up dates: " . $e->getMessage());
}

// Include navigation after session is established
require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";

// Get business hours for same-day availability check
$business_hours = null;
if ($conn) {
    $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $business_hours_result = $conn->query($business_hours_query);
    if ($business_hours_result && $business_hours_result->num_rows > 0) {
        $business_hours = $business_hours_result->fetch_assoc();
    }
}
?>

<meta name="business-opening-time" content="<?php echo $business_hours['opening_time'] ?? '08:00'; ?>">
<meta name="business-closing-time" content="<?php echo $business_hours['closing_time'] ?? '21:00'; ?>">

<link rel="stylesheet" href="/frontend/pages/home/user-dashboard.css">
<link rel="stylesheet" href="/frontend/pages/home/coupons.css">
<link rel="stylesheet" href="/frontend/pages/home/product-dashboard.css">
<link rel="stylesheet" href="/frontend/pages/home/hero-carousel.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize AOS animation library
        AOS.init({
            duration: 800,
            once: true
        });

        // Hero carousel simple slide transition
        let heroSlideIndex = 0;
        const heroSlides = document.querySelectorAll('.hero-slide');
        
        function showHeroSlides() {
            // Remove active class from all slides
            heroSlides.forEach(slide => slide.classList.remove('active'));
            
            // Add active class to current slide
            heroSlides[heroSlideIndex].classList.add('active');
            
            // Move to next slide
            heroSlideIndex = (heroSlideIndex + 1) % heroSlides.length;
        }
        
        // Initialize first slide
        if (heroSlides.length > 0) {
            heroSlides[0].classList.add('active');
            setInterval(showHeroSlides, 5000);
        }

        // Featured products animation with Intersection Observer
        const productCards = document.querySelectorAll('.product-card.featured-product');
        
        // Add animation classes with delays
        productCards.forEach((card, index) => {
            card.classList.add(`fade-up-${index + 1}`);
        });
        
        // Create intersection observer for product cards
        const productObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    productObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        // Observe each product card
        productCards.forEach(card => {
            productObserver.observe(card);
        });
    });
</script>

<?php
// Get today's date for availability checking
$today_date = date('Y-m-d');

// Get all featured products with comprehensive status information
$featured_query = "SELECT 
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
                WHERE p.is_featured = 1 
                AND p.deleted_at IS NULL 
                AND p.id > 0 
                AND p.status_id IN (1, 2, 3, 4)
                GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id, ps.name, pi.cloud_url, pi.image_url, p.quantity, p.show_when_unavailable, p.hide_when_unavailable, p.availtoday_status_id, ats.name, c.name, qpd.quantity
                ORDER BY p.is_featured DESC, p.name ASC";

$featured_stmt = mysqli_prepare($conn, $featured_query);
mysqli_stmt_execute($featured_stmt);
$featured_result = mysqli_stmt_get_result($featured_stmt);

/**
 * Determine product availability based on stock levels, date availability, and visibility flags
 * 
 * @param array $product_row Product data from database query
 * @param string $today_date Current date in Y-m-d format
 * @return array Availability information with keys: has_preorder, has_sameday, is_unavailable, unavailable_reason, should_display
 */
function determineProductAvailability($product_row, $today_date) {
    // Initialize result structure with default values
    $result = [
        'has_preorder' => false,      // Can customer pre-order this product?
        'has_sameday' => false,       // Can customer order for same-day delivery?
        'is_unavailable' => false,    // Is product completely unavailable?
        'unavailable_reason' => '',   // Why is it unavailable (if applicable)?
        'should_display' => true      // Should we show this product on the page?
    ];
    
    // Extract and normalize product data from database row
    $status_id = $product_row['status_id'];                                      // Product status: 1=Regular, 2=Featured, 3=Limited, 4=Same-Day Only
    $preorder_stock = $product_row['quantity'] ?? 0;                            // Stock available for pre-orders (products.quantity)
    $sameday_stock = $product_row['sameday_stock_today'] ?? 0;                  // Stock available for same-day orders today (quantity_per_day_sdo.quantity)
    $has_availtoday_config = !empty($product_row['availtoday_status_id']);      // Does product have same-day configuration?
    $todays_dates = $product_row['todays_product_dates'] ? explode(', ', $product_row['todays_product_dates']) : [];  // Dates for status 4 products
    $regular_dates = $product_row['regular_today_dates'] ? explode(', ', $product_row['regular_today_dates']) : [];   // Dates for status 1/2/3 products with same-day
    $show_when_unavailable = (bool)($product_row['show_when_unavailable'] ?? 0); // Force show even when unavailable?
    $hide_when_unavailable = (bool)($product_row['hide_when_unavailable'] ?? 0); // Force hide when unavailable?
    
    // ============================================================================
    // STEP 1: Determine product capabilities (per system documentation)
    // ============================================================================
    
    // Pre-order capability check:
    // Products with status 1/2/3 can be pre-ordered if they have stock in products.quantity
    // Status 4 products are same-day only and CANNOT be pre-ordered
    $result['has_preorder'] = in_array($status_id, [1, 2, 3]) && $preorder_stock > 0;
    
    // Same-day capability check for pure same-day products (status 4):
    // Requirements: stock in quantity_per_day_sdo AND today's date in todays_products_dates
    if ($status_id == 4) {
        $has_valid_date = in_array($today_date, $todays_dates);
        $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
    }
    
    // Same-day capability check for dual-capability products (status 1/2/3 with availtoday config):
    // Requirements: has availtoday_status_id set, stock in quantity_per_day_sdo, AND today's date in regular_products_today_dates
    if (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
        $has_valid_date = in_array($today_date, $regular_dates);
        $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
    }
    
    // ============================================================================
    // STEP 2: Determine availability
    // ============================================================================
    
    // A product is unavailable ONLY if it has NO capabilities at all
    $result['is_unavailable'] = !$result['has_preorder'] && !$result['has_sameday'];
    
    if ($result['is_unavailable']) {
        // Determine the specific reason for unavailability based on product type
        if ($status_id == 4) {
            // Same-day only product (status 4) unavailability reasons:
            if ($sameday_stock <= 0 || $sameday_stock === null) {
                $result['unavailable_reason'] = 'Out of Stock';
            } else {
                $result['unavailable_reason'] = 'Not Available Today';
            }
        } elseif (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
            // Dual-capability product: both stocks are depleted
            $result['unavailable_reason'] = 'Out of Stock';
        } else {
            // Pre-order only product: no pre-order stock available
            $result['unavailable_reason'] = 'Out of Stock';
        }
    }
    
    // ============================================================================
    // STEP 3: Apply visibility rules
    // ============================================================================
    
    if ($result['is_unavailable']) {
        // Visibility priority hierarchy:
        // 1. hide_when_unavailable = 1: ALWAYS hide when unavailable (highest priority)
        // 2. show_when_unavailable = 1: ALWAYS show even when unavailable (medium priority)
        // 3. Default: hide unavailable products (lowest priority)
        
        if ($hide_when_unavailable) {
            $result['should_display'] = false;
        } elseif ($show_when_unavailable) {
            $result['should_display'] = true;
        } else {
            $result['should_display'] = false;
        }
    } else {
        // Available products are always displayed regardless of visibility flags
        $result['should_display'] = true;
    }
    
    return $result;
}

// Get today's date for availability checks
$today_date = date('Y-m-d');

// Fetch all featured products into an array for custom sorting
$all_featured_products = [];
while ($row = mysqli_fetch_assoc($featured_result)) {
    // Check product availability and visibility
    $availability = determineProductAvailability($row, $today_date);
    
    // Skip products that should not be displayed
    if (!$availability['should_display']) {
        continue;
    }
    
    // Add availability info to product data
    $row['is_unavailable'] = $availability['is_unavailable'];
    $row['unavailable_reason'] = $availability['unavailable_reason'];
    
    $all_featured_products[] = $row;
}

// Custom sort: Priority hierarchy - Available > Unavailable
usort($all_featured_products, function($a, $b) use ($today_date) {
    // Use pre-calculated unavailability flags
    $a_unavailable = $a['is_unavailable'] ?? false;
    $b_unavailable = $b['is_unavailable'] ?? false;
    
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
    
    // Priority 0: Unavailable products go to the end
    if ($a_unavailable && !$b_unavailable) return 1;
    if (!$a_unavailable && $b_unavailable) return -1;
    
    // Priority 1: Available today (highest priority for available products)
    if ($a_available_today && !$b_available_today) return -1;
    if (!$a_available_today && $b_available_today) return 1;
    
    // Priority 2: Alphabetical by name
    return strcmp($a['name'], $b['name']);
});

// Get all images for each product
$featured_products_data = [];
foreach ($all_featured_products as $product) {
    // Get all images for this product
    $images_sql = "SELECT COALESCE(cloud_url, image_url) as image_url FROM product_images WHERE product_id = ?";
    $images_stmt = mysqli_prepare($conn, $images_sql);
    mysqli_stmt_bind_param($images_stmt, "i", $product['id']);
    mysqli_stmt_execute($images_stmt);
    $images_result = mysqli_stmt_get_result($images_stmt);
    $images = [];
    while ($image = mysqli_fetch_assoc($images_result)) {
        $images[] = $image['image_url'];
    }
    
    $product['images'] = $images;
    $featured_products_data[] = $product;
}

$total_featured = count($featured_products_data);

// Debug information
error_log("Total featured products found: " . $total_featured);

// Get active promotions/coupons
$today = date('Y-m-d');
$promotions_query = "SELECT 
                        id, title, code, type, value, min_purchase, 
                        activation_date, expiration_date, status
                    FROM promotions 
                    WHERE status = 'active' 
                    AND activation_date <= ? 
                    AND expiration_date >= ?
                    ORDER BY created_at DESC";

$promotions_stmt = mysqli_prepare($conn, $promotions_query);
mysqli_stmt_bind_param($promotions_stmt, "ss", $today, $today);
mysqli_stmt_execute($promotions_stmt);
$promotions_result = mysqli_stmt_get_result($promotions_stmt);

$active_promotions = [];
while ($promo = mysqli_fetch_assoc($promotions_result)) {
    $active_promotions[] = $promo;
}

// Get carousel settings
$settings_query = "SELECT * FROM carousel_settings LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);
$carousel_settings = mysqli_fetch_assoc($settings_result);

// Get carousel images - prioritize Cloudinary URLs
$images_query = "SELECT id, COALESCE(cloud_url, image_url) as image_url, title, display_order, is_active FROM carousel_images WHERE is_active = 1 ORDER BY display_order ASC";
$images_result = mysqli_query($conn, $images_query);
$total_images = mysqli_num_rows($images_result);
?>

<main class="Main">
    <div id="confirmationPopup" class="confirmation-popup"></div>
    
    <div class="dashboard-container">
        <!-- Hero Carousel Section -->
        <section class="hero-carousel-section">
        <?php if ($total_images > 0): ?>
            <div class="carousel-content">
                <h2 class="carousel-title" data-aos="fade-down"><?php echo htmlspecialchars($carousel_settings['title']); ?></h2>
                <div class="dscrptn" data-aos="fade-right">
                    <?php 
                        $description = $carousel_settings['description'];
                        $words = explode(' ', $description);
                        $chunks = array_chunk($words, 12);
                        foreach ($chunks as $chunk) {
                            echo '<p class="carousel-description" style="margin-bottom: 6px;">' . htmlspecialchars(implode(' ', $chunk)) . '</p>';
                        }
                    ?>
                </div>
                <div class="button-link" data-aos="fade-up">
                    <?php
                    $button_link = $carousel_settings['button_link'];
                    // Ensure the link starts with / for absolute path from root
                    if (!empty($button_link) && strpos($button_link, '/') !== 0 && strpos($button_link, 'http') !== 0) {
                        $button_link = '/' . $button_link;
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars($button_link); ?>" class="carousel-button">
                        <?php echo htmlspecialchars($carousel_settings['button_text']); ?>
                    </a>
                </div>
            </div>
        
            <div class="hero-carousel">
                <?php while ($image = mysqli_fetch_assoc($images_result)): 
                    // Handle both Cloudinary URLs and local paths
                    $image_url = $image['image_url'];
                    if (strpos($image_url, 'http://') === 0 || strpos($image_url, 'https://') === 0) {
                        $image_path = $image_url; // Cloudinary URL
                    } else {
                        $image_path = '/assets/' . $image_url; // Local path
                    }
                ?>
                    <div class="hero-slide" style="background-image: url('<?php echo htmlspecialchars($image_path); ?>');">
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-slides" role="alert">
                <p>No carousel slides available at the moment.</p>
            </div>
        <?php endif; ?>
        </section>

        <div class="other-content">
            <section class="featured-section">
                <header class="section-header" data-aos="fade-up">
                    <h1 class="titles">Featured Products</h1>
                    <p class="service-subtitle">Discover our handpicked selection of premium products</p>
                </header>
                
                <?php if (count($featured_products_data) > 0): ?>
                    <div class="featured-grid">
                        <?php 
                        // Display only the first 4 products
                        $count = 0;
                        foreach ($featured_products_data as $row): 
                            if ($count >= 4) break;
                            $count++;
                            
                            // Get all images for this product
                            $images_sql = "SELECT COALESCE(cloud_url, image_url) as image_url FROM product_images WHERE product_id = ?";
                            $images_stmt = mysqli_prepare($conn, $images_sql);
                            mysqli_stmt_bind_param($images_stmt, "i", $row['id']);
                            mysqli_stmt_execute($images_stmt);
                            $images_result = mysqli_stmt_get_result($images_stmt);
                            $images = [];
                            while ($image = mysqli_fetch_assoc($images_result)) {
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
                            
                            // Do NOT add featured class on dashboard - no featured badge display needed
                            $statusClass = strtolower(str_replace(' ', '-', $row['status_name']));
                            
                            $productDataJson = htmlspecialchars(json_encode($productData), ENT_QUOTES, 'UTF-8');
                            
                            // Get available dates for display
                            // Status 4 = Same Day Order
                            $available_dates = $row['status_id'] == 4 ? $row['todays_product_dates'] : $row['regular_today_dates'];
                            
                            // Use availability data already calculated by determineProductAvailability()
                            $is_unavailable = $row['is_unavailable'];
                            $unavailable_reason = $row['unavailable_reason'];
                            
                            // Check if product is available TODAY
                            // Can be from either:
                            // 1. Pure status 4 (Same Day Order only) - uses todays_product_dates
                            // 2. Status 1/2/3 with same-day option - uses regular_today_dates
                            $is_available_today = false;
                            
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
                        ?>
                            <div class="product-card <?php echo $unavailableClass; ?>" 
                                data-product-id="<?php echo $row['id']; ?>"
                                data-status="<?php echo htmlspecialchars($row['status_name']); ?>"
                                data-available-dates="<?php echo htmlspecialchars($available_dates ?? ''); ?>"
                                data-product="<?php echo $productDataJson; ?>" 
                                data-unavailable="<?php echo $is_unavailable ? 'true' : 'false'; ?>"
                                onclick="openProductModalFromData(this)">
                                
                                <?php
                                // Display badges: Show capabilities based on product configuration AND stock availability
                                if ($is_unavailable) {
                                    // Unavailable badge (highest priority, exclusive)
                                    echo "<div class='unavailable-badge-left'>" . htmlspecialchars($unavailable_reason) . "</div>";
                                } else {
                                    // Determine product capabilities with actual stock checks
                                    $has_preorder = in_array($row['status_id'], [1, 2, 3]) && $row['quantity'] > 0;
                                    
                                    // Check same-day capability: must have stock AND be available today
                                    $sameday_stock = $row['sameday_stock_today'] ?? 0;
                                    $has_sameday = false;
                                    
                                    if ($row['status_id'] == 4) {
                                        // Status 4: Same-day only product - check stock and date
                                        $has_sameday = ($sameday_stock > 0) && $is_available_today;
                                    } elseif (!empty($row['availtoday_status_id'])) {
                                        // Status 1/2/3 with same-day capability - check stock and date
                                        $has_sameday = ($sameday_stock > 0) && $is_available_today;
                                    }
                                    
                                    // Show badges based on capabilities
                                    if ($has_sameday && $has_preorder) {
                                        // Product has BOTH capabilities with stock
                                        echo "<div class='today-badge-left'>Same Day & Pre-Order</div>";
                                    } elseif ($has_sameday) {
                                        // Same-day only with stock
                                        echo "<div class='today-badge-left'>Same Day Order</div>";
                                    } elseif ($has_preorder) {
                                        // Pre-order only with stock
                                        echo "<div class='preorder-badge-left'>Pre-Order</div>";
                                    }
                                    // Note: Featured badge is handled by CSS class 'featured-product' and image overlay
                                }
                                ?>
                                
                                <div class="product-image">
                                    <?php
                                    // Image overlays: Show both if product is Available Today AND Featured
                                    if ($is_unavailable) {
                                        // Unavailable overlay (exclusive)
                                        echo "<div class='unavailable-overlay'>
                                                <span class='unavailable-text'>UNAVAILABLE</span>
                                                <span class='unavailable-reason'>" . htmlspecialchars($unavailable_reason) . "</span>
                                              </div>";
                                    }
                                    
                                    // Handle both Cloudinary URLs (full URLs) and local paths
                                    $image_src = $row['image_url'] ?: 'images/no-image.jpg';
                                    if (strpos($image_src, 'http://') === 0 || strpos($image_src, 'https://') === 0) {
                                        // It's a full URL (Cloudinary)
                                        $image_path = htmlspecialchars($image_src);
                                    } else {
                                        // It's a local path
                                        $image_path = '/assets/' . htmlspecialchars($image_src);
                                    }
                                    ?>
                                    <img src="<?php echo $image_path; ?>" 
                                        alt="<?php echo htmlspecialchars($row['name']); ?>"
                                        loading="lazy"
                                        onerror="this.onerror=null; this.src='/assets/images/no-image.jpg';">
                                </div>
                                
                                <div class="product-info">
                                    <h3 class="productname"><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <p class="price">₱<?php echo number_format($row['price'], 2); ?></p>
                                    
                                    <?php if ($is_unavailable): ?>
                                        <button class="add-to-cart unavailable-btn" disabled>Unavailable</button>
                                    <?php else: ?>
                                        <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?php echo $row['id']; ?>, this)">Add to Cart</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($featured_products_data) > 4): ?>
                        <button class="learn-more" data-aos="fade-up">
                            <span class="circle" aria-hidden="true">
                            <span class="icon arrow"></span>
                            </span>
                            <span class="button-text"> 
                                <a href="/frontend/pages/products/product-dashboard.php">
                                View More
                                </a>
                            </span>
                        </button>                
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-products" role="alert">
                        <p>No featured products available at the moment.</p>
                    </div>
                <?php endif; ?>
            </section>

            <?php
            // Get service section settings
            $service_settings_query = "SELECT * FROM service_section_settings LIMIT 1";
            $service_settings_result = mysqli_query($conn, $service_settings_query);
            if (!$service_settings_result) {
                // Log error for debugging
                error_log("Service settings query failed: " . mysqli_error($conn));
                $service_settings = null;
                $service_section_settings = false;
            } else {
                $service_settings = mysqli_fetch_assoc($service_settings_result);
                if (!$service_settings) {
                    // If no settings found, create default
                    $service_settings = [
                        'title' => 'Our Services',
                        'subtitle' => 'What we offer'
                    ];
                    $service_section_settings = false;
                    error_log("No service settings found in database, using defaults");
                } else {
                    $service_section_settings = true;
                    error_log("Service settings found: " . print_r($service_settings, true));
                }
            }

            // Get service cards
            $service_cards_query = "SELECT * FROM service_cards ORDER BY display_order ASC";
            $service_cards_result = mysqli_query($conn, $service_cards_query);
            if (!$service_cards_result) {
                error_log("Service cards query failed: " . mysqli_error($conn));
                $service_cards_result = null;
            }
            ?>


            <section class="promotions-section">
                 <div class="section-header">
                    <h1 class="promotion-title">Discount Coupons</h1>
                    <p class="service-subtitle">Save money with our discount codes at checkout!</p>
                </div>

                <div class="promotion-grid">
                    <?php if (count($active_promotions) > 0): ?>
                        <?php foreach ($active_promotions as $promo): 
                            // Format discount display
                            if ($promo['type'] === 'free_shipping') {
                                $discount_display = 'FREE SHIPPING';
                                $discount_type_display = 'FREE SHIPPING';
                            } elseif ($promo['type'] === 'percentage') {
                                // Percentage discount: format as 0%
                                $discount_display = $promo['value'] . '%' . ' DISCOUNT';
                                $discount_type_display = 'PRODUCT DISCOUNT';
                            } else {
                                // Fixed amount discount: format as ₱0.00
                                $discount_display = '₱' . number_format($promo['value'], 2) . ' DISCOUNT';
                                $discount_type_display = 'PRODUCT DISCOUNT';
                            }
                            
                            // Format dates to MM/DD/YY
                            $start_date = date('m/d/y', strtotime($promo['activation_date']));
                            $end_date = date('m/d/y', strtotime($promo['expiration_date']));
                            $date_validity = $start_date . ' - ' . $end_date;
                            
                            // Format min spend
                            $min_spend = number_format($promo['min_purchase'], 0);
                        ?>
                            <div class="coupon-ticket" data-aos="fade-up">
                                <div class="ticket-left">
                                    <div class="coupon-code"><?php echo htmlspecialchars($promo['code']); ?></div>
                                    <div class="coupon-title"><?php echo $discount_display; ?></div>
                                </div>
                                <div class="ticket-divider">
                                    <div class="circle-top"></div>
                                    <div class="dashed-line"></div>
                                    <div class="circle-bottom"></div>
                                </div>
                                <div class="ticket-right">
                                    <div class="discount-type"><?php echo $discount_type_display; ?></div>
                                    <div class="coupon-details">
                                        <div class="min-spend">Min Spend: ₱<?php echo $min_spend; ?></div>
                                        <div class="validity">Valid: <?php echo $date_validity; ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No available promotions at the moment.</p>
                    <?php endif; ?>
                </div>
                
            </section>

            <!-- Service Section -->
            <section class="service-section">
                <div class="section-header">
                    <h1 class="service-title" data-aos="fade-up"><?php echo $service_section_settings ? htmlspecialchars($service_settings['title']) : 'Our Services'; ?></h1>
                    <p class="service-subtitle" data-aos="fade-up"><?php echo $service_section_settings ? htmlspecialchars($service_settings['subtitle']) : 'What we offer'; ?></p>
                </div>
                
                <div class="service-grid">
                    <?php if ($service_cards_result && mysqli_num_rows($service_cards_result) > 0): ?>
                        <?php while ($card = mysqli_fetch_assoc($service_cards_result)): ?>
                            <?php
                            // Generate an icon based on the icon_name
                            $icon_svg = '';
                            switch ($card['icon_name']) {
                                case 'star':
                                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24"><path fill="currentColor" d="M22 10.1c.1-.5-.3-1.1-.8-1.1l-5.7-.8L12.9 3c-.1-.2-.2-.3-.4-.4c-.5-.3-1.1-.1-1.4.4L8.6 8.2L2.9 9c-.3 0-.5.1-.6.3c-.4.4-.4 1 0 1.4l4.1 4l-1 5.7c0 .2 0 .4.1.6c.3.5.9.7 1.4.4l5.1-2.7l5.1 2.7c.1.1.3.1.5.1h.2c.5-.1.9-.6.8-1.2l-1-5.7l4.1-4c.2-.1.3-.3.3-.5z"/></svg>';
                                    break;
                                case 'truck':
                                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M10 17h4V5H2v12h3m15 0h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5m0 8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></g></svg>';
                                    break;
                                case 'diamond':
                                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 512 512"><path fill="currentColor" fill-rule="evenodd" d="m384 85.333l85.333 85.334v256H42.667V182.113l42.666 49.777V384h341.334V181.333L373.333 128h-46.985l-34.34-42.667zM384 320v32H128v-32zM256 64l53.333 74.667l-138.666 160L32 138.667L85.333 64zm-20.664 192H384v32H234.667v-31.22zm-41.663-106.667H147.64l23.027 91.478zm-79.059 0H81.86l58.37 80.05zm144.839 0h-32.734l-25.611 80.033zm30.74 42.666L384 192v32H262.765zM137.214 96h-35.433l-22.853 32h36.953zm39.306 0h-11.745l-13.609 32h38.974zm63.01 0h-35.412l21.334 32h36.931z"/></svg>';
                                    break;
                                case 'paper-bag':
                                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M8 3h8a2 2 0 0 1 2 2v1.82a5 5 0 0 0 .528 2.236l.944 1.888A5 5 0 0 1 20 13.18V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5.82a5 5 0 0 1 .528-2.236L6 8V5a2 2 0 0 1 2-2z"/><path d="M12 15a2 2 0 1 0 4 0a2 2 0 1 0-4 0m-6 6a2 2 0 0 0 2-2v-5.82a5 5 0 0 0-.528-2.236L6 8m5-1h2"/></g></svg>';
                                    break;
                                case 'clock':
                                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 16 16"><path fill="currentColor" d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8s8-3.6 8-8s-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6s6 2.7 6 6s-2.7 6-6 6z"/><path fill="currentColor" d="M8 3H7v6h5V8H8z"/></svg>';
                                    break;
                                case 'info':
                                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M21 12a9 9 0 1 1-18 0a9 9 0 0 1 18 0m-9 11c6.075 0 11-4.925 11-11S18.075 1 12 1S1 5.925 1 12s4.925 11 11 11m0-13.8a1.2 1.2 0 1 0 0-2.4a1.2 1.2 0 0 0 0 2.4m1 1.8v6h-2v-6z" clip-rule="evenodd"/></svg>';
                                    break;
                                default:
                                    $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 12 12"><path fill="currentColor" d="M6 9.5A1.5 1.5 0 0 0 7.5 11h2A1.5 1.5 0 0 0 11 9.5v-3A1.5 1.5 0 0 0 9.5 5h-2A1.5 1.5 0 0 0 6 6.5v3Zm1.5.5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-2ZM1 5.5A1.5 1.5 0 0 0 2.5 7h1A1.5 1.5 0 0 0 5 5.5v-3A1.5 1.5 0 0 0 3.5 1h-1A1.5 1.5 0 0 0 1 2.5v3Zm1.5.5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-1Zm5-2a1.5 1.5 0 1 1 0-3h2a1.5 1.5 0 0 1 0 3h-2ZM7 2.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 0-1h-2a.5.5 0 0 0-.5.5Zm-6 7A1.5 1.5 0 0 0 2.5 11h1a1.5 1.5 0 0 0 0-3h-1A1.5 1.5 0 0 0 1 9.5Zm1.5.5a.5.5 0 0 1 0-1h1a.5.5 0 0 1 0 1h-1Z"/></svg>';
                            }
                            ?>
                            <div class="service-card" data-aos="fade-up">
                                <div class="service-icon">
                                    <?php echo $icon_svg; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($card['title']); ?></h3>
                                <p><?php echo htmlspecialchars($card['description']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-service-cards" role="alert">
                            <p>No service cards available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Update the modal structure -->
<div id="productModal" class="modal">
    <div class="modal-content fade-in-pop">
        <span class="close" onclick="closeProductModal()">&times;</span>
        <div class="modal-body">
            <div class="product-images">
                <div class="main-image">
                    <img id="modalMainImage" src="/assets/images/placeholder.svg" alt="Product Image">
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
                    <!-- Stock is hidden as per product-dashboard.php -->
                </div>
                <h3>Description:</h3>
                <div class="description" id="modalProductDescription" style="white-space: pre-line"></div>

                <!-- Quantity controls removed from modal - using quantity modal instead -->
                <button class="add-to-cart" id="modalAddToCart">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<!-- Quantity Modal (appears before adding to cart) -->
<div id="quantityModal" class="modal" style="display: none;">
    <div class="modal-content quantity-modal-content fade-in-pop">
        <span class="close" onclick="closeQuantityModal()">&times;</span>
        
        <!-- Loading Overlay -->
        <div id="quantityModalLoader" class="quantity-modal-loader" style="display: none;">
            <div class="quantity-modal-spinner"></div>
            <p>Loading...</p>
        </div>
        
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

<script>
// Service cards animation
document.addEventListener('DOMContentLoaded', function() {
    // Animate service cards
    const serviceCards = document.querySelectorAll('.service-card');
    
    const serviceObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Add the 'animated' class to start the animation with a delay
                setTimeout(() => {
                    entry.target.classList.add('animated');
                }, index * 500); // Stagger the animations
                
                // Unobserve after animation is triggered
                serviceObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    // Observe each service card
    serviceCards.forEach(card => {
        serviceObserver.observe(card);
    });

    // Product functionality
    window.updateQuantity = function(button, change) {
        const container = button.parentElement;
        const input = container.querySelector('input');
        const newValue = parseInt(input.value) + change;
        if (newValue >= parseInt(input.min) && newValue <= parseInt(input.max)) {
            input.value = newValue;
        }
    }

    window.validateQuantity = function(input) {
        const value = parseInt(input.value);
        const max = parseInt(input.max);
        const min = parseInt(input.min);
        
        if (value > max) input.value = max;
        if (value < min) input.value = min;
        if (isNaN(value)) input.value = min;
    }

    window.updateModalQuantity = function(change) {
        const input = document.getElementById('modalQuantity');
        const max = parseInt(input.max);
        const newValue = parseInt(input.value) + change;
        if (newValue >= 1 && newValue <= max) {
            input.value = newValue;
        }
    }

    window.validateModalQuantity = function() {
        const input = document.getElementById('modalQuantity');
        const value = parseInt(input.value);
        const max = parseInt(input.max);
        
        if (value > max) input.value = max;
        if (value < 1) input.value = 1;
        if (isNaN(value)) input.value = 1;
    }
});

// Check if user is logged in
const isLoggedIn = <?= SessionManager::isUserLoggedIn() ? 'true' : 'false' ?>;
const loginUrl = '/frontend/login/user/login-signup.php';

// Function to check login and redirect if needed
function checkLoginAndRedirect() {
    if (!isLoggedIn) {
        alert('Please login to add items to cart');
        window.location.href = loginUrl;
        return false;
    }
    return true;
}

// Quantity Modal Variables
let pendingCartProduct = null;
let currentProductModalData = null;

// Add to cart function - Opens quantity modal
window.addToCart = function(productId, button) {
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

window.openProductModal = function(product) {
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
        const addToCartBtn = document.getElementById('modalAddToCart');

        // Set main content
        productName.textContent = product.name || 'Unknown Product';
        productPrice.textContent = '₱' + (parseFloat(product.price) || 0).toFixed(2);
        productStatus.textContent = product.status || 'Available';
        productStatus.className = 'status-badge status-' + (product.status || '').toLowerCase().replace(' ', '-');
        productDescription.textContent = product.description || 'No description available';
        // Stock display removed as per product-dashboard.php

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

        // Check if product is unavailable
        let isUnavailable = false;
        let unavailableReason = '';
        
        const preorderStock = product.quantity || 0;
        const samedayStock = product.sameday_stock_today || 0;
        const hasAvailtoday = product.availtoday_status_id != null && product.availtoday_status_id != '';
        
        if (product.status_id == 4) {
            // Status 4: Same Day ONLY product
            if (samedayStock == 0 || samedayStock === null) {
                isUnavailable = true;
                unavailableReason = 'Out of Stock';
            }
        } else if ([1, 2, 3].includes(product.status_id)) {
            if (hasAvailtoday) {
                // DUAL capability: Pre-order AND Same-day
                if (preorderStock == 0 && (samedayStock == 0 || samedayStock === null)) {
                    isUnavailable = true;
                    unavailableReason = 'Out of Stock';
                }
            } else {
                // Pre-order ONLY
                if (preorderStock == 0) {
                    isUnavailable = true;
                    unavailableReason = 'Out of Stock';
                }
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

        modal.style.display = 'flex';
    } catch (error) {
        console.error('Error in openProductModal:', error);
        alert('An error occurred while opening the product details');
    }
}

// Open product modal from data attribute
window.openProductModalFromData = function(element) {
    const productData = JSON.parse(element.getAttribute('data-product'));
    openProductModal(productData);
};

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

window.closeProductModal = function() {
    document.getElementById('productModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('productModal');
    if (event.target == modal) {
        closeProductModal();
    }
}

// Check if same-day option should be available
async function checkSameDayAvailability(productId) {
    try {
        const response = await fetch(`/frontend/pages/products/get-sdo-quantity.php?product_id=${productId}`);
        const data = await response.json();
        
        if (data.success) {
            // Check if there's quantity available today
            const todayQuantity = data.quantity || 0;
            const hasDateToday = data.has_date_today || false;
            
            // Same-day is available if:
            // 1. There's a date for today AND
            // 2. There's quantity available
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
        // Wraps past midnight (e.g., 15:00 to 08:00 next day)
        return !(currentTimeStr >= openingTime || currentTimeStr < closingTime);
    } else {
        // Normal hours (e.g., 08:00 to 21:00)
        return currentTimeStr < openingTime || currentTimeStr > closingTime;
    }
}

// Open quantity modal with order type selection
async function openQuantityModalWithOrderType(productName, productPrice, productStock, statusId, availtodayStatusId) {
    const modal = document.getElementById('quantityModal');
    const loader = document.getElementById('quantityModalLoader');
    const modalBody = document.querySelector('.quantity-modal-body');
    
    // Show modal with loader
    modal.style.display = 'flex';
    loader.style.display = 'flex';
    modalBody.style.opacity = '0.3';
    modalBody.style.pointerEvents = 'none';
    
    // Small delay to show loading state
    await new Promise(resolve => setTimeout(resolve, 300));
    
    document.getElementById('quantityModalProductName').textContent = productName;
    document.getElementById('quantityModalPrice').textContent = productPrice;
    
    const quantityInput = document.getElementById('quantityModalInput');
    const orderTypeSelector = document.getElementById('orderTypeSelector');
    const dateDisplay = document.getElementById('quantityModalDate');
    const samedayRadio = document.querySelector('input[name="orderType"][value="sameday"]');
    const preorderRadio = document.querySelector('input[name="orderType"][value="preorder"]');
    
    // Check if business is currently closed
    const businessClosed = isBusinessClosed();
    
    // Determine which order types to show
    if ((statusId == 1 || statusId == 2 || statusId == 3) && availtodayStatusId) {
        // Product has BOTH pre-order and same day order
        // Check if same-day is actually available today
        const sameDayAvailable = await checkSameDayAvailability(pendingCartProduct.id);
        
        if (sameDayAvailable && !businessClosed) {
            // Show both options (only if business is open)
            orderTypeSelector.style.display = 'block';
            samedayRadio.disabled = false;
            preorderRadio.disabled = false;
            preorderRadio.checked = true;
            samedayRadio.checked = false;
            quantityInput.value = 1;
            dateDisplay.style.display = 'none';
            
            // Set default to pre-order
            pendingCartProduct.selectedOrderType = 'preorder';
            
            // Fetch pre-order quantity with cart check
            await fetchPreOrderQuantity(pendingCartProduct.id);
        } else {
            // Same-day not available or business closed - show pre-order only
            orderTypeSelector.style.display = 'block';
            samedayRadio.disabled = true;
            preorderRadio.disabled = false;
            preorderRadio.checked = true;
            samedayRadio.checked = false;
            dateDisplay.style.display = 'none';
            pendingCartProduct.selectedOrderType = 'preorder';
            quantityInput.value = 1;
            
            // Fetch pre-order quantity with cart check
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
            quantityInput.value = 1;
            quantityInput.max = 1;
        } else {
            // Business is open - enable same-day
            samedayRadio.checked = true;
            dateDisplay.style.display = 'block';
            dateDisplay.textContent = 'For: Today';
            pendingCartProduct.selectedOrderType = 'sameday';
            
            // Fetch today's quantity before hiding loader
            quantityInput.value = 1;
            quantityInput.max = 1;
            
            await fetchTodayQuantity(pendingCartProduct.id);
        }
    } else {
        // Pre-order ONLY (status 1, 2, or 3 without availtoday_status_id)
        orderTypeSelector.style.display = 'none';
        dateDisplay.style.display = 'none';
        pendingCartProduct.selectedOrderType = 'preorder';
        quantityInput.value = 1;
        
        // Fetch pre-order quantity with cart check
        await fetchPreOrderQuantity(pendingCartProduct.id);
    }
    
    // Hide loader and show content
    loader.style.display = 'none';
    modalBody.style.opacity = '1';
    modalBody.style.pointerEvents = 'auto';
}

// Set loading state for quantity modal
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
function selectOrderType(type) {
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
            fetchTodayQuantity(pendingCartProduct.id).then(() => {
                setQuantityModalLoading(false);
            });
        }
    } else {
        dateDisplay.style.display = 'none';
        
        // Fetch pre-order stock quantity from products.quantity
        if (pendingCartProduct) {
            setQuantityModalLoading(true);
            fetchPreOrderQuantity(pendingCartProduct.id).then(() => {
                setQuantityModalLoading(false);
            });
        }
    }
    
    if (pendingCartProduct) {
        pendingCartProduct.selectedOrderType = type;
    }
}

// Fetch today's specific quantity for same day orders
async function fetchTodayQuantity(productId) {
    const stockDisplay = document.getElementById('quantityModalStock');
    const confirmBtn = document.querySelector('.btn-confirm');
    stockDisplay.textContent = 'Loading...';
    
    try {
        const response = await fetch(`/frontend/pages/products/get-sdo-quantity.php?product_id=${productId}`);
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
            const quantityInput = document.getElementById('quantityModalInput');
            quantityInput.disabled = true;
        }
    } catch (error) {
        console.error('Error:', error);
        stockDisplay.textContent = 'Stock: 0';
        confirmBtn.disabled = true;
        const quantityInput = document.getElementById('quantityModalInput');
        quantityInput.disabled = true;
    }
}

// Fetch pre-order quantity from cart (with cart quantity check)
async function fetchPreOrderQuantity(productId) {
    const stockDisplay = document.getElementById('quantityModalStock');
    const confirmBtn = document.querySelector('.btn-confirm');
    stockDisplay.textContent = 'Loading...';
    
    try {
        const response = await fetch(`/frontend/pages/products/get-preorder-quantity.php?product_id=${productId}`);
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
            const quantityInput = document.getElementById('quantityModalInput');
            quantityInput.disabled = true;
        }
    } catch (error) {
        console.error('Error:', error);
        stockDisplay.textContent = 'Stock: 0';
        confirmBtn.disabled = true;
        const quantityInput = document.getElementById('quantityModalInput');
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

// Show confirmation popup
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
    
    const quantityInput = document.getElementById('quantityModalInput');
    const quantity = parseInt(quantityInput.value) || 1;
    const maxAllowed = parseInt(quantityInput.max) || 0;
    const confirmBtn = document.querySelector('.quantity-modal-actions .btn-confirm');
    
    if (!confirmBtn) return;
    
    // Validate quantity doesn't exceed max allowed
    if (quantity > maxAllowed) {
        showConfirmation(`Cannot add ${quantity}. Maximum available: ${maxAllowed}`, true);
        quantityInput.value = maxAllowed;
        return;
    }
    
    if (quantity < 1) {
        showConfirmation('Quantity must be at least 1', true);
        quantityInput.value = 1;
        return;
    }
    
    // Store original button text
    const originalText = confirmBtn.textContent;
    
    // Disable button and show loading state
    confirmBtn.disabled = true;
    confirmBtn.style.opacity = '0.7';
    confirmBtn.style.cursor = 'not-allowed';
    confirmBtn.innerHTML = '<span class="loading-spinner-small"></span> Adding to cart...';
    
    // Determine which cart to add to based on selectedOrderType
    const orderType = pendingCartProduct.selectedOrderType || 'preorder';
    
    // DEBUG: Log the order type and product info
    console.log('confirmAddToCart called');
    console.log('orderType:', orderType);
    console.log('pendingCartProduct:', pendingCartProduct);
    console.log('selectedOrderType:', pendingCartProduct.selectedOrderType);
    console.log('quantity:', quantity, 'maxAllowed:', maxAllowed);
    
    if (orderType === 'sameday') {
        // Add to same-day cart via API directly
        console.log('Adding to same-day cart');
        addToSameDayCartViaAPI(pendingCartProduct.id, quantity, confirmBtn, originalText);
    } else {
        // Add to pre-order cart (cart table)
        console.log('Adding to pre-order cart');
        // Use fetch to add to regular cart
        fetch('/frontend/pages/cart/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${pendingCartProduct.id}&quantity=${quantity}`,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Trigger cart notification update
                window.dispatchEvent(new CustomEvent('cartUpdated'));
                
                // Show success state
                confirmBtn.innerHTML = '<span class="success-icon">✓</span> Product added!';
                confirmBtn.style.backgroundColor = '#4CAF50';
                
                // Show confirmation message
                showConfirmation(`Added ${quantity} ${pendingCartProduct.name} to cart`, false);
                
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
                console.error('Failed to add to cart:', data.message);
                showConfirmation(data.message || 'Failed to add to cart', true);
                
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
}

// Add to same-day cart via API
function addToSameDayCartViaAPI(productId, quantity, confirmBtn, originalText) {
    // First check if user is logged in
    if (!isLoggedIn) {
        showConfirmation('Please login to add items to cart', true);
        confirmBtn.disabled = false;
        confirmBtn.style.opacity = '1';
        confirmBtn.style.cursor = 'pointer';
        confirmBtn.textContent = originalText;
        
        // Redirect to login
        setTimeout(() => {
            window.location.href = loginUrl;
        }, 1500);
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('/frontend/pages/products/availtoday-cart-api.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => {
        // Log the response for debugging
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Clone response to read it twice
        return response.text().then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                console.error('Response text:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Show success state
            confirmBtn.innerHTML = '<span class="success-icon">✓</span> Product added!';
            confirmBtn.style.backgroundColor = '#4CAF50';
            
            // Show confirmation message
            showConfirmation(`Added ${quantity} ${pendingCartProduct.name} to cart`, false);
            
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
            console.error('Failed to add to same-day cart:', data.error);
            showConfirmation(data.error || 'Failed to add to cart', true);
            
            // Reset button on error
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
            confirmBtn.style.cursor = 'pointer';
            confirmBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error adding to same-day cart:', error);
        showConfirmation('Error adding to cart', true);
        
        // Reset button on error
        confirmBtn.disabled = false;
        confirmBtn.style.opacity = '1';
        confirmBtn.style.cursor = 'pointer';
        confirmBtn.textContent = originalText;
    });
}

// Close modal when clicking outside quantity modal
window.addEventListener('click', function(event) {
    const quantityModal = document.getElementById('quantityModal');
    if (event.target === quantityModal) {
        closeQuantityModal();
    }
});
</script>

<style>
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

/* Quantity Modal Loader */
.quantity-modal-loader {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 100;
    border-radius: 12px;
}

.quantity-modal-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #256035;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 15px;
}

.quantity-modal-loader p {
    color: #256035;
    font-weight: 600;
    font-size: 14px;
    margin: 0;
}

/* Confirmation Popup */
.confirmation-popup {
    position: fixed;
    top: 80px;
    left: 50%;
    transform: translateX(-50%) translateY(-100px);
    background: white;
    color: #333;
    padding: 16px 24px;
    border-radius: 12px;
    z-index: 10000;
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    font-weight: 600;
    min-width: 300px;
    max-width: 500px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    border: 2px solid transparent;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.confirmation-popup.success {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    color: #2e7d32;
    border-color: #4caf50;
    box-shadow: 0 10px 40px rgba(76, 175, 80, 0.3);
}

.confirmation-popup.error {
    background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
    color: #c62828;
    border-color: #f44336;
    box-shadow: 0 10px 40px rgba(244, 67, 54, 0.3);
}

.confirmation-popup.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.confirmation-popup.hide {
    opacity: 0;
    transform: translateX(-50%) translateY(-100px);
}

/* Mobile responsive confirmation popup */
@media (max-width: 768px) {
    .confirmation-popup {
        top: 70px;
        min-width: 280px;
        max-width: 90%;
        padding: 14px 20px;
        font-size: 14px;
    }
    
    .confirmation-popup.show {
        transform: translateX(-50%) translateY(0);
    }
    
    .confirmation-popup.hide {
        transform: translateX(-50%) translateY(-100px);
    }
}
</style>

<?php require_once __DIR__ . "/../../user-includes/user-footer.php"; ?>