<?php
// Include database first - it handles session configuration
require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../../includes/session-manager.php";

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header("Location: product-dashboard.php");
    exit;
}

// Fetch product details
$sql = "SELECT 
            p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id,
            ps.name AS status_name, 
            p.quantity, p.show_when_unavailable, p.hide_when_unavailable,
            p.availtoday_status_id, ats.name AS availtoday_status_name,
            c.name AS category_name,
            GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ', ') as todays_product_dates,
            GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ', ') as regular_today_dates,
            qpd.quantity as sameday_stock_today
        FROM products p
        LEFT JOIN product_statuses ps ON p.status_id = ps.id
        LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
        LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
        LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
        WHERE p.deleted_at IS NULL 
        AND p.id = ?
        GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id, ps.name, p.quantity, p.show_when_unavailable, p.hide_when_unavailable, p.availtoday_status_id, ats.name, c.name, qpd.quantity";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: product-dashboard.php");
    exit;
}

$product = $result->fetch_assoc();

// Get all images for this product
$images_sql = "SELECT COALESCE(cloud_url, image_url) as image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC";
$images_stmt = $conn->prepare($images_sql);
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$images = [];
while ($image = $images_result->fetch_assoc()) {
    $images[] = $image['image_url'];
}

$page_title = $product['name'];
$additional_css = [
    "/frontend/pages/products/product-details.css"
];

require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once __DIR__ . "/../../user-includes/user-header.php";
require_once __DIR__ . "/../../user-includes/preview-mode.php";

$today_date = date('Y-m-d');
?>

<?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>
<div id="confirmationPopup" class="confirmation-popup"></div>

