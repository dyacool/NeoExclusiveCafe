<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define preview mode
$is_preview_mode = !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);

$page_title = "Weekly Products";
$additional_css = [
    "../products/weekly-product.css",
    "../../confirmations.css"
];

require_once('../../user-includes/database.php');
require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once __DIR__ . "/../../user-includes/user-header.php";

// Only redirect to login if trying to access protected features
$current_page = basename($_SERVER['PHP_SELF']);
$protected_pages = [
    'profile.php',
    'orders.php',
    'cart.php',
    'checkout.php'
];

if (!$is_preview_mode && in_array($current_page, $protected_pages)) {
    header("Location: ../../pages/auth/login-signup.php");
    exit();
}

// Check verification only for logged-in users
if (!$is_preview_mode && (!isset($_SESSION['is_verified']) || $_SESSION['is_verified'] !== true)) {
    header("Location: ../../pages/auth/verification-page.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<div id="confirmationPopup" class="confirmation-popup"></div>
<div id="confirmAddToCartModal" class="modal" style="display: none;">
    <div class="modal-content fade-in-pop">
        <span class="close" onclick="closeConfirmModal()">&times;</span>
        <div class="modal-body">
            <h2>Confirm Add to Cart</h2>
            <p>Are you sure you want to add this item to your cart?</p>
            <div class="modal-actions">
                <button class="cancel-btn" onclick="closeConfirmModal()">Cancel</button>
                <button class="confirm-btn" onclick="confirmAddToCart()">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="wrapper">
    <button class="cta" onclick="window.location.href='/frontend/pages/products/product-dashboard.php'">
            <svg
                id="arrow-horizontal"
                xmlns="http://www.w3.org/2000/svg"
                width="30"
                height="10"
                viewBox="0 0 46 16"
            >
                <path
                id="Path_10"
                data-name="Path 10"
                d="M38,0,39.455,1.455,33.949,6.961H76V9.039H33.949l5.506,5.506L38,16l-8-8Z"
                transform="translate(-25)"
                ></path>
            </svg>
            <span class="hover-underline-animation"> Go Back </span>
        </button>   
    <h1 class="prdct-title">Delivery Products</h1>
    <div class="main-container fade-in">
        <div class="products-grid" id="productsGrid">
            <?php
                $sql = "SELECT 
                            p.id, p.name, p.price, p.description, p.status_id, p.is_featured,
                            ps.name AS status_name, pi.image_url, p.quantity, p.show_when_unavailable,
                            GROUP_CONCAT(pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days
                        FROM products p
                        LEFT JOIN product_statuses ps ON p.status_id = ps.id
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                        LEFT JOIN product_day pd ON p.id = pd.product_id
                        WHERE p.deleted_at IS NULL 
                        AND ps.name = 'Delivery'
                        AND (p.status_id != 3 
                            OR (p.status_id = 3 AND p.show_when_unavailable = 1))
                        GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, ps.name, pi.image_url, p.quantity, p.show_when_unavailable
                        ORDER BY p.is_featured DESC, p.status_id ASC";
        
                $result = $conn->query($sql);

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
                            'id' => (int)$row['id'],
                            'name' => $row['name'] ?? 'Unknown Product',
                            'price' => (float)$row['price'],
                            'description' => $row['description'] ?? 'No description available',
                            'status' => $row['status_name'] ?? 'Unknown',
                            'images' => array_filter($images), // Remove any null/empty values
                            'is_featured' => (bool)$row['is_featured'],
                            'quantity' => (int)$row['quantity'],
                            'show_when_unavailable' => (bool)$row['show_when_unavailable'],
                            'available_days' => $row['available_days'] ? explode(', ', $row['available_days']) : []
                        ];
                        
                        // Encode the data for JavaScript
                        $jsonData = json_encode($productData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
                        if ($jsonData === false) {
                            error_log("JSON encode error for product {$row['id']}: " . json_last_error_msg());
                            continue; // Skip this product if JSON encoding fails
                        }
                        
                        $featuredClass = $row['is_featured'] ? 'featured-product' : '';
                        $isUnavailable = $row['status_id'] == 3 || $row['quantity'] <= 0;
                        $statusClass = strtolower(str_replace(' ', '-', $row['status_name'] ?? 'unknown'));
                        
                        // Use data attribute instead of onclick to prevent escaping issues
                        echo "<div class='product-card {$featuredClass}' 
                              data-status='" . htmlspecialchars($row['status_name'] ?? 'Unknown') . "'
                              data-product='" . htmlspecialchars($jsonData, ENT_QUOTES, 'UTF-8') . "'
                              onclick='handleProductClick(this)'>";
                        echo "<div class='product-image'>
                                    <img src='/assets/" . htmlspecialchars($row['image_url'] ?: 'images/no-image.jpg') . "' alt='" . htmlspecialchars($row['name']) . "' width='50'>";
                        if ($row['is_featured']) {
                            echo "<span class='featured-badge'>Featured</span>";
                        }
                        echo "</div>
                                <div class='product-info'>
                                    <h3>" . htmlspecialchars($row['name']) . "</h3>
                                    <p class='price'>₱" . number_format($row['price'], 2) . "</p>
                                    <div class= 'product-availability'>
                                        <span class='status-badge status-{$statusClass}'>" . ($isUnavailable ? "Not Available" : htmlspecialchars($row['status_name'])) . "</span>
                                        <p class='stock'>Stock: " . $row['quantity'] . "</p>
                                    </div>";

                        if (!$isUnavailable) {
                            echo "<div class='quantity-controls'>
                                    <button type='button' onclick='event.stopPropagation(); updateQuantity(this, -1)'>-</button>
                                    <input type='number' value='1' min='1' max='" . $row['quantity'] . "' onclick='event.stopPropagation()' onchange='validateQuantity(this)'>
                                    <button type='button' onclick='event.stopPropagation(); updateQuantity(this, 1)'>+</button>
                                </div>";
                            echo "<button class='add-to-cart' onclick='event.stopPropagation(); addToCart(" . $row['id'] . ", this)'>Add to Cart</button>";
                        } else {
                            echo "<button class='add-to-cart unavailable' disabled>Currently Unavailable</button>";
                        }
                        
                        echo "</div></div>";
                    }
                } else {
                    echo "<div class='no-products'>No Delivery products available at the moment.</div>";
                }
                $conn->close();
            ?>
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
                <h2 class = "m-title" id="modalProductName"></h2>
                <p class="price" id="modalProductPrice"></p>
                <div class="prdct-qty">
                    <span class="status-badge" id="modalProductStatus"></span>
                    <p class="stock" id="modalProductStock"></p>
                </div>
                <h3 class="dscrptn">Description:</h3>
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

<script>
    let pendingCartAction = null;

    function filterProducts(status) {
        let cards = document.querySelectorAll(".product-card");
        cards.forEach(card => {
            if (status === "all") {
                card.style.display = "block";
            } else if (status === "Featured") {
                card.style.display = card.classList.contains('featured-product') ? "block" : "none";
            } else if (card.getAttribute("data-status") === status) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
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

    function showConfirmModal(productId, quantity) {
        pendingCartAction = { productId, quantity };
        document.getElementById('confirmAddToCartModal').style.display = 'block';
    }

    function closeConfirmModal() {
        document.getElementById('confirmAddToCartModal').style.display = 'none';
        pendingCartAction = null;
    }

    function confirmAddToCart() {
        if (pendingCartAction) {
            addToCart(pendingCartAction.productId, null, pendingCartAction.quantity);
            closeConfirmModal();
        }
    }

    function addToCart(productId, button, quantity = null) {
        const quantityInput = button ? button.parentElement.querySelector('input') : null;
        const finalQuantity = quantity || (quantityInput ? parseInt(quantityInput.value) : 1);

        fetch("../../pages/cart/add-to-cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `product_id=${productId}&quantity=${finalQuantity}`
        })
        .then(response => {
            // Check if response is a redirect (status 302) or if content-type is not JSON
            const contentType = response.headers.get('content-type');
            if (response.redirected || response.status === 302 || (contentType && !contentType.includes('application/json'))) {
                // If it's a redirect, follow it
                window.location.href = response.url;
                return;
            }
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                showConfirmation("Product added to cart successfully!");
            } else if (data) {
                showConfirmation("Error: " + (data.error || "Unknown error"), true);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            // Don't show error message if it's a redirect (user will be redirected to login)
            if (!error.message.includes('redirect')) {
                showConfirmation("An error occurred while adding to cart", true);
            }
        });
    }

    function openProductModal(product) {
        try {
            // Debug logging
            console.log('Product data received:', product);
            
            // Validate product data
            if (!product || typeof product !== 'object') {
                console.error('Invalid product data:', product);
                showConfirmation('Error: Invalid product data', true);
                return;
            }

            // Log individual properties
            console.log('Product properties:', {
                name: product.name,
                price: product.price,
                status: product.status,
                quantity: product.quantity,
                images: product.images
            });

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

            // Set main content with fallbacks
            productName.textContent = product.name || 'Unknown Product';
            productPrice.textContent = '₱' + (parseFloat(product.price) || 0).toFixed(2);
            productStatus.textContent = (!product.quantity || product.quantity <= 0 || product.status === 'Unavailable') ? 'Not Available' : (product.status || 'Unknown');
            productStatus.className = 'status-badge status-' + (product.status || '').toLowerCase().replace(' ', '-');
            productDescription.textContent = product.description || 'No description available';
            productStock.textContent = 'Stock: ' + (product.quantity || 0);

            // Set quantity input max value
            quantityInput.max = product.quantity || 0;
            quantityInput.value = 1;

            // Set up images
            if (product.images && Array.isArray(product.images) && product.images.length > 0) {
                mainImage.src = '/assets/' + product.images[0];
                
                // Clear existing thumbnails
                thumbnails.innerHTML = '';
                
                // Add all images as thumbnails
                product.images.forEach((image, index) => {
                    if (image) {
                        const thumb = document.createElement('img');
                        thumb.src = '/assets/' + image;
                        thumb.alt = `${product.name || 'Product'} view ${index + 1}`;
                        thumb.onclick = () => mainImage.src = thumb.src;
                        thumbnails.appendChild(thumb);
                    }
                });
            } else {
                mainImage.src = '/assets/images/no-image.jpg';
                thumbnails.innerHTML = '';
            }

            // Set up Add to Cart button
            const isUnavailable = !product.quantity || product.quantity <= 0 || product.status === 'Unavailable';
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
                    if (product.id) {
                        showConfirmModal(product.id, parseInt(quantityInput.value));
                    }
                };
            }

            modal.style.display = 'block';
        } catch (error) {
            console.error('Error in openProductModal:', error);
            showConfirmation('An error occurred while opening the product details', true);
        }
    }

    function closeProductModal() {
        document.getElementById('productModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('productModal');
        if (event.target == modal) {
            closeProductModal();
        }
    }

    // Add error handling for notifications
    async function fetchNotifications() {
        try {
            const response = await fetch('../../pages/notifications/fetch-notif.php');
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();
            if (data.status === 'success') {
                updateNotificationCount(data.count || 0);
            } else {
                console.error('Error fetching notifications:', data.message);
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    // Update notification count
    function updateNotificationCount(count) {
        const notifCount = document.getElementById('notificationCount');
        if (notifCount) {
            notifCount.textContent = count;
            notifCount.style.display = count > 0 ? 'block' : 'none';
        }
    }

    // Fetch notifications periodically
    if (document.getElementById('notificationCount')) {
        fetchNotifications();
        setInterval(fetchNotifications, 30000); // Refresh every 30 seconds
    }

    function handleProductClick(element) {
        try {
            const productData = JSON.parse(element.getAttribute('data-product'));
            openProductModal(productData);
        } catch (error) {
            console.error('Error parsing product data:', error);
            showConfirmation('Error: Could not load product details', true);
        }
    }
</script>

<style>
    #confirmAddToCartModal .modal-content {
        max-width: 400px;
        background: #fff;
        border-radius: 8px;
        padding: 20px;
    }

    #confirmAddToCartModal .modal-body {
        text-align: center;
    }

    #confirmAddToCartModal h2 {
        color: #333;
        margin-bottom: 15px;
    }

    #confirmAddToCartModal p {
        color: #666;
        margin-bottom: 20px;
    }

    #confirmAddToCartModal .modal-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    #confirmAddToCartModal .cancel-btn,
    #confirmAddToCartModal .confirm-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    #confirmAddToCartModal .cancel-btn {
        background: #f0f0f0;
        color: #333;
    }

    #confirmAddToCartModal .confirm-btn {
        background: #4CAF50;
        color: white;
    }

    #confirmAddToCartModal .cancel-btn:hover {
        background: #e0e0e0;
    }

    #confirmAddToCartModal .confirm-btn:hover {
        background: #45a049;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }

    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

</style>
