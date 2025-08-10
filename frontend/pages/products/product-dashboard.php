<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Products";
$additional_css = [
    "/frontend/pages/products/product-dashboard.css"
];

require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once __DIR__ . "/../../user-includes/user-header.php";
require_once __DIR__ . "/../../user-includes/preview-mode.php";
require_once __DIR__ . "/../../user-includes/database.php";
?>

<div id="confirmationPopup" class="confirmation-popup"></div>

<div class="wrapper">
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <div class="admin-controls">
        <a href="/backend/pages/admin-manager.php" class="admin-back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>
    </div>
    <?php endif; ?>
    
    <h1 class="prdct-title">Available Today for Pick Up or Delivery!</h1>
    <div class="header-section">
        <h2 class="prdct-subtitle" id="currentDate"><?php echo date('l, F j, Y'); ?></h2>
        <div class="cart-dropdown">
            <button class="cart-btn" id="availableTodayCartBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="m1 1 4 4 6.5 13h9"></path>
                    <path d="m7 13 10-10-1.5-1.5L5.5 11.5"></path>
                </svg>
                <span class="cart-count" id="availableTodayCartCount">0</span>
            </button>
            <div class="cart-dropdown-content" id="availableTodayCartContent">
                <div class="cart-header">
                    <h3>Available Today Cart</h3>
                </div>
                <div class="availToday_timer" id="availToday_timer">
                    <span class="timer-label">Order before:</span>
                    <span class="timer-value" id="availTodayTimerValue">Loading...</span>
                </div>
                <div class="cart-items" id="availableTodayCartItems">
                    <p class="empty-cart">No items in cart</p>
                </div>
                <div class="cart-footer">
                    <div class="cart-total" id="availableTodayCartTotal">Total: ₱0.00</div>
                    <button class="checkout-btn" id="availableTodayCheckoutBtn" disabled>Checkout</button>
                </div>
            </div>
        </div>
    </div>
    <div class="main-container fade-in">

        <!-- Scroll Container -->
        <div class="scroll-container">
            <div class="products-grid" id="productScroll">
                        <?php
                            // Get today's day of the week
                            $today = date('l'); // Returns full day name (e.g., 'Monday', 'Tuesday', etc.)
                            
                            // Query only for Available Today products (status_id = 3) that are available on today's day
                            $sql = "SELECT 
                                        p.id, p.name, p.price, p.description, p.status_id, p.is_featured,
                                        ps.name AS status_name, pi.image_url, p.quantity, p.show_when_unavailable,
                                        GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days
                                    FROM products p
                                    LEFT JOIN product_statuses ps ON p.status_id = ps.id
                                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                    LEFT JOIN product_day pd ON p.id = pd.product_id
                                    WHERE p.deleted_at IS NULL 
                                    AND p.status_id = 3
                                    AND p.quantity > 0
                                    AND pd.day_of_week = ?
                                    GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, ps.name, pi.image_url, p.quantity, p.show_when_unavailable
                                    ORDER BY p.is_featured DESC, p.name ASC";
                    
                            // Prepare and execute the statement with today's day parameter
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("s", $today);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Get all images for this product
                                    $images_sql = "SELECT image_url FROM product_images WHERE product_id = ?";
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
                                        'images' => $images,
                                        'is_featured' => (bool)$row['is_featured'],
                                        'quantity' => $row['quantity'],
                                        'show_when_unavailable' => (bool)$row['show_when_unavailable'],
                                        'available_days' => $row['available_days'] ? explode(', ', $row['available_days']) : []
                                    ];
                                    
                                    $featuredClass = $row['is_featured'] ? 'featured-product' : '';
                                    $statusClass = strtolower(str_replace(' ', '-', $row['status_name']));
                                    
                                    $productDataJson = htmlspecialchars(json_encode($productData), ENT_QUOTES, 'UTF-8');
                                    echo "<div class='product-card {$featuredClass}' data-status='" . htmlspecialchars($row['status_name']) . "' 
                                          data-available-days='" . htmlspecialchars($row['available_days'] ?? '') . "'
                                          data-product='" . $productDataJson . "' onclick='openProductModalFromData(this)'>
                                            <div class='product-image'>
                                                <img src='../../../assets/" . htmlspecialchars($row['image_url'] ?: 'images/no-image.jpg') . "' alt='" . htmlspecialchars($row['name']) . "'>
                                            </div>
                                            <div class='product-info'>
                                                <h3>" . htmlspecialchars($row['name']) . "</h3>
                                                <p class='price'>₱" . number_format($row['price'], 2) . "</p>
                                                
                                                <div class='prdct-availability'>
                                                    <span class='status-badge status-{$statusClass}'>" . htmlspecialchars($row['status_name']) . "</span>
                                                    <p class='stock'>Stock: " . $row['quantity'] . "</p>";
                                    
                                    // Display available days if product has them
                                    if (!empty($row['available_days'])) {
                                        $abbreviated_days = str_replace(
                                            ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                                            ['S', 'M', 'T', 'W', 'Th', 'F', 'Sa'],
                                            $row['available_days']
                                        );
                                        echo "<p class='available-days'>Available: " . htmlspecialchars($abbreviated_days) . "</p>";
                                    }
                                    
                                    echo "</div>
                                                
                                                <div class='quantity-controls'>
                                                    <button type='button' onclick='event.stopPropagation(); updateQuantity(this, -1)'>-</button>
                                                    <input type='number' value='1' min='1' max='" . $row['quantity'] . "' onclick='event.stopPropagation()' onchange='validateQuantity(this)'>
                                                    <button type='button' onclick='event.stopPropagation(); updateQuantity(this, 1)'>+</button>
                                                </div>
                                                
                                                <button class='add-to-cart' onclick='event.stopPropagation(); addToCart(" . $row['id'] . ", this)'>Add to Cart</button>
                                            </div>
                                        </div>";
                                }
                            } else {
                                echo "<div class='no-products'>No products available for " . $today . " at the moment.</div>";
                            }
                            $stmt->close();
                            $conn->close();
                        ?>
            </div> <!-- End products-grid -->
        </div> <!-- End scroll-container -->
    </div> <!-- End main-container -->