<div class="wrapper">
    <div class="product-details-container fade-in">
        <div class="product-details-content">
            <div class="product-images-section">
                <div class="main-image">
                    <img id="mainImage" src="<?php echo !empty($images) ? htmlspecialchars($images[0]) : '../../../assets/images/no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="thumbnail-container">
                    <?php foreach ($images as $index => $image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?> view <?php echo $index + 1; ?>" 
                             onclick="changeMainImage(this.src)">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="product-info-section">
                <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>

                <div class="product-description">
                    <h3>Description:</h3>
                    <p><?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : 'No description available.'; ?></p>
                </div>

                <?php
                // Check if product is unavailable
                $preorderStock = $product['quantity'];
                $samedayStock = $product['sameday_stock_today'] ?? 0;
                $hasAvailtoday = !empty($product['availtoday_status_id']);
                
                $isUnavailable = false;
                $unavailableReason = '';
                
                // Check stock based on product type
                if ($product['status_id'] == 4) {
                    // Same Day ONLY product
                    $stockUnavailable = ($samedayStock == 0);
                    $todaysDates = !empty($product['todays_product_dates']) ? explode(', ', $product['todays_product_dates']) : [];
                    $dateUnavailable = !in_array($today_date, $todaysDates);
                    
                    $isUnavailable = $stockUnavailable || $dateUnavailable;
                    if ($stockUnavailable) {
                        $unavailableReason = 'Out of Stock';
                    } elseif ($dateUnavailable) {
                        $unavailableReason = 'Not Available Today';
                    }
                } elseif (in_array($product['status_id'], [1, 2, 3])) {
                    if ($hasAvailtoday) {
                        // DUAL capability
                        $isUnavailable = ($preorderStock == 0 && $samedayStock == 0);
                        if ($isUnavailable) {
                            $unavailableReason = 'Out of Stock';
                        }
                    } else {
                        // Pre-order ONLY
                        $isUnavailable = ($preorderStock == 0);
                        if ($isUnavailable) {
                            $unavailableReason = 'Out of Stock';
                        }
                    }
                }
                ?>

                <?php if ($isUnavailable): ?>
                    <button class="add-to-cart unavailable-btn" disabled>Unavailable - <?php echo htmlspecialchars($unavailableReason); ?></button>
                <?php else: ?>
                    <button class="add-to-cart" onclick="addToCartFromDetails()">Add to Cart</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2>Product Reviews</h2>
            <div class="reviews-summary" id="reviewsSummary">
                <div class="rating-overview">
                    <div class="average-rating" id="averageRating">
                        <span class="rating-number">0</span>
                        <div class="stars" id="averageStars"></div>
                        <span class="review-count" id="reviewCount">(0 reviews)</span>
                    </div>
                </div>
            </div>
            <div class="reviews-list" id="reviewsList">
                <p class="loading-reviews">Loading reviews...</p>
            </div>
            <button id="viewMoreReviewsBtn" class="view-more-reviews-btn" style="display: none;" onclick="toggleReviews()">
                View More Reviews
            </button>
        </div>

        <!-- Related Products Section -->
        <?php if ($related_result->num_rows > 0): ?>
        <div class="related-products-section">
            <h2>You May Also Like</h2>
            <div class="related-products-grid" id="relatedProducts">
                <?php
                // Fetch related products from the same category
                $related_sql = "SELECT 
                                    p.id, p.name, p.price, p.status_id, p.is_featured,
                                    ps.name AS status_name,
                                    COALESCE(pi.cloud_url, pi.image_url) as image_url,
                                    p.quantity, p.availtoday_status_id,
                                    GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ', ') as todays_product_dates,
                                    GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ', ') as regular_today_dates,
                                    qpd.quantity as sameday_stock_today
                                FROM products p
                                LEFT JOIN product_statuses ps ON p.status_id = ps.id
                                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
                                LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
                                LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
                                WHERE p.deleted_at IS NULL 
                                AND p.category_id = ? 
                                AND p.id != ?
                                AND p.status_id IN (1, 2, 3, 4)
                                GROUP BY p.id, p.name, p.price, p.status_id, p.is_featured, ps.name, pi.cloud_url, pi.image_url, p.quantity, p.availtoday_status_id, qpd.quantity
                                ORDER BY p.is_featured DESC, RAND()
                                LIMIT 8";
                
                $related_stmt = $conn->prepare($related_sql);
                $related_stmt->bind_param("ii", $product['category_id'], $product_id);
                $related_stmt->execute();
                $related_result = $related_stmt->get_result();

                if ($related_result->num_rows > 0):
                    while ($related = $related_result->fetch_assoc()):
                        $image_path = !empty($related['image_url']) ? htmlspecialchars($related['image_url']) : '../../../assets/images/no-image.jpg';
                        
                        // Check if related product is unavailable
                        $related_preorder_stock = $related['quantity'];
                        $related_sameday_stock = $related['sameday_stock_today'] ?? 0;
                        $related_has_availtoday = !empty($related['availtoday_status_id']);
                        
                        $related_is_unavailable = false;
                        $related_unavailable_reason = '';
                        
                        if ($related['status_id'] == 4) {
                            // Same Day ONLY product
                            $stock_unavailable = ($related_sameday_stock == 0);
                            $todays_dates = !empty($related['todays_product_dates']) ? explode(', ', $related['todays_product_dates']) : [];
                            $date_unavailable = !in_array($today_date, $todays_dates);
                            
                            $related_is_unavailable = $stock_unavailable || $date_unavailable;
                            if ($stock_unavailable) {
                                $related_unavailable_reason = 'Out of Stock';
                            } elseif ($date_unavailable) {
                                $related_unavailable_reason = 'Not Available Today';
                            }
                        } elseif (in_array($related['status_id'], [1, 2, 3])) {
                            if ($related_has_availtoday) {
                                // DUAL capability
                                $related_is_unavailable = ($related_preorder_stock == 0 && $related_sameday_stock == 0);
                                if ($related_is_unavailable) {
                                    $related_unavailable_reason = 'Out of Stock';
                                }
                            } else {
                                // Pre-order ONLY
                                $related_is_unavailable = ($related_preorder_stock == 0);
                                if ($related_is_unavailable) {
                                    $related_unavailable_reason = 'Out of Stock';
                                }
                            }
                        }
                        
                        $unavailable_class = $related_is_unavailable ? 'unavailable' : '';
                ?>
                        <div class="related-product-card <?php echo $unavailable_class; ?>" <?php echo !$related_is_unavailable ? "onclick=\"window.location.href='product-details.php?id=" . $related['id'] . "'\"" : ""; ?>>
                            <div class="related-product-image">
                                <?php if ($related_is_unavailable): ?>
                                    <div class="related-unavailable-overlay">
                                        <span><?php echo htmlspecialchars($related_unavailable_reason); ?></span>
                                    </div>
                                <?php endif; ?>
                                <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" loading="lazy">
                            </div>
                            <div class="related-product-info">
                                <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                                <p class="related-product-price">₱<?php echo number_format($related['price'], 2); ?></p>
                            </div>
                        </div>
                <?php
                    endwhile;
                endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quantity Modal -->
