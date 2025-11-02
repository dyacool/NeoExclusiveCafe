<?php
$page_title = "Dashboard";

// Include session management files first (before any HTML output)
require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../user-includes/user-header.php";

// Include navigation after session is established
require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
?>

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
 * @return array Availability information with keys: is_unavailable, unavailable_reason, should_display
 */
function determineProductAvailability($product_row, $today_date) {
    $result = [
        'is_unavailable' => false,
        'unavailable_reason' => '',
        'should_display' => true
    ];
    
    // Extract data
    $status_id = $product_row['status_id'];
    $preorder_stock = $product_row['quantity'] ?? 0;
    $sameday_stock = $product_row['sameday_stock_today'] ?? 0;
    $has_availtoday = !empty($product_row['availtoday_status_id']);
    $todays_dates = $product_row['todays_product_dates'] ? explode(', ', $product_row['todays_product_dates']) : [];
    $regular_dates = $product_row['regular_today_dates'] ? explode(', ', $product_row['regular_today_dates']) : [];
    $show_when_unavailable = (bool)($product_row['show_when_unavailable'] ?? 0);
    $hide_when_unavailable = (bool)($product_row['hide_when_unavailable'] ?? 0);
    
    // Step 1: Check stock based on product type
    $stock_unavailable = false;
    
    if ($status_id == 4) {
        // Same-day ONLY product
        $stock_unavailable = ($sameday_stock == 0 || $sameday_stock === null);
    } elseif (in_array($status_id, [1, 2, 3])) {
        if ($has_availtoday) {
            // DUAL capability: unavailable if BOTH stocks are 0
            $stock_unavailable = ($preorder_stock == 0 && ($sameday_stock == 0 || $sameday_stock === null));
        } else {
            // Pre-order ONLY
            $stock_unavailable = ($preorder_stock == 0);
        }
    }
    
    // Step 2: Check date availability
    $date_unavailable = false;
    
    if ($status_id == 4) {
        // Same-day ONLY: must have date in todays_products_dates
        $date_unavailable = !in_array($today_date, $todays_dates);
    } elseif (in_array($status_id, [1, 2, 3]) && $has_availtoday) {
        // DUAL capability: check regular_products_today_dates for same-day option
        if ($sameday_stock > 0) {
            // Has same-day stock, so must have valid date
            $date_unavailable = !in_array($today_date, $regular_dates);
        } else {
            // No same-day stock, date check not needed
            $date_unavailable = false;
        }
    }
    
    // Step 3: Determine overall unavailability
    $result['is_unavailable'] = $stock_unavailable || $date_unavailable;
    
    if ($stock_unavailable) {
        $result['unavailable_reason'] = 'Out of Stock';
    } elseif ($date_unavailable) {
        $result['unavailable_reason'] = 'Not Available Today';
    }
    
    // Step 4: Apply visibility rules
    if ($result['is_unavailable']) {
        // Priority: hide_when_unavailable takes precedence
        if ($hide_when_unavailable) {
            $result['should_display'] = false;
        } elseif ($show_when_unavailable) {
            $result['should_display'] = true;
        } else {
            // Default: hide unavailable products
            $result['should_display'] = false;
        }
        
        // Log visibility decisions for debugging
        if (!$result['should_display']) {
            error_log(sprintf(
                "[Product Visibility] Hidden - Product ID: %d, Name: %s, Reason: %s, hide_flag: %d, show_flag: %d",
                $product_row['id'],
                $product_row['name'],
                $result['unavailable_reason'],
                $hide_when_unavailable ? 1 : 0,
                $show_when_unavailable ? 1 : 0
            ));
        }
    } else {
        // Available products are always displayed
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
                    <a href="<?php echo htmlspecialchars($carousel_settings['button_link']); ?>" class="carousel-button">
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
                            
                            // Encode product data for modal
                            $productData = [
                                'id' => $row['id'],
                                'name' => $row['name'],
                                'price' => $row['price'],
                                'description' => $row['description'],
                                'status' => $row['status_name'],
                                'status_id' => $row['status_id'],
                                'images' => $row['images'],
                                'is_featured' => (bool)$row['is_featured'],
                                'quantity' => $row['quantity'],
                                'sameday_stock_today' => $row['sameday_stock_today'] ?? 0,
                                'show_when_unavailable' => (bool)$row['show_when_unavailable'],
                                'availtoday_status_id' => $row['availtoday_status_id'],
                                'availtoday_status_name' => $row['availtoday_status_name'],
                                'todays_product_dates' => $row['todays_product_dates'] ? explode(', ', $row['todays_product_dates']) : [],
                                'regular_today_dates' => $row['regular_today_dates'] ? explode(', ', $row['regular_today_dates']) : []
                            ];
                            
                            $featuredClass = $row['is_featured'] ? 'featured-product' : '';
                            $statusClass = strtolower(str_replace(' ', '-', $row['status_name']));
                            
                            $productDataJson = htmlspecialchars(json_encode($productData), ENT_QUOTES, 'UTF-8');
                            
                            // Get available dates for display
                            $available_dates = $row['status_id'] == 4 ? $row['todays_product_dates'] : $row['regular_today_dates'];
                            
                            // Use pre-calculated availability data
                            $is_unavailable = $row['is_unavailable'] ?? false;
                            $unavailable_reason = $row['unavailable_reason'] ?? '';
                            
                            // Check if product is available TODAY
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
                            <div class="product-card <?php echo $featuredClass; ?> <?php echo $unavailableClass; ?>" 
                                data-status="<?php echo htmlspecialchars($row['status_name']); ?>"
                                data-available-dates="<?php echo htmlspecialchars($available_dates ?? ''); ?>"
                                data-product="<?php echo $productDataJson; ?>" 
                                data-unavailable="<?php echo $is_unavailable ? 'true' : 'false'; ?>"
                                onclick="openProductModalFromData(this)">
                                
                                <?php
                                // Display badges: Show both if product is Available Today AND Featured
                                if ($is_unavailable) {
                                    // Unavailable badge (highest priority, exclusive)
                                    echo "<div class='unavailable-badge-left'>" . htmlspecialchars($unavailable_reason) . "</div>";
                                } else {
                                    // Show today badge if available today
                                    if ($is_available_today) {
                                        echo "<div class='today-badge-left'>Same Day Order</div>";
                                    } else {
                                        // Show pre-order badge if not available today and not unavailable
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
                                        $image_path = $image_src;
                                    } else {
                                        // It's a local path
                                        $image_path = '/assets/' . $image_src;
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                        alt="<?php echo htmlspecialchars($row['name']); ?>"
                                        loading="lazy">
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
                                <a href="/frontend/pages/products/products-categories.php">
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
                            // Format discount type display
                            $discount_type_display = '';
                            if ($promo['type'] === 'free_shipping') {
                                $discount_type_display = 'FREE SHIPPING';
                            } else {
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
                                    <div class="coupon-title"><?php echo htmlspecialchars($promo['title']); ?></div>
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
                    <button type="button" class="order-type-btn active" data-type="preorder" onclick="selectOrderType('preorder')">
                        Pre-Order
                    </button>
                    <button type="button" class="order-type-btn" data-type="sameday" onclick="selectOrderType('sameday')">
                        Same Day Order
                    </button>
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
const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
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
    
    // Determine which order types to show
    if ((statusId == 1 || statusId == 2 || statusId == 3) && availtodayStatusId) {
        // Product has BOTH pre-order and same day order
        // Check if same-day is actually available today
        const sameDayAvailable = await checkSameDayAvailability(pendingCartProduct.id);
        
        if (sameDayAvailable) {
            // Show both options
            orderTypeSelector.style.display = 'block';
            document.getElementById('quantityModalStock').textContent = `Stock: ${productStock}`;
            quantityInput.value = 1;
            quantityInput.max = productStock;
            selectOrderType('preorder'); // Default to pre-order
        } else {
            // Same-day not available - show pre-order only
            orderTypeSelector.style.display = 'none';
            dateDisplay.style.display = 'none';
            pendingCartProduct.selectedOrderType = 'preorder';
            document.getElementById('quantityModalStock').textContent = `Stock: ${productStock}`;
            quantityInput.value = 1;
            quantityInput.max = productStock;
        }
    } else if (statusId == 4) {
        // Same Day Order ONLY - Fetch today's quantity
        orderTypeSelector.style.display = 'none';
        dateDisplay.style.display = 'block';
        dateDisplay.textContent = 'For: Today';
        pendingCartProduct.selectedOrderType = 'sameday';
        
        // Fetch today's quantity before hiding loader
        document.getElementById('quantityModalStock').textContent = 'Loading...';
        quantityInput.value = 1;
        quantityInput.max = 1;
        
        await fetchTodayQuantity(pendingCartProduct.id);
    } else {
        // Pre-order ONLY (status 1, 2, or 3 without availtoday_status_id)
        orderTypeSelector.style.display = 'none';
        dateDisplay.style.display = 'none';
        pendingCartProduct.selectedOrderType = 'preorder';
        document.getElementById('quantityModalStock').textContent = `Stock: ${productStock}`;
        quantityInput.value = 1;
        quantityInput.max = productStock;
    }
    
    // Hide loader and show content
    loader.style.display = 'none';
    modalBody.style.opacity = '1';
    modalBody.style.pointerEvents = 'auto';
}

// Select order type
async function selectOrderType(type) {
    const buttons = document.querySelectorAll('.order-type-btn');
    buttons.forEach(btn => {
        if (btn.getAttribute('data-type') === type) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    const dateDisplay = document.getElementById('quantityModalDate');
    const stockDisplay = document.getElementById('quantityModalStock');
    
    if (type === 'sameday') {
        dateDisplay.style.display = 'block';
        dateDisplay.textContent = 'For: Today';
        
        // Show mini loader in stock display while fetching
        stockDisplay.innerHTML = '<span class="loading-spinner-small" style="display: inline-block;"></span> Loading...';
        
        // Fetch today's specific quantity from database
        if (pendingCartProduct) {
            await fetchTodayQuantity(pendingCartProduct.id);
        }
    } else {
        dateDisplay.style.display = 'none';
        
        // Restore original stock quantity for pre-order
        if (pendingCartProduct) {
            stockDisplay.textContent = `Stock: ${pendingCartProduct.stock}`;
            const quantityInput = document.getElementById('quantityModalInput');
            quantityInput.max = pendingCartProduct.stock;
            quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, pendingCartProduct.stock);
        }
    }
    
    if (pendingCartProduct) {
        pendingCartProduct.selectedOrderType = type;
    }
}

// Fetch today's specific quantity for same day orders
function fetchTodayQuantity(productId) {
    const stockDisplay = document.getElementById('quantityModalStock');
    stockDisplay.textContent = 'Loading...';
    
    return fetch(`/frontend/pages/products/get-sdo-quantity.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const todayQuantity = data.quantity || 0;
                stockDisplay.textContent = `Stock: ${todayQuantity}`;
                
                const quantityInput = document.getElementById('quantityModalInput');
                quantityInput.max = todayQuantity;
                quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, todayQuantity);
                
                if (todayQuantity === 0) {
                    stockDisplay.textContent = 'Out of Stock for Today';
                    quantityInput.disabled = true;
                }
            } else {
                stockDisplay.textContent = 'Stock: 0';
                console.error('Failed to fetch today\'s quantity');
            }
        })
        .catch(error => {
            console.error('Error fetching today\'s quantity:', error);
            stockDisplay.textContent = 'Stock: 0';
        });
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
    
    // Determine the correct endpoint based on order type
    const endpoint = orderType === 'sameday' 
        ? '/frontend/pages/cart/add-to-availtoday-cart.php' 
        : '/frontend/pages/cart/add-to-cart.php';
    
    // Make the actual API call
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${pendingCartProduct.id}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.success) {
            // Show success state
            confirmBtn.innerHTML = '<span class="success-icon">✓</span> Product added!';
            confirmBtn.style.backgroundColor = '#4CAF50';
            
            // Show confirmation message
            const cartType = orderType === 'sameday' ? 'same-day cart' : 'cart';
            showConfirmation(`✓ Added ${quantity} ${pendingCartProduct.name} to ${cartType}`, false);
            
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
            showConfirmation('✗ ' + (data.message || 'Failed to add to cart'), true);
            
            // Reset button on error
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
            confirmBtn.style.cursor = 'pointer';
            confirmBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showConfirmation('✗ Error adding to cart. Please try again.', true);
        
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