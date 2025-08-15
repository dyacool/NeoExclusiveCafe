<?php
// Redirect if not logged in
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();
$page_title = "Our Products";
$additional_css = [
    "../../pages/products/user-products.css",
    "../../confirmations.css"
];

require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once "../../user-includes/user-header.php";

// Check if filter parameter exists
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Set active filter for highlighting
$activeFilter = $filter;

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<div id="confirmationPopup" class="confirmation-popup"></div>

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
    <h1 class= "prdct-title"> Products </h1>

    <div class="main-container fade-in">

        <div class="filter-container">
            <div class="sort-container">
                <button class="filter-button active" data-filter="all" type="button" onclick="handleFilterClick(event, 'all', this)">All</button>
                <button class="filter-button" data-filter="Pickup" type="button" onclick="handleFilterClick(event, 'Pickup', this)">Pickup</button>
                <button class="filter-button" data-filter="Unavailable" type="button" onclick="handleFilterClick(event, 'Unavailable', this)">Unavailable</button>
                <button class="filter-button" data-filter="Featured" type="button" onclick="handleFilterClick(event, 'Featured', this)">Featured</button>
            </div>
        </div>

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
                        AND ps.name != 'Delivery'
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
                        $isUnavailable = $row['status_name'] == 'Unavailable' || $row['quantity'] <= 0;
                        $statusClass = strtolower(str_replace(' ', '-', $row['status_name']));
                        
                        $productDataJson = htmlspecialchars(json_encode($productData), ENT_QUOTES, 'UTF-8');
                        echo "<div class='product-card {$featuredClass}' data-status='" . htmlspecialchars($row['status_name']) . "' 
                              data-product='" . $productDataJson . "' onclick='openProductModalFromData(this)'>
                                <div class='product-image'>
                                    <img src='../../../assets/" . htmlspecialchars($row['image_url'] ?: 'images/no-image.jpg') . "' alt='" . htmlspecialchars($row['name']) . "'>";
                        if ($row['is_featured']) {
                            echo "<span class='featured-badge'>Featured</span>";
                        }
                        echo "</div>
                                <div class='product-info'>
                                    <h3>" . htmlspecialchars($row['name']) . "</h3>
                                    <p class='price'>₱" . number_format($row['price'], 2) . "</p>
                                    
                                    <div class='prdct-availability'>
                                        <span class='status-badge status-{$statusClass}'>" . ($isUnavailable ? "Not Available" : htmlspecialchars($row['status_name'])) . "</span>
                                        <p class='stock'>Stock: " . $row['quantity'] . "</p>
                                    </div>";
                        if (!$isUnavailable) {
                            echo "<div class='quantity-controls'>
                                    <button type='button' onclick='event.stopPropagation(); updateQuantity(this, -1)'>-</button>
                                    <input type='number' value='1' min='1' max='" . $row['quantity'] . "' onclick='event.stopPropagation()' onchange='validateQuantity(this)'>
                                    <button type='button' onclick='event.stopPropagation(); updateQuantity(this, 1)'>+</button>
                                </div>";
                            echo "<button class='add-to-cart' onclick='event.stopPropagation(); console.log(\"Add to Cart button clicked for product ID: " . $row['id'] . "\"); addToCart(" . $row['id'] . ", this)'>Add to Cart</button>";
                        
                        } else {
                            echo "<button class='add-to-cart unavailable' disabled>Currently Unavailable</button>";
                        }
                        
                        echo "</div></div>";
                    }
                } else {
                    echo "<div class='no-products'>No products available at the moment.</div>";
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
                <h2 class = "modal-title"id="modalProductName"></h2>
                <p class="modal-price" id="modalProductPrice"></p>
                <div class="prdct-qty">
                    <span class="status-badge" id="modalProductStatus"></span>
                    <p class="stock" id="modalProductStock"></p>
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

<script>
    console.log('User products page JavaScript loaded');
    let productModalOpen = false;

    function handleFilterClick(event, status, button) {
        event.preventDefault();
        event.stopPropagation();
        console.log('Filter button clicked:', status);
        filterProducts(status, button);
        return false;
    }

    function filterProducts(status, button) {
        // Update active state of buttons
        document.querySelectorAll('.filter-button').forEach(btn => {
            btn.classList.remove('active');
        });
        if (button) button.classList.add('active');

        // Filter products
        let cards = document.querySelectorAll(".product-card");
        cards.forEach(card => {
            if (status === "all") {
                card.style.display = "block";
            } else if (status === "Featured") {
                card.style.display = card.classList.contains('featured-product') ? "block" : "none";
            } else if (status === "Pickup") {
                // Show products with "Pick Up" status
                card.style.display = card.getAttribute("data-status") === "Pick Up" ? "block" : "none";
            } else if (status === "Unavailable") {
                // Show products that have "Not Available" status
                const statusBadge = card.querySelector('.status-badge');
                card.style.display = statusBadge.textContent === "Not Available" ? "block" : "none";
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

    function addToCart(productId, button, quantity = null) {
        console.log('addToCart called with:', { productId, button, quantity });
        
        const quantityInput = button ? button.parentElement.querySelector('input') : null;
        const finalQuantity = quantity || (quantityInput ? parseInt(quantityInput.value) : 1);
        
        console.log('Final quantity:', finalQuantity);

        fetch("../../pages/cart/add-to-cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `product_id=${productId}&quantity=${finalQuantity}`
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            // Check if response is a redirect (status 302) or if content-type is not JSON
            const contentType = response.headers.get('content-type');
            console.log('Content type:', contentType);
            
            if (response.redirected || response.status === 302 || (contentType && !contentType.includes('application/json'))) {
                // If it's a redirect, follow it
                console.log('Redirecting to:', response.url);
                window.location.href = response.url;
                return;
            }
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data && data.success) {
                console.log('Product added successfully');
                showConfirmation("Product added to cart successfully!");
                if (productModalOpen) closeProductModal();
            } else if (data) {
                console.log('Error in response:', data.error);
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
            const quantityInput = document.getElementById('modalQuantity');
            const addToCartBtn = document.getElementById('modalAddToCart');

            // Set main content
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
                        addToCart(product.id, null, parseInt(quantityInput.value));
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

    // Apply filter on page load if URL parameter exists
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const filter = urlParams.get('filter');
        
        // Filter buttons now use onclick handlers instead of event listeners
        
        // Apply initial filter if URL parameter exists
        if (filter) {
            const filterButton = document.querySelector(`[data-filter="${filter}"]`);
            if (filterButton) {
                filterProducts(filter, filterButton);
            }
        }
        
        // Modify the Add to Cart button setup for product cards
        document.querySelectorAll('.add-to-cart:not(.unavailable)').forEach(btn => {
            btn.onclick = function(e) {
                try {
                    e.stopPropagation();
                    const card = btn.closest('.product-card');
                    const productData = card ? card.getAttribute('data-product') : null;
                    if (!productData) return;
                    
                    let product;
                    try {
                        product = JSON.parse(productData);
                    } catch (parseError) {
                        console.error('Error parsing product data:', parseError);
                        return;
                    }

                    if (!product || !product.id) {
                        console.error('Invalid product data:', product);
                        return;
                    }

                    const quantityInput = btn.parentElement.querySelector('input');
                    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                    
                    addToCart(product.id, null, quantity);
                } catch (error) {
                    console.error('Error in Add to Cart click handler:', error);
                    showConfirmation('An error occurred while adding to cart', true);
                }
            };
        });
    });
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
    .back-btn-basic {
        display: inline-block;
        margin: 18px 0 10px 0;
        padding: 8px 18px;
        background: #256029;
        color: #fff;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        font-size: 1rem;
        border: none;
        transition: background 0.2s;
    }
    .back-btn-basic:hover {
        background: #1e4d2b;
    }
</style>
