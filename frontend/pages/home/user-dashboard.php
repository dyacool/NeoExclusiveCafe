<?php
$page_title = "Dashboard";


require_once __DIR__ . "/../../user-includes/database.php";
require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once __DIR__ . "/../../user-includes/user-header.php";
?>

<link rel="stylesheet" href="/frontend/pages/home/user-dashboard.css">
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
// Get all featured products
$featured_query = "SELECT DISTINCT p.*, 
                    COALESCE(pi.image_url, 'assets/images/placeholder.jpg') as image_url, 
                    ps.name as status_name,
                    p.quantity as stock
                  FROM crud.products p 
                  LEFT JOIN crud.product_images pi ON p.id = pi.product_id 
                  LEFT JOIN crud.product_statuses ps ON p.status_id = ps.id
                  WHERE p.is_featured = 1 
                  AND p.deleted_at IS NULL
                  AND (p.hide_when_unavailable = 0 OR p.status_id != 3)
                  AND (pi.is_primary = 1 OR pi.is_primary IS NULL)
                  ORDER BY p.created_at DESC";

$featured_stmt = mysqli_prepare($conn, $featured_query);
mysqli_stmt_execute($featured_stmt);
$featured_products = mysqli_stmt_get_result($featured_stmt);
$total_featured = mysqli_num_rows($featured_products);

// Debug information
error_log("Featured products query: " . $featured_query);
error_log("Total featured products found: " . $total_featured);

// If no featured products found, check the database state
if ($total_featured == 0) {
    $check_query = "SELECT COUNT(*) as total FROM crud.products WHERE is_featured = 1";
    $check_result = mysqli_query($conn, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);
    error_log("Total featured products in database (including deleted): " . $check_data['total']);
}

// Get all images for each product
$featured_products_data = [];
while ($product = mysqli_fetch_assoc($featured_products)) {
    // Get all images for this product
    $images_sql = "SELECT image_url FROM product_images WHERE product_id = ?";
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

// Get carousel settings
$settings_query = "SELECT * FROM carousel_settings LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);
$carousel_settings = mysqli_fetch_assoc($settings_result);

// Get carousel images
$images_query = "SELECT * FROM carousel_images WHERE is_active = 1 ORDER BY display_order ASC";
$images_result = mysqli_query($conn, $images_query);
$total_images = mysqli_num_rows($images_result);
?>

<main class="Main">
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
                <?php while ($image = mysqli_fetch_assoc($images_result)): ?>
                    <div class="hero-slide" style="background-image: url('/<?php echo htmlspecialchars($image['image_url']); ?>');">
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
                </header>
                
                <?php if (count($featured_products_data) > 0): ?>
                    <div class="featured-grid">
                        <?php 
                        // Display only the first 4 products
                        $count = 0;
                        foreach ($featured_products_data as $product): 
                            if ($count >= 4) break;
                            $count++;
                            
                            $isUnavailable = $product['status_id'] == 3 || $product['stock'] <= 0;
                            $statusClass = strtolower(str_replace(' ', '-', $product['status_name']));
                            
                            // Encode product data for modal
                            $productData = [
                                'id' => $product['id'],
                                'name' => $product['name'],
                                'price' => $product['price'],
                                'description' => $product['description'],
                                'status' => $product['status_name'],
                                'images' => $product['images'],
                                'is_featured' => (bool)$product['is_featured'],
                                'quantity' => $product['stock'],
                                'show_when_unavailable' => (bool)($product['hide_when_unavailable'] == 0)
                            ];
                        ?>
                            <div class="product-card featured-product" 
                                data-status="<?php echo htmlspecialchars($product['status_name']); ?>"
                                onclick="openProductModal(<?php echo htmlspecialchars(json_encode($productData), ENT_QUOTES, 'UTF-8'); ?>)">
                                <div class="product-image">
                                    <img src="/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        loading="lazy">
                                </div>
                                <div class="product-info">
                                    <h3 class = "prdct-nm"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="price">₱<?php echo number_format($product['price'], 2); ?></p>
                                    
                                    <div class="prdct-availability">
                                        <span class="status-badge status-<?php echo $statusClass; ?>">
                                            <?php echo $isUnavailable ? "Not Available" : htmlspecialchars($product['status_name']); ?>
                                        </span>
                                        <div class="stocks">
                                            <p class="stock">Stock: <?php echo $product['stock']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!$isUnavailable): ?>
                                        <div class="quantity-controls">
                                            <button type="button" onclick="event.stopPropagation(); updateQuantity(this, -1)">-</button>
                                            <input type="number" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                                                onclick="event.stopPropagation()" onchange="validateQuantity(this)">
                                            <button type="button" onclick="event.stopPropagation(); updateQuantity(this, 1)">+</button>
                                        </div>
                                        <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?php echo $product['id']; ?>, this)">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="add-to-cart unavailable" disabled>Currently Unavailable</button>
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
                                <a href="/NeoExclusiveCafe/pages/users/user-products.php?filter=Featured">
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

            <!-- Categories Section -->
            <section class="categories-section">
                <header class="section-header" data-aos="fade-up">
                    <h1 class="titles">Categories</h1>
                </header>
                <div class="blog-container" data-aos="fade-up">
                    <div class="blog-section animate-fade-in-left">
                        <a href="product-dashboard.php" class="blog-link">
                            <img src="/assets/images/IMG_1171.jpg" alt="Weekly Available">
                            <div class="section-title">
                                <span>PRODUCTS</span>
                            </div>
                        </a>
                    </div>

                    <div class="blog-section animate-fade-in-right">
                        <a href="blog-page.php" class="blog-link">
                            <img src="/assets/images/44185072_Unknown.JPG" alt="All Products">
                            <div class="section-title">
                                <span>BLOGS</span>
                            </div>
                        </a>
                    </div>
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
                    <p class="stock" id="modalProductStock"></p>
                </div>
                <h3>Description:</h3>
                <div class="description" id="modalProductDescription" style="white-space: pre-line"></div>

                <div class="quantity-controls modal-quantity">
                    <button type="button" onclick="updateModalQuantity(-1)">-</button>
                    <input type="number" id="modalQuantity" value="1" min="1" onchange="validateModalQuantity()">
                    <button type="button" onclick="updateModalQuantity(1)">+</button>
                </div>
                <button class="add-to-cart" id="modalAddToCart">Add to Cart</button>
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