<div id="quantityModal" class="modal" style="display: none;">
    <div class="modal-content quantity-modal-content fade-in-pop">
        <span class="close" onclick="closeQuantityModal()">&times;</span>
        <div class="quantity-modal-body">
            <h2 id="quantityModalProductName">Product Name</h2>
            <p class="quantity-modal-price" id="quantityModalPrice"></p>
            
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
            <p class="quantity-modal-date" id="quantityModalDate" style="display: none;">For: Today</p>

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
const isLoggedIn = <?= SessionManager::isUserLoggedIn() ? 'true' : 'false' ?>;
const loginUrl = window.location.origin + '/frontend/login/user/login-signup.php';

const productData = {
    id: <?php echo $product['id']; ?>,
    name: <?php echo json_encode($product['name']); ?>,
    price: <?php echo $product['price']; ?>,
    quantity: <?php echo $product['quantity']; ?>,
    status_id: <?php echo $product['status_id']; ?>,
    availtoday_status_id: <?php echo $product['availtoday_status_id'] ?? 'null'; ?>,
    sameday_stock_today: <?php echo $product['sameday_stock_today'] ?? 0; ?>
};

let pendingCartProduct = null;

function checkLoginAndRedirect() {
    if (!isLoggedIn) {
        alert('Please login to add items to cart');
        window.location.href = loginUrl;
        return false;
    }
    return true;
}

function changeMainImage(src) {
    document.getElementById('mainImage').src = src;
}

function addToCartFromDetails() {
    if (!checkLoginAndRedirect()) {
        return;
    }
    
    pendingCartProduct = {
        productId: productData.id,
        productName: productData.name,
        productPrice: '₱' + parseFloat(productData.price).toFixed(2),
        productStock: productData.quantity,
        statusId: productData.status_id,
        availtodayStatusId: productData.availtoday_status_id,
        samedayStock: productData.sameday_stock_today
    };
    
    openQuantityModalWithOrderType(
        productData.name,
        '₱' + parseFloat(productData.price).toFixed(2),
        productData.quantity,
        productData.status_id,
        productData.availtoday_status_id
    );
}

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

// Global variable to store all reviews
let allReviews = [];
let showingAllReviews = false;
const INITIAL_REVIEWS_COUNT = 2;

// Load reviews on page load
document.addEventListener('DOMContentLoaded', function() {
    loadProductReviews(<?php echo $product_id; ?>);
});

