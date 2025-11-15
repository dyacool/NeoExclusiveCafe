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
$conn = new mysqli("mysql-neoexclusivecafe.alwaysdata.net", "429123", "NeoCafe123", "neoexclusivecafe_crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

<div id="confirmationPopup" class="confirmation-popup"></div>

<div class="wrapper">

    <div class="filters">
        <h1 class="prdct-title">Pick-up Products</h1>
        
        <!-- Sorting & Main Filters Section -->
        <div class="main-filters-section">
            <h2>Filters:</h2>

            <!-- Desktop Radio Buttons -->
            <div class="desktop-filters">
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="mainFilter" value="all" checked onchange="handleMainFilterChange()">
                        <span class="radio-label">All Pre-order Products</span>
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
                <label for="mobileMainFilter">Filter Options:</label>
                <select id="mobileMainFilter" onchange="handleMobileMainFilterChange()">
                    <option value="all">All Pre-order Products</option>
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
                        AND (p.status_id = 1 OR p.availtoday_status_id = 1)
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
                              data-available-days='" . htmlspecialchars($row['available_days'] ?? '') . "' 
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
                                    
                                    <div class='product-availability'>
                                        <span class='status-badge status-{$statusClass}'>" . ($isUnavailable ? "Not Available" : htmlspecialchars($row['status_name'])) . "</span>
                                        <p class='stock'>Stock: " . $row['quantity'] . "</p>
                                    </div>";
                        
                        // Display available days similar to weekly-product
                        if (!$isUnavailable && !empty($row['available_days'])) {
                            $abbreviated_days = str_replace(
                                ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                                ['S', 'M', 'T', 'W', 'Th', 'F', 'Sa'],
                                $row['available_days']
                            );
                            echo "<div class='available-day'>";
                            echo "<div class = 'display-days'> <p class='available-days'>Pick-Up days: </p>";
                            echo "<p class='p-days'>" . htmlspecialchars($abbreviated_days) . "</p> </div>";
                            echo "</div>";
                        }
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
                    echo "<div class='no-products'>No products available at the moment.</div>";
                }
                $conn->close();
            ?>
        </div>
    </div>
</div>

<div id="footer-container">
    <?php require_once "../../user-includes/user-footer.php"; ?>
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
    let currentMainFilter = 'all';
    let selectedDays = [];

    function handleMainFilterChange() {
        const radioButtons = document.querySelectorAll('input[name="mainFilter"]');
        const selectedRadio = document.querySelector('input[name="mainFilter"]:checked');
        currentMainFilter = selectedRadio.value;
        
        // Update mobile dropdown to match desktop selection
        const mobileSelect = document.getElementById('mobileMainFilter');
        if (mobileSelect) {
            mobileSelect.value = currentMainFilter;
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

            modal.classList.add('show');
        } catch (error) {
            console.error('Error in openProductModal:', error);
            showConfirmation('An error occurred while opening the product details', true);
        }
    }

    function closeProductModal() {
        productModalOpen = false;
        const modal = document.getElementById('productModal');
        modal.classList.remove('show');
        modal.style.display = 'none';
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


    /* Responsive Design */
    .filters {
        width: 320px;
        height: fit-content;
        flex-shrink: 0;
    }

    .main-container {
        flex: 1;
        min-width: 0;
    }

    @media (max-width: 1200px) {
    .filters {
        width: 280px;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}


    @media (max-width: 1024px) {
        .wrapper {
            flex-direction: column;
            padding: 10px;
            gap: 15px;
        }
        
        .filters {
            width: 100%;
            padding: 0px;
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

                .mobile-filters label {
            display: none !important;
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

        .prdct-title {
            text-align: center;
        }

        .products-grid {
            grid-template-columns: repeat(3, 1fr);
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