window.addToCart = function(productId, button) {
    const quantityInput = button.parentElement.querySelector('input');
    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

    fetch("/frontend/pages/cart/add-to-cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Product added to cart successfully!");
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(error => console.error("Error:", error));
}

window.openProductModal = function(product) {
    const modal = document.getElementById('productModal');
    const mainImage = document.getElementById('modalMainImage');
    const thumbnails = document.getElementById('thumbnailContainer');
    const productName = document.getElementById('modalProductName');
    const productPrice = document.getElementById('modalProductPrice');
    const productStatus = document.getElementById('modalProductStatus');
    const productDescription = document.getElementById('modalProductDescription');
    const productStock = document.getElementById('modalProductStock');
    const quantityInput = document.getElementById('modalQuantity');
    const addToCartBtn = document.getElementById('modalAddToCart');

    // Set main content
    productName.textContent = product.name;
    productPrice.textContent = '₱' + parseFloat(product.price).toFixed(2);
    productStatus.textContent = product.quantity <= 0 || product.status === 'Unavailable' ? 'Not Available' : product.status;
    productStatus.className = 'status-badge status-' + product.status.toLowerCase().replace(' ', '-');
    productDescription.textContent = product.description || 'No description available';
    productStock.textContent = 'Stock: ' + product.quantity;

    // Set quantity input max value
    quantityInput.max = product.quantity;
    quantityInput.value = 1;

    // Set up images
    if (product.images && product.images.length > 0) {
        mainImage.src = '/' + product.images[0];
        
        // Clear existing thumbnails
        thumbnails.innerHTML = '';
        
        // Add all images as thumbnails
        product.images.forEach((image, index) => {
            const thumb = document.createElement('img');
            thumb.src = '/' + image;
            thumb.alt = `${product.name} view ${index + 1}`;
            thumb.onclick = () => mainImage.src = thumb.src;
            thumbnails.appendChild(thumb);
        });
    }

    // Set up Add to Cart button
    const isUnavailable = product.status === 'Unavailable' || product.quantity <= 0;
    if (isUnavailable) {
        addToCartBtn.disabled = true;
        addToCartBtn.textContent = 'Not Available';
        addToCartBtn.classList.add('unavailable');
        quantityInput.disabled = true;
    } else {
        addToCartBtn.disabled = false;
        addToCartBtn.textContent = 'Add to Cart';
        addToCartBtn.classList.remove('unavailable');
        quantityInput.disabled = false;
        addToCartBtn.onclick = () => {
            const quantity = parseInt(quantityInput.value);
            fetch("/frontend/pages/cart/add-to-cart.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `product_id=${product.id}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Product added to cart successfully!");
                    closeProductModal();
                } else {
                    alert("Error: " + data.error);
                }
            })
            .catch(error => console.error("Error:", error));
        };
    }

    modal.style.display = 'flex';
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
</script>

<?php require_once __DIR__ . "/../../user-includes/user-footer.php"; ?>