function loadProductReviews(productId) {
    const reviewsList = document.getElementById('reviewsList');
    
    reviewsList.innerHTML = '<p class="loading-reviews">Loading reviews...</p>';
    
    fetch(`../../api/get-reviews.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allReviews = data.reviews;
                displayReviews(data.reviews, data.statistics);
            } else {
                reviewsList.innerHTML = '<p class="no-reviews">No reviews yet.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading reviews:', error);
            reviewsList.innerHTML = '<p class="no-reviews">Error loading reviews.</p>';
        });
}

function displayReviews(reviews, stats) {
    const reviewsList = document.getElementById('reviewsList');
    const averageRating = document.getElementById('averageRating');
    const averageStars = document.getElementById('averageStars');
    const reviewCount = document.getElementById('reviewCount');
    const viewMoreBtn = document.getElementById('viewMoreReviewsBtn');
    
    if (stats.total_reviews > 0) {
        averageRating.querySelector('.rating-number').textContent = stats.average_rating.toFixed(1);
        averageStars.innerHTML = renderStars(stats.average_rating);
        reviewCount.textContent = `(${stats.total_reviews} ${stats.total_reviews === 1 ? 'review' : 'reviews'})`;
    } else {
        averageRating.querySelector('.rating-number').textContent = '0';
        averageStars.innerHTML = renderStars(0);
        reviewCount.textContent = '(0 reviews)';
    }
    
    if (reviews.length === 0) {
        reviewsList.innerHTML = '<p class="no-reviews">No reviews yet. Be the first to review this product!</p>';
        viewMoreBtn.style.display = 'none';
        return;
    }
    
    // Determine which reviews to show
    const reviewsToShow = showingAllReviews ? reviews : reviews.slice(0, INITIAL_REVIEWS_COUNT);
    
    reviewsList.innerHTML = reviewsToShow.map(review => `
        <div class="review-item ${review.is_featured ? 'featured' : ''}">
            <div class="review-header">
                <span class="review-user">${review.user_name}</span>
                <span class="review-date">${formatDate(review.created_at)}</span>
            </div>
            <div class="review-rating">${renderStars(review.rating)}</div>
            ${review.review_text ? `<div class="review-text">${escapeHtml(review.review_text)}</div>` : ''}
            ${review.media && review.media.length > 0 ? `
                <div class="review-media" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; margin-top: 12px;">
                    ${review.media.map(media => {
                        if (media.type === 'image') {
                            return `<img src="${media.url}" alt="Review image" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; cursor: pointer;" onclick="openMediaModal('${media.url}', 'image')">`;
                        } else {
                            return `<video src="${media.url}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; cursor: pointer;" onclick="openMediaModal('${media.url}', 'video')"></video>`;
                        }
                    }).join('')}
                </div>
            ` : ''}
        </div>
    `).join('');
    
    // Show/hide the "View More" button
    if (reviews.length > INITIAL_REVIEWS_COUNT) {
        viewMoreBtn.style.display = 'block';
        viewMoreBtn.textContent = showingAllReviews ? 'Show Less' : `View More Reviews (${reviews.length - INITIAL_REVIEWS_COUNT} more)`;
    } else {
        viewMoreBtn.style.display = 'none';
    }
}

function toggleReviews() {
    showingAllReviews = !showingAllReviews;
    const stats = {
        total_reviews: allReviews.length,
        average_rating: allReviews.reduce((sum, r) => sum + parseFloat(r.rating), 0) / allReviews.length
    };
    displayReviews(allReviews, stats);
    
    // Scroll to reviews section if showing less
    if (!showingAllReviews) {
        document.querySelector('.reviews-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function renderStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    let stars = '';
    
    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
            stars += '<span class="star">★</span>';
        } else if (i === fullStars + 1 && hasHalfStar) {
            stars += '<span class="star">☆</span>';
        } else {
            stars += '<span class="star empty">☆</span>';
        }
    }
    
    return stars;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function openMediaModal(url, type) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
    
    // Create close button
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = 'position: absolute; top: 20px; right: 20px; background: white; border: none; font-size: 32px; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; color: #333;';
    closeBtn.onclick = () => document.body.removeChild(modal);
    
    // Create media element
    let mediaElement;
    if (type === 'image') {
        mediaElement = document.createElement('img');
        mediaElement.src = url;
        mediaElement.style.cssText = 'max-width: 90%; max-height: 90%; border-radius: 8px;';
    } else {
        mediaElement = document.createElement('video');
        mediaElement.src = url;
        mediaElement.controls = true;
        mediaElement.autoplay = true;
        mediaElement.style.cssText = 'max-width: 90%; max-height: 90%; border-radius: 8px;';
    }
    
    modal.appendChild(closeBtn);
    modal.appendChild(mediaElement);
    modal.onclick = (e) => {
        if (e.target === modal) document.body.removeChild(modal);
    };
    
    document.body.appendChild(modal);
}

