<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

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
$conn = new mysqli("mysql-neoexclusivecafe.alwaysdata.net", "429123", "NeoCafe123", "neoexclusivecafe_crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

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

    <div class="filters">
        <h1 class="prdct-title">Delivery Products</h1>
        
        <!-- Sorting & Main Filters Section -->
        <div class="main-filters-section">
            <h2>Filters:</h2>

            <!-- Desktop Radio Buttons -->
            <div class="desktop-filters">
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="mainFilter" value="all" checked onchange="handleMainFilterChange()">
                        <span class="radio-label">All Delivery Products</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="mainFilter" value="unavailable" onchange="handleMainFilterChange()">
                        <span class="radio-label">Unavailable Products</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="mainFilter" value="alpha-asc" onchange="handleMainFilterChange()">
                        <span class="radio-label">Alphabetical (A–Z)</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="mainFilter" value="alpha-desc" onchange="handleMainFilterChange()">
                        <span class="radio-label">Alphabetical (Z–A)</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="mainFilter" value="price-asc" onchange="handleMainFilterChange()">
                        <span class="radio-label">Price (Low → High)</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="mainFilter" value="price-desc" onchange="handleMainFilterChange()">
                        <span class="radio-label">Price (High → Low)</span>
                    </label>
                </div>
            </div>
            
            <!-- Mobile Dropdown -->
            <div class="mobile-filters">
                <select id="mobileMainFilter" onchange="handleMobileMainFilterChange()">
                    <option value="all">All Delivery Products</option>
                    <option value="unavailable">Unavailable Products</option>
                    <option value="alpha-asc">Alphabetical (A–Z)</option>
                    <option value="alpha-desc">Alphabetical (Z–A)</option>
                    <option value="price-asc">Price (Low → High)</option>
                    <option value="price-desc">Price (High → Low)</option>
                </select>
            </div>
        </div>

        <!-- Day Filters Section -->
        <div class="day-filters-section" id="dayFiltersSection">
            <h4>Filter by Days:</h4>
            
            <!-- Desktop Checkboxes -->
            <div class="checkbox-group desktop-day-filters">
                <label class="checkbox-option">
                    <input type="checkbox" value="sunday" onchange="handleDayFilterChange()">
                    <span class="checkbox-label">Sunday</span>
                </label>
                <label class="checkbox-option">
                    <input type="checkbox" value="monday" onchange="handleDayFilterChange()">
                    <span class="checkbox-label">Monday</span>
                </label>
                <label class="checkbox-option">
                    <input type="checkbox" value="tuesday" onchange="handleDayFilterChange()">
                    <span class="checkbox-label">Tuesday</span>
                </label>
                <label class="checkbox-option">
                    <input type="checkbox" value="wednesday" onchange="handleDayFilterChange()">
                    <span class="checkbox-label">Wednesday</span>
                </label>
                <label class="checkbox-option">
                    <input type="checkbox" value="thursday" onchange="handleDayFilterChange()">
                    <span class="checkbox-label">Thursday</span>
                </label>
                <label class="checkbox-option">
                    <input type="checkbox" value="friday" onchange="handleDayFilterChange()">
                    <span class="checkbox-label">Friday</span>
                </label>
                <label class="checkbox-option">
                    <input type="checkbox" value="saturday" onchange="handleDayFilterChange()">
                    <span class="checkbox-label">Saturday</span>
                </label>
            </div>
            
            <!-- Mobile Filter Buttons -->
            <div class="mobile-day-filters">
                <div class="day-button-group">
                    <button type="button" class="day-filter-btn" data-day="sunday" onclick="toggleDayFilter(this)">Sun</button>
                    <button type="button" class="day-filter-btn" data-day="monday" onclick="toggleDayFilter(this)">Mon</button>
                    <button type="button" class="day-filter-btn" data-day="tuesday" onclick="toggleDayFilter(this)">Tue</button>
                    <button type="button" class="day-filter-btn" data-day="wednesday" onclick="toggleDayFilter(this)">Wed</button>
                    <button type="button" class="day-filter-btn" data-day="thursday" onclick="toggleDayFilter(this)">Thu</button>
                    <button type="button" class="day-filter-btn" data-day="friday" onclick="toggleDayFilter(this)">Fri</button>
                    <button type="button" class="day-filter-btn" data-day="saturday" onclick="toggleDayFilter(this)">Sat</button>
                </div>
            </div>
        </div>
    </div>
    
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
                            WHERE p.deleted_at IS NULL AND p.id > 0
                        AND (ps.name = 'Delivery' OR ps.name = 'Unavailable Delivery' OR ps.name = 'Unavailable Pick Up')
                        AND (p.status_id NOT IN (4, 5) 
                            OR (p.status_id IN (4, 5) AND p.show_when_unavailable = 1))
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
                        $isUnavailable = in_array($row['status_id'], [4, 5]) || $row['quantity'] <= 0;
                        $statusClass = strtolower(str_replace(' ', '-', $row['status_name'] ?? 'unknown'));
                        
                        // Use data attribute instead of onclick to prevent escaping issues
                        echo "<div class='product-card {$featuredClass}' 
                              data-status='" . htmlspecialchars($row['status_name'] ?? 'Unknown') . "'
                              data-available-days='" . htmlspecialchars($row['available_days'] ?? '') . "'
                              data-product='" . htmlspecialchars($jsonData, ENT_QUOTES, 'UTF-8') . "'
                              onclick='handleProductClick(this)'>";
                        echo "<div class='product-image'>
                                    <img src='../../../assets/" . htmlspecialchars($row['image_url'] ?: 'images/no-image.jpg') . "' alt='" . htmlspecialchars($row['name']) . "'>";
                        if ($row['is_featured']) {
                            echo "<span class='featured-badge'>Featured</span>";
                        }
                        echo "</div>
                                <div class='product-info'>
                                    <h3>" . htmlspecialchars($row['name']) . "</h3>
                                    <p class='price'>₱" . number_format($row['price'], 2) . "</p>
                                    <div class= 'product-availability'>
                                        <span class='status-badge status-{$statusClass}'>" . ($isUnavailable ? "Not Available" : htmlspecialchars($row['status_name'])) . "</span>
                                        <p class='stock'>Stock: " . $row['quantity'] . "</p>";
                        
                        echo "</div>";
                        echo "<div class='available-day'>";

                        // Display available days if product is not unavailable
                        if (!$isUnavailable && !empty($row['available_days'])) {
                            $abbreviated_days = str_replace(
                                ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                                ['S', 'M', 'T', 'W', 'Th', 'F', 'Sa'],
                                $row['available_days']
                            );
                            echo "<div class = 'display-days'> <p class='available-days'>Available days: </p>";
                            echo "<p class='p-days'>" . htmlspecialchars($abbreviated_days) . "</p> </div>";
                        }
                        
                        echo "</div>";

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
                <div class="modal-days">
                    <p class="available-days" id="modalProductAvailableDays" style="display: none;"></p>
                </div>
                <h3 class="dscrptn">Description:</h3>
                <div class="description" id="modalProductDescription"></div>
                <div class="modal-controls">
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
</div>

<script>
    let pendingCartAction = null;
    let currentMainFilter = 'all';
    let selectedDays = [];

    function handleMainFilterChange() {
        const radioButtons = document.querySelectorAll('input[name="mainFilter"]');
        const selectedRadio = document.querySelector('input[name="mainFilter"]:checked');
        currentMainFilter = selectedRadio.value;
        
        // Show/hide day filters based on selection
        const dayFiltersSection = document.getElementById('dayFiltersSection');
        if (currentMainFilter === 'all') {
            dayFiltersSection.style.display = 'block';
        } else {
            dayFiltersSection.style.display = 'none';
            // Clear day selections when hiding
            clearDaySelections();
        }
        
        applyFiltersAndSort();
    }

    function handleMobileMainFilterChange() {
        const mobileSelect = document.getElementById('mobileMainFilter');
        currentMainFilter = mobileSelect.value;
        
        // Update radio button to match mobile selection
        const radioButton = document.querySelector(`input[name="mainFilter"][value="${currentMainFilter}"]`);
        if (radioButton) {
            radioButton.checked = true;
        }
        
        // Show/hide day filters based on selection
        const dayFiltersSection = document.getElementById('dayFiltersSection');
        if (currentMainFilter === 'all') {
            dayFiltersSection.style.display = 'block';
        } else {
            dayFiltersSection.style.display = 'none';
            // Clear day selections when hiding
            clearDaySelections();
        }
        
        applyFiltersAndSort();
    }

    function handleDayFilterChange() {
        const dayCheckboxes = document.querySelectorAll('.checkbox-group input[type="checkbox"]');
        selectedDays = [];
        
        dayCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedDays.push(checkbox.value);
            }
            
            // Sync with mobile day filter buttons
            const mobileButton = document.querySelector(`.day-filter-btn[data-day="${checkbox.value}"]`);
            if (mobileButton) {
                if (checkbox.checked) {
                    mobileButton.classList.add('active');
                } else {
                    mobileButton.classList.remove('active');
                }
            }
        });
        
        applyFiltersAndSort();
    }

    function clearDaySelections() {
        const dayCheckboxes = document.querySelectorAll('.checkbox-group input[type="checkbox"]');
        dayCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Clear mobile day filter buttons
        const dayButtons = document.querySelectorAll('.day-filter-btn');
        dayButtons.forEach(button => {
            button.classList.remove('active');
        });
        
        selectedDays = [];
    }

    function toggleDayFilter(button) {
        const day = button.getAttribute('data-day');
        
        // Toggle button active state
        button.classList.toggle('active');
        
        // Update selectedDays array
        if (button.classList.contains('active')) {
            if (!selectedDays.includes(day)) {
                selectedDays.push(day);
            }
        } else {
            selectedDays = selectedDays.filter(d => d !== day);
        }
        
        // Sync with desktop checkboxes
        const checkbox = document.querySelector(`input[type="checkbox"][value="${day}"]`);
        if (checkbox) {
            checkbox.checked = button.classList.contains('active');
        }
        
        applyFiltersAndSort();
    }

    function applyFiltersAndSort() {
        let cards = Array.from(document.querySelectorAll(".product-card"));
        
        // Filter cards first
        cards.forEach(card => {
            let shouldShow = false;
            const statusName = card.getAttribute("data-status");
            const availableDays = card.getAttribute("data-available-days") || "";
            
            switch (currentMainFilter) {
                case 'all':
                    if (selectedDays.length === 0) {
                        // Show all non-unavailable products
                        shouldShow = !(statusName === "Unavailable Pick Up" || statusName === "Unavailable Delivery");
                    } else {
                        // Filter by selected days
                        const isUnavailable = statusName === "Unavailable Pick Up" || statusName === "Unavailable Delivery";
                        if (!isUnavailable) {
                            shouldShow = selectedDays.some(day => {
                                const dayToCheck = day.charAt(0).toUpperCase() + day.slice(1);
                                return availableDays.includes(dayToCheck);
                            });
                        }
                    }
                    break;
                    
                case 'unavailable':
                    shouldShow = (statusName === "Unavailable Pick Up" || statusName === "Unavailable Delivery");
                    break;
                    
                default:
                    // For sorting options, show all non-unavailable products
                    shouldShow = !(statusName === "Unavailable Pick Up" || statusName === "Unavailable Delivery");
                    break;
            }
            
            card.style.display = shouldShow ? "block" : "none";
        });
        
        // Sort visible cards
        if (currentMainFilter.includes('alpha-') || currentMainFilter.includes('price-')) {
            sortProducts(currentMainFilter);
        }
    }

    function sortProducts(sortType) {
        const productsGrid = document.getElementById('productsGrid');
        const cards = Array.from(productsGrid.querySelectorAll('.product-card')).filter(card => 
            card.style.display !== 'none'
        );
        
        cards.sort((a, b) => {
            switch (sortType) {
                case 'alpha-asc':
                    const nameA = a.querySelector('h3').textContent.toLowerCase();
                    const nameB = b.querySelector('h3').textContent.toLowerCase();
                    return nameA.localeCompare(nameB);
                    
                case 'alpha-desc':
                    const nameA2 = a.querySelector('h3').textContent.toLowerCase();
                    const nameB2 = b.querySelector('h3').textContent.toLowerCase();
                    return nameB2.localeCompare(nameA2);
                    
                case 'price-asc':
                    const priceA = parseFloat(a.querySelector('.price').textContent.replace('₱', '').replace(',', ''));
                    const priceB = parseFloat(b.querySelector('.price').textContent.replace('₱', '').replace(',', ''));
                    return priceA - priceB;
                    
                case 'price-desc':
                    const priceA2 = parseFloat(a.querySelector('.price').textContent.replace('₱', '').replace(',', ''));
                    const priceB2 = parseFloat(b.querySelector('.price').textContent.replace('₱', '').replace(',', ''));
                    return priceB2 - priceA2;
                    
                default:
                    return 0;
            }
        });
        
        // Re-append sorted cards
        cards.forEach(card => {
            productsGrid.appendChild(card);
        });
    }

    // Legacy function for backward compatibility (can be removed if not used elsewhere)
    function filterProducts() {
        // This function is kept for backward compatibility but redirects to new system
        applyFiltersAndSort();
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
            const productAvailableDays = document.getElementById('modalProductAvailableDays');
            const quantityInput = document.getElementById('modalQuantity');
            const addToCartBtn = document.getElementById('modalAddToCart');

            // Set main content with fallbacks
            productName.textContent = product.name || 'Unknown Product';
            productPrice.textContent = '₱' + (parseFloat(product.price) || 0).toFixed(2);
            productStatus.textContent = (!product.quantity || product.quantity <= 0 || product.status === 'Unavailable Pick Up' || product.status === 'Unavailable Delivery') ? 'Not Available' : (product.status || 'Unknown');
            productStatus.className = 'status-badge status-' + (product.status || '').toLowerCase().replace(' ', '-');
            productDescription.textContent = product.description || 'No description available';
            productStock.textContent = 'Stock: ' + (product.quantity || 0);
            
            // Handle available days in modal
            if (product.available_days && product.available_days.length > 0 && product.status !== 'Unavailable Pick Up' && product.status !== 'Unavailable Delivery' && product.quantity > 0) {
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
                productAvailableDays.textContent = 'Available Days: ' + abbreviatedDays.join(', ');
                productAvailableDays.style.display = 'block';
            } else {
                productAvailableDays.style.display = 'none';
            }

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
            const isUnavailable = !product.quantity || product.quantity <= 0 || product.status === 'Unavailable Pick Up' || product.status === 'Unavailable Delivery';
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

    /* New Filter Section Styles */


    .main-filters-section {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }

    .main-filters-section h2 {
        align: left;
        font-size: 1.2em;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        letter-spacing: 0.5px;
    }

    /* Desktop Radio Buttons */
    .desktop-filters {
        display: block;
    }

    .radio-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .radio-option {
        display: flex;
        align-items: center;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 6px;
        transition: background-color 0.3s ease;
    }

    .radio-option:hover {
        background-color: #f8f9fa;
    }

    .radio-option input[type="radio"] {
        margin-right: 10px;
        width: 16px;
        height: 16px;
        accent-color: #1a4a28;
    }

    .radio-label {
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }

    /* Mobile Dropdown */
    .mobile-filters {
        display: none;
    }

    .mobile-filters label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        display: block;
        font-size: 14px;
    }

    .mobile-filters select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        background: #f8f9fa;
        color: #333;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .mobile-filters select:focus {
        outline: none;
        border-color: #1a4a28;
        box-shadow: 0 0 0 3px rgba(26, 74, 40, 0.1);
    }

    /* Day Filters Section */
    .day-filters-section {
        display: block;
    }

    .day-filters-section h4 {
        font-size: 1em;
        font-weight: 600;
        color: #333;
        margin-bottom: 12px;
        letter-spacing: 0.5px;
    }

    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 8px;
    }

    .checkbox-option {
        display: flex;
        align-items: center;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 6px;
        transition: background-color 0.3s ease;
    }

    .checkbox-option:hover {
        background-color: #f8f9fa;
    }

    .checkbox-option input[type="checkbox"] {
        margin-right: 10px;
        width: 16px;
        height: 16px;
        accent-color: #1a4a28;
    }

    .checkbox-label {
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }

    /* Responsive Design */
    .wrapper {
        display: flex;
        gap: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .filters {
        width: 320px;
        height: fit-content;
        flex-shrink: 0;
    }

    .main-container {
        flex: 1;
        min-width: 0;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        padding: 0;
    }

    /* Desktop Day Filters (Checkboxes) */
    .desktop-day-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 8px;
    }

    /* Mobile Day Filters (Buttons) */
    .mobile-day-filters {
        display: none;
    }

    .day-button-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
    }

    .day-filter-btn {
        padding: 8px 12px;
        border: 2px solid #e5e7eb;
        background: #f8f9fa;
        color: #333;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 40px;
    }

    .day-filter-btn:hover {
        border-color: #1a4a28;
        background: #f0f8f0;
    }

    .day-filter-btn.active {
        background: #1a4a28;
        color: white;
        border-color: #1a4a28;
    }

    @media (max-width: 1200px) {
        .filters {
            width: 280px;
        }
        
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    @media (max-width: 992px) {
        .wrapper {
            gap: 15px;
            padding: 15px;
        }
        
        .filters {
            width: 250px;
            padding: 20px;
        }
        
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }
    }

    @media (max-width: 768px) {
        .wrapper {
            flex-direction: column;
            padding: 10px;
            gap: 15px;
        }
        
        .filters {
            width: 100%;
            padding: 15px;
            order: -1;
            margin-bottom: 0;
        }
        
        .main-container {
            order: 1;
        }
        
        .desktop-filters {
            display: none;
        }
        
        .mobile-filters {
            display: block;
        }
        
        .desktop-day-filters {
            display: none;
        }
        
        .mobile-day-filters {
            display: block;
        }
        
        .main-filters-section {
            margin-bottom: 15px;
            padding-bottom: 15px;
        }
        
        .day-filters-section h4 {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
    }

    @media (max-width: 480px) {
        .wrapper {
            padding: 8px;
        }
        
        .filters {
            padding: 12px;
        }
        
        .products-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .day-button-group {
            gap: 6px;
        }
        
        .day-filter-btn {
            font-size: 11px;
            padding: 6px 10px;
            min-width: 35px;
        }
    }

</style>