</div> <!-- End wrapper -->

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
            
<!-- Available Today Cart JavaScript -->
<script src="availtoday-cart.js"></script>

<script>
    console.log('Product dashboard page JavaScript loaded');
    let productModalOpen = false;
    let totalProducts = 0;
    const itemsPerRow = 4;

    document.addEventListener('DOMContentLoaded', function() {
        const scrollContainer = document.getElementById('productScroll');
        const products = scrollContainer.querySelectorAll('.product-card');
        
        totalProducts = products.length;
        
        // Show scroll mode if more than 4 products
        if (totalProducts > itemsPerRow) {
            setupScroll();
        } else {
            // For 4 or less products, display them normally in a grid
            scrollContainer.classList.add('normal-grid');
        }
        
            // Initialize business hours functionality
        initBusinessHours();
        
        // Add a manual test button for debugging
        const testButton = document.createElement('button');
        testButton.textContent = 'Test Business Hours';
        testButton.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 1000; padding: 10px; background: #ff6b35; color: white; border: none; border-radius: 5px; cursor: pointer;';
        testButton.onclick = () => {
            console.log('Manual test button clicked');
            const now = new Date();
            const currentTime = now.toTimeString().slice(0, 5);
            console.log('Current time:', currentTime);
            console.log('Business hours:', businessHours);
            console.log('Is within business hours:', isWithinBusinessHours(currentTime));
            checkBusinessHoursAndUpdateDisplay();
        };
        document.body.appendChild(testButton);
    });

    function setupScroll() {
        const scrollContainer = document.getElementById('productScroll');
        scrollContainer.classList.add('scroll-mode');
        
        // No additional scroll listeners needed - native scrolling handles everything
    }

    // Smooth scroll to specific position
    function scrollToPosition(scrollContainer, targetPosition) {
        scrollContainer.scrollTo({
            left: targetPosition,
            behavior: 'smooth'
        });
    }

    // Available Today cart functions are now handled by availtoday-cart.js

    // Business Hours Management
    let businessHours = {
        openingTime: '08:00',
        closingTime: '17:00'
    };

    function initBusinessHours() {
        // Ensure cart is visible by default
        const cartDropdown = document.querySelector('.cart-dropdown');
        if (cartDropdown) {
            cartDropdown.style.display = 'block';
            console.log('Cart dropdown set to visible by default');
        }
        
        loadBusinessHours();
        // Check immediately after loading
        setTimeout(() => {
            checkBusinessHoursAndUpdateDisplay();
        }, 100);
        // Check every minute
        setInterval(checkBusinessHoursAndUpdateDisplay, 60000);
        
        // Fallback check after 2 seconds in case fetch fails
        setTimeout(() => {
            if (!businessHours.openingTime || !businessHours.closingTime) {
                console.log('Fallback: Using default business hours');
                businessHours.openingTime = '08:00';
                businessHours.closingTime = '17:00';
                checkBusinessHoursAndUpdateDisplay();
            }
        }, 2000);
    }

    function loadBusinessHours() {
        fetch('get-business-hours.php')
            .then(response => response.json())
            .then(data => {
                console.log('Business hours data received:', data);
                if (data.success && data.businessHours) {
                    businessHours.openingTime = data.businessHours.opening_time;
                    businessHours.closingTime = data.businessHours.closing_time;
                    console.log('Business hours loaded:', businessHours);
                    updateTimerDisplay();
                    
                    // Immediately check business hours after loading
                    setTimeout(() => {
                        checkBusinessHoursAndUpdateDisplay();
                    }, 50);
                } else {
                    console.error('Failed to load business hours:', data);
                }
            })
            .catch(error => {
                console.error('Error loading business hours:', error);
            });
    }

    function checkBusinessHoursAndUpdateDisplay() {
        const now = new Date();
        const currentTime = now.toTimeString().slice(0, 5); // HH:MM format
        
        console.log('Current date/time info:', {
            fullDate: now.toString(),
            timeString: now.toTimeString(),
            currentTime: currentTime,
            hours: now.getHours(),
            minutes: now.getMinutes(),
            timezone: now.getTimezoneOffset()
        });
        
        const isOpen = isWithinBusinessHours(currentTime);
        
        console.log('Business Hours Check:', {
            currentTime: currentTime,
            openingTime: businessHours.openingTime,
            closingTime: businessHours.closingTime,
            isOpen: isOpen,
            businessHours: businessHours
        });
        
        updateProductVisibility(isOpen);
        updateTimerDisplay();
    }

    function isWithinBusinessHours(currentTime) {
        console.log('Time comparison:', {
            currentTime: currentTime,
            openingTime: businessHours.openingTime,
            closingTime: businessHours.closingTime,
            currentTimeType: typeof currentTime,
            openingTimeType: typeof businessHours.openingTime,
            closingTimeType: typeof businessHours.closingTime
        });
        
        // Ensure we have valid business hours
        if (!businessHours.openingTime || !businessHours.closingTime) {
            console.log('Business hours not loaded yet, defaulting to open');
            return true;
        }
        
        // Convert times to minutes for easier comparison
        const currentMinutes = parseInt(currentTime.split(':')[0]) * 60 + parseInt(currentTime.split(':')[1]);
        const openingMinutes = parseInt(businessHours.openingTime.split(':')[0]) * 60 + parseInt(businessHours.openingTime.split(':')[1]);
        const closingMinutes = parseInt(businessHours.closingTime.split(':')[0]) * 60 + parseInt(businessHours.closingTime.split(':')[1]);
        
        console.log('Time comparison in minutes:', {
            currentMinutes: currentMinutes,
            openingMinutes: openingMinutes,
            closingMinutes: closingMinutes,
            isOpen: currentMinutes >= openingMinutes && currentMinutes < closingMinutes
        });
        
        return currentMinutes >= openingMinutes && currentMinutes < closingMinutes;
    }

    function updateProductVisibility(isOpen) {
        const productsGrid = document.getElementById('productScroll');
        const title = document.querySelector('.prdct-title');
        const subtitle = document.querySelector('.prdct-subtitle');
        const cartDropdown = document.querySelector('.cart-dropdown');
        
        console.log('updateProductVisibility called with isOpen:', isOpen);
        console.log('Products grid element:', productsGrid);
        console.log('Title element:', title);
        console.log('Subtitle element:', subtitle);
        console.log('Cart dropdown element:', cartDropdown);
        
        if (!isOpen) {
            // Hide products and show closing message
            console.log('Hiding products - setting display to none');
            productsGrid.style.display = 'none';
            title.textContent = 'Products Currently Unavailable';
            subtitle.innerHTML = `Check again tomorrow for available pre-made breads!<br><span style="color: #ff6b35; font-weight: 600;">Business Hours: ${formatTimeForDisplay(businessHours.openingTime)} - ${formatTimeForDisplay(businessHours.closingTime)}</span>`;
            
            // Hide cart and clear cart data when business hours are closed
            if (cartDropdown) {
                console.log('Hiding cart dropdown and clearing cart data');
                cartDropdown.style.display = 'none';
                
                // Clear the cart data using the function from availtoday-cart.js
                if (typeof clearAvailableTodayCart === 'function') {
                    console.log('Calling clearAvailableTodayCart function');
                    clearAvailableTodayCart();
                    console.log('Cart data cleared successfully');
                } else {
                    console.log('clearAvailableTodayCart function not available - trying alternative approach');
                    // Alternative: manually clear cart data
                    if (typeof availableTodayCart !== 'undefined') {
                        availableTodayCart = [];
                        availableTodayCartTotal = 0;
                        console.log('Cart data manually cleared');
                    }
                    // Also try to clear localStorage
                    try {
                        localStorage.removeItem('availableTodayCart');
                        localStorage.removeItem('availableTodayCartTotal');
                        console.log('Cart data cleared from localStorage');
                    } catch (e) {
                        console.log('Could not clear localStorage:', e);
                    }
                }
            }
        } else {
            // Show products and restore original title
            console.log('Showing products - setting display to grid');
            productsGrid.style.display = 'grid';
            title.textContent = 'Available Today for Pick Up or Delivery!';
            subtitle.textContent = new Date().toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            // Show cart when business hours are open
            if (cartDropdown) {
                console.log('Showing cart dropdown');
                cartDropdown.style.display = 'block';
                
                // Also ensure cart display is updated
                if (typeof updateAvailableTodayCartDisplay === 'function') {
                    console.log('Updating cart display after showing cart');
                    updateAvailableTodayCartDisplay();
                }
            }
        }
        
        console.log('Final products grid display style:', productsGrid.style.display);
        console.log('Final cart dropdown display style:', cartDropdown ? cartDropdown.style.display : 'N/A');
    }

    function updateTimerDisplay() {
        const timerValue = document.getElementById('availTodayTimerValue');
        if (timerValue) {
            // Format the time to be more readable (e.g., "5:00 PM" instead of "17:00")
            const formattedTime = formatTimeForDisplay(businessHours.closingTime);
            timerValue.textContent = formattedTime;
        }
    }

    function formatTimeForDisplay(timeString) {
        // Convert 24-hour format to 12-hour format
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    function updateQuantity(button, change) {
        const container = button.parentElement;
        const input = container.querySelector('input');
        const newValue = parseInt(input.value) + change;
        if (newValue >= parseInt(input.min) && newValue <= parseInt(input.max)) {
            input.value = newValue;
        }
    }

    function validateQuantity(input) {
        const value = parseInt(input.value);
        const max = parseInt(input.max);
        const min = parseInt(input.min);
        
        if (isNaN(value) || value < min) {
            input.value = min;
        } else if (value > max) {
            input.value = max;
        }
    }

    function updateModalQuantity(change) {
        const input = document.getElementById('modalQuantity');
        const max = parseInt(input.max);
        const newValue = parseInt(input.value) + change;
        if (newValue >= 1 && newValue <= max) {
            input.value = newValue;
        }
    }

    function validateModalQuantity() {
        const input = document.getElementById('modalQuantity');
        const value = parseInt(input.value);
        const max = parseInt(input.max);
        
        if (isNaN(value) || value < 1) {
            input.value = 1;
        } else if (value > max) {
            input.value = max;
        }
    }

    function showConfirmation(message, isError = false) {
        const popup = document.getElementById('confirmationPopup');
        popup.textContent = message;
        popup.className = 'confirmation-popup' + (isError ? ' error' : '');
        popup.classList.add('show');
        
        setTimeout(() => {
            popup.classList.remove('show');
            popup.classList.add('hide');
            setTimeout(() => {
                popup.classList.remove('hide');
            }, 300);
        }, 3000);
    }

    function addToCart(productId, button, quantity = null) {
        console.log('addToCart called with:', { productId, button, quantity });
        
        let finalQuantity;
        let isAvailableToday = false;
        
        if (button) {
            // Called from product card button
            const productCard = button.closest('.product-card');
            if (!productCard) {
                showConfirmation("Error: Product not found", true);
                return;
            }
            
            // Verify this is an Available Today product
            const statusAttribute = productCard.getAttribute('data-status');
            const statusElement = productCard.querySelector('.status-badge');
            isAvailableToday = (statusAttribute === 'Available Today') || 
                             (statusElement && statusElement.classList.contains('status-available-today'));
            
            const quantityInput = button.parentElement.querySelector('input');
            finalQuantity = quantity || (quantityInput ? parseInt(quantityInput.value) : 1);
        } else {
            // Called from modal - all products on this page are Available Today (status_id = 3)
            isAvailableToday = true; // This page only shows Available Today products
            finalQuantity = quantity || 1;
        }
        
        if (!isAvailableToday) {
            showConfirmation("Only 'Available Today' products can be added to cart from this page", true);
            return;
        }
        
        console.log('Final quantity:', finalQuantity);

        // Use Available Today cart API instead of main cart API
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', finalQuantity);
        
        fetch("../../../backend/pages/cart/availtoday-cart-api.php", {
            method: "POST",
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Available Today Cart API Response:', data);
            
            if (data && data.success) {
                console.log('Product added to Available Today cart successfully');
                showConfirmation(`${data.product_name || 'Product'} added to Available Today cart!`);
                
                // Sync the Available Today cart display with server
                if (typeof syncWithServer === 'function') {
                    syncWithServer();
                } else {
                    // Fallback: manually add to local cart for immediate UI feedback
                    if (typeof addToAvailableTodayCart === 'function') {
                        addToAvailableTodayCart(productId, finalQuantity, button);
                    }
                }
                
                if (productModalOpen) closeProductModal();
            } else if (data) {
                console.log('Error in Available Today cart response:', data.error);
                showConfirmation("Error: " + (data.error || "Unknown error"), true);
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            console.error("Error message:", error.message);
            // Don't show error message if it's a redirect (user will be redirected to login)
            if (!error.message.includes('redirect')) {
                showConfirmation("An error occurred while adding to cart", true);
            }
        });
    }

    function openProductModalFromData(cardElement) {
        try {
            const productData = cardElement.getAttribute('data-product');
            if (!productData) {
                console.error('No product data found');
                return;
            }

            const product = JSON.parse(productData);
            openProductModal(product);
        } catch (error) {
            console.error('Error parsing product data:', error);
            showConfirmation('An error occurred while opening the product details', true);
        }
    }

    function openProductModal(product) {
        try {
            if (!product || typeof product !== 'object') {
                console.error('Invalid product data:', product);
                return;
            }

            productModalOpen = true;
            const modal = document.getElementById('productModal');
            const mainImage = document.getElementById('modalMainImage');
            const thumbnails = document.getElementById('thumbnailContainer');
            const productName = document.getElementById('modalProductName');
            const productPrice = document.getElementById('modalProductPrice');
            const productStatus = document.getElementById('modalProductStatus');
            const productDescription = document.getElementById('modalProductDescription');
            const productStock = document.getElementById('modalProductStock');
            const productAvailableDays = document.getElementById('modalProductAvailableDays');
            const quantityInput = document.getElementById('modalQuantity');
            const addToCartBtn = document.getElementById('modalAddToCart');

            // Set main content
            productName.textContent = product.name || 'Unknown Product';
            productPrice.textContent = '₱' + (parseFloat(product.price) || 0).toFixed(2);
            productStatus.textContent = product.status || 'Available Today';
            productStatus.className = 'status-badge status-' + (product.status || '').toLowerCase().replace(' ', '-');
            productDescription.textContent = product.description || 'No description available';
            productStock.textContent = 'Stock: ' + (product.quantity || 0);

            // Handle available days in modal
            if (product.available_days && product.available_days.length > 0) {
                const dayAbbreviations = {
                    'Sunday': 'S',
                    'Monday': 'M', 
                    'Tuesday': 'T',
                    'Wednesday': 'W',
                    'Thursday': 'Th',
                    'Friday': 'F',
                    'Saturday': 'Sa'
                };
                const abbreviatedDays = product.available_days.map(day => dayAbbreviations[day] || day);
                productAvailableDays.textContent = 'Available: ' + abbreviatedDays.join(', ');
                productAvailableDays.style.display = 'block';
            } else {
                productAvailableDays.style.display = 'none';
            }

            // Set quantity input max value
            quantityInput.max = product.quantity || 0;
            quantityInput.value = 1;

            // Set up images
            if (product.images && Array.isArray(product.images) && product.images.length > 0) {
                mainImage.src = '../../../assets/' + product.images[0];
                thumbnails.innerHTML = '';
                product.images.forEach((image, index) => {
                    if (image) {
                        const thumb = document.createElement('img');
                        thumb.src = '../../../assets/' + image;
                        thumb.alt = `${product.name || 'Product'} view ${index + 1}`;
                        thumb.onclick = () => mainImage.src = thumb.src;
                        thumbnails.appendChild(thumb);
                    }
                });
            } else {
                mainImage.src = '../../../assets/images/no-image.jpg';
                thumbnails.innerHTML = '';
            }

            // Set up Add to Cart button
            addToCartBtn.disabled = false;
            addToCartBtn.textContent = 'Add to Cart';
            addToCartBtn.classList.remove('unavailable');
            quantityInput.disabled = false;
            addToCartBtn.onclick = () => {
                if (product.id) {
                    addToCart(product.id, null, parseInt(quantityInput.value));
                }
            };

            modal.style.display = 'block';
        } catch (error) {
            console.error('Error in openProductModal:', error);
            showConfirmation('An error occurred while opening the product details', true);
        }
    }

    function closeProductModal() {
        productModalOpen = false;
        document.getElementById('productModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('productModal');
        if (event.target == modal) {
            closeProductModal();
        }
    }

    // Add touch/swipe support for mobile
    // Enhanced touch/swipe support for smooth scrolling
    let isScrolling = false;
    let startX = 0;
    let scrollLeft = 0;

    document.addEventListener('touchstart', function(e) {
        const scrollContainer = document.getElementById('productScroll');
        if (!e.target.closest('#productScroll')) return;
        
        isScrolling = true;
        startX = e.touches[0].clientX;
        scrollLeft = scrollContainer.scrollLeft;
    }, { passive: true });

    document.addEventListener('touchmove', function(e) {
        if (!isScrolling) return;
        
        const scrollContainer = document.getElementById('productScroll');
        if (!e.target.closest('#productScroll')) return;
        
        const x = e.touches[0].clientX;
        const walk = (startX - x) * 2; // Scroll speed multiplier
        scrollContainer.scrollLeft = scrollLeft + walk;
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        isScrolling = false;
    }, { passive: true });
</script>

<style>
    input[type="number"] {
        -moz-appearance: textfield;
    }
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
</style>
            
            <div class="blog-container">
                <div class="blog-section animate-fade-in-left">
                <a href="weekly-product.php" class="blog-link">
                        <img src="../../../assets/images/43384560.jpg" alt="Weekly Available">
                        <div class="section-title">
                            <span>DELIVERY</span>
                        </div>
                    </a>
                </div>

                <div class="blog-section animate-fade-in-right">
                    <a href="user-products.php" class="blog-link">
                        <img src="../../../assets/images/43387632.JPG" alt="All Products">
                        <div class="section-title">
                            <span>PICK UP</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="footer">
            <a href="about-page.php">About Us</a>
            <a href="terms.php">Terms and Conditions</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
    </div>
</div>