// Quantity modal functions (copied from product-dashboard.php)
async function openQuantityModalWithOrderType(productName, productPrice, productStock, statusId, availtodayStatusId) {
    const modal = document.getElementById('quantityModal');
    const quantityInput = document.getElementById('quantityModalInput');
    const orderTypeSelector = document.getElementById('orderTypeSelector');
    const stockDisplay = document.getElementById('quantityModalStock');
    const dateDisplay = document.getElementById('quantityModalDate');
    
    document.getElementById('quantityModalProductName').textContent = productName;
    document.getElementById('quantityModalPrice').textContent = productPrice;
    
    quantityInput.value = 1;
    quantityInput.min = 1;
    
    const hasPreorder = [1, 2, 3].includes(statusId) && productStock > 0;
    const hasSameday = statusId == 4 || (availtodayStatusId != null && availtodayStatusId != '');
    
    if (hasPreorder && hasSameday) {
        orderTypeSelector.style.display = 'block';
        document.querySelector('input[name="orderType"][value="preorder"]').checked = true;
        quantityInput.max = productStock;
        stockDisplay.textContent = `Stock: ${productStock}`;
        dateDisplay.style.display = 'none';
    } else if (hasSameday) {
        orderTypeSelector.style.display = 'none';
        const samedayStock = pendingCartProduct.samedayStock || 0;
        quantityInput.max = samedayStock;
        stockDisplay.textContent = `Stock: ${samedayStock}`;
        dateDisplay.style.display = 'block';
        dateDisplay.textContent = 'For: Today';
    } else {
        orderTypeSelector.style.display = 'none';
        quantityInput.max = productStock;
        stockDisplay.textContent = `Stock: ${productStock}`;
        dateDisplay.style.display = 'none';
    }
    
    modal.style.display = 'flex';
}

function closeQuantityModal() {
    document.getElementById('quantityModal').style.display = 'none';
    pendingCartProduct = null;
}

function selectOrderType(type) {
    if (!pendingCartProduct) return;
    
    const quantityInput = document.getElementById('quantityModalInput');
    const stockDisplay = document.getElementById('quantityModalStock');
    const dateDisplay = document.getElementById('quantityModalDate');
    
    if (type === 'preorder') {
        quantityInput.max = pendingCartProduct.productStock;
        quantityInput.value = Math.min(parseInt(quantityInput.value), pendingCartProduct.productStock);
        stockDisplay.textContent = `Stock: ${pendingCartProduct.productStock}`;
        dateDisplay.style.display = 'none';
    } else {
        const samedayStock = pendingCartProduct.samedayStock || 0;
        quantityInput.max = samedayStock;
        quantityInput.value = Math.min(parseInt(quantityInput.value), samedayStock);
        stockDisplay.textContent = `Stock: ${samedayStock}`;
        dateDisplay.style.display = 'block';
        dateDisplay.textContent = 'For: Today';
    }
}

function updateQuantityModalValue(change) {
    const input = document.getElementById('quantityModalInput');
    const newValue = parseInt(input.value) + change;
    if (newValue >= parseInt(input.min) && newValue <= parseInt(input.max)) {
        input.value = newValue;
    }
}

function validateQuantityModalInput() {
    const input = document.getElementById('quantityModalInput');
    const value = parseInt(input.value);
    const max = parseInt(input.max);
    const min = parseInt(input.min);
    
    if (isNaN(value) || value < min) {
        input.value = min;
    } else if (value > max) {
        input.value = max;
    }
}

function confirmAddToCart() {
    if (!pendingCartProduct) return;
    
    const quantity = parseInt(document.getElementById('quantityModalInput').value);
    const orderTypeSelector = document.getElementById('orderTypeSelector');
    let orderType = 'preorder';
    
    if (orderTypeSelector.style.display !== 'none') {
        const selectedRadio = document.querySelector('input[name="orderType"]:checked');
        orderType = selectedRadio ? selectedRadio.value : 'preorder';
    } else if (pendingCartProduct.statusId == 4 || pendingCartProduct.availtodayStatusId) {
        orderType = 'sameday';
    }
    
    if (orderType === 'sameday') {
        addToAvailTodayCart(pendingCartProduct.productId, quantity);
    } else {
        addToPreorderCart(pendingCartProduct.productId, quantity);
    }
    
    closeQuantityModal();
}

function addToAvailTodayCart(productId, quantity) {
    fetch('../../api/add-to-availtoday-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showConfirmation('Added to Same Day Order cart!');
            if (typeof updateAvailTodayCartCount === 'function') {
                updateAvailTodayCartCount();
            }
        } else {
            showConfirmation(data.message || 'Error adding to cart', true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showConfirmation('Error adding to cart', true);
    });
}

function addToPreorderCart(productId, quantity) {
    fetch('../../api/add-to-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showConfirmation('Added to Pre-Order cart!');
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
        } else {
            showConfirmation(data.message || 'Error adding to cart', true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showConfirmation('Error adding to cart', true);
    });
}
</script>

<script src="availtoday-cart.js"></script>

<?php require_once __DIR__ . "/../../user-includes/user-footer.php"; ?>
