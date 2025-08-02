<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../user-includes/preview-mode.php";

// Direct database connection
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set page title and additional CSS
$page_title = "Search Results";
$additional_css = [
    "../search/search-results.css",
    "../pages/products/user-products.css",
    "../pages/home/product-dashboard.css"
];

require_once "../user-includes/navbar/customer-navigation.php";

// Get the search query
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Initialize results array
$products = [];
$user_blogs = [];
$admin_blogs = [];

// Only search if a query was provided
if (!empty($search_query)) {
    // Create search term with wildcards for partial word matching
    $search_param = "%" . $search_query . "%";
    
    // Search in products table - using the exact same query structure as your product pages
    try {
        $product_sql = "SELECT 
                            p.id, p.name, p.price, p.description, p.status_id, p.is_featured,
                            ps.name AS status_name, pi.image_url, p.quantity, p.show_when_unavailable 
                        FROM products p
                        LEFT JOIN product_statuses ps ON p.status_id = ps.id
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                        WHERE (p.name LIKE ? OR p.description LIKE ?)
                        AND p.deleted_at IS NULL 
                        AND (p.status_id != 3 
                            OR (p.status_id = 3 AND p.show_when_unavailable = 1))
                        ORDER BY p.is_featured DESC, p.status_id ASC
                        LIMIT 20";
        
        $stmt = $conn->prepare($product_sql);
        if ($stmt) {
            $stmt->bind_param("ss", $search_param, $search_param);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently handle errors
    }
    
    // Search in user blog posts
    try {
        $user_blog_sql = "SELECT p.*, u.firstname, u.lastname 
                        FROM user_blog_post p 
                        JOIN users u ON p.user_id = u.id 
                        WHERE (p.title LIKE ? OR p.content LIKE ?)
                        AND p.status = 'published' 
                        ORDER BY p.created_at DESC 
                        LIMIT 10";
        
        $stmt = $conn->prepare($user_blog_sql);
        if ($stmt) {
            $stmt->bind_param("ss", $search_param, $search_param);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $user_blogs[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently handle errors
    }
    
    // Search in admin blog posts
    try {
        $admin_blog_sql = "SELECT * FROM blog_posts 
                          WHERE (title LIKE ? OR description LIKE ?) 
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
}

// Include the header/navigation
require_once "../user-includes/navbar/customer-navigation.php";
?>
<link rel="stylesheet" href="../search/search-results.css">
<link rel="stylesheet" href="../pages/products/user-products.css">
<link rel="stylesheet" href="../pages/home/product-dashboard.css">


<div class="wrapper">
    <div class="search-results-container fade-in">
        <h1 class="search-title">Search Results for: "<?php echo htmlspecialchars($search_query); ?>"</h1>
        
        <?php if (empty($search_query)): ?>
            <div class="no-results">
                <p>Please enter a search term to find products and blog posts.</p>
            </div>
        <?php elseif (empty($products) && empty($user_blogs) && empty($admin_blogs)): ?>
            <div class="no-results">
                <p>No results found for "<?php echo htmlspecialchars($search_query); ?>".</p>
                <p>Try different keywords or check your spelling.</p>
            </div>
        <?php else: ?>
            <!-- Product Results -->
            <?php if (!empty($products)): ?>
                <div class="result-section">
                    <h2>Products (<?php echo count($products); ?>)</h2>
                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($products as $row): 
                            // Get all images for this product - exactly as in your product pages
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
                                'show_when_unavailable' => (bool)$row['show_when_unavailable']
                            ];
                            
                            $featuredClass = $row['is_featured'] ? 'featured-product' : '';
                            $isUnavailable = $row['status_id'] == 3 || $row['quantity'] <= 0;
                            $statusClass = strtolower(str_replace(' ', '-', $row['status_name']));
                        ?>
                            <div class="product-card <?php echo $featuredClass; ?>" data-status="<?php echo htmlspecialchars($row['status_name']); ?>" 
                                 onclick="openProductModal(<?php echo htmlspecialchars(json_encode($productData), ENT_QUOTES, 'UTF-8'); ?>)">
                                <div class="product-image">
                                    <img src="../../<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                    <?php if ($row['is_featured']): ?>
                                        <span class="featured-badge">Featured</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <p class="price">₱<?php echo number_format($row['price'], 2); ?></p>
                                    <div class="product-availability">
                                        <span class="status-badge status-<?php echo $statusClass; ?>"><?php echo ($isUnavailable ? "Not Available" : htmlspecialchars($row['status_name'])); ?></span>
                                        <p class="stock">Stock: <?php echo $row['quantity']; ?></p>
                                    </div>
                                    
                                    <?php if (!$isUnavailable): ?>
                                        <div class="quantity-controls">
                                            <button type="button" onclick="event.stopPropagation(); updateQuantity(this, -1)">-</button>
                                            <input type="number" value="1" min="1" max="<?php echo $row['quantity']; ?>" onclick="event.stopPropagation()" onchange="validateQuantity(this)">
                                            <button type="button" onclick="event.stopPropagation(); updateQuantity(this, 1)">+</button>
                                        </div>
                                        <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?php echo $row['id']; ?>, this)">Add to Cart</button>
                                    <?php else: ?>
                                        <button class="add-to-cart unavailable" disabled>Currently Unavailable</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- User Blog Results -->
            <?php if (!empty($user_blogs)): ?>
                <div class="result-section">
                    <h2>Customer Blog Posts (<?php echo count($user_blogs); ?>)</h2>
                    <div class="blog-grid">
                        <?php foreach ($user_blogs as $post): ?>
                            <div class="blog-post">
                                <div class="post-header">
                                    <div class="post-user">
                                        <div class="user-info">
                                            <span class="username"><?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?></span>
                                            <div class="actions">
                                                <span class="post-date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                                                <button class="action-btn" onclick="toggleActionBox(this)">⋯</button>
                                                <div class="action-box">
                                                    <a href="edit-blog.php?id=<?php echo $post['id']; ?>" class="edit-btn">
                                                        <span style="vertical-align: middle;">Edit</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#676767" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 5px;">
                                                            <path d="M4 20.0001H20M4 20.0001V16.0001L12 8.00012M4 20.0001L8 20.0001L16 12.0001M12 8.00012L14.8686 5.13146L14.8704 5.12976C15.2652 4.73488 15.463 4.53709 15.691 4.46301C15.8919 4.39775 16.1082 4.39775 16.3091 4.46301C16.5369 4.53704 16.7345 4.7346 17.1288 5.12892L18.8686 6.86872C19.2646 7.26474 19.4627 7.46284 19.5369 7.69117C19.6022 7.89201 19.6021 8.10835 19.5369 8.3092C19.4628 8.53736 19.265 8.73516 18.8695 9.13061L18.8686 9.13146L16 12.0001M12 8.00012L16 12.0001"></path>
                                                        </svg>
                                                    </a><br>
                                                    <button onclick="deletePost(<?php echo $post['id']; ?>)" class="delete-btn">
                                                        <span style="vertical-align: middle;">Delete</span>
                                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 5px;">
                                                                <path d="M8 1.5V2.5H3C2.44772 2.5 2 2.94772 2 3.5V4.5C2 5.05228 2.44772 5.5 3 5.5H21C21.5523 5.5 22 5.05228 22 4.5V3.5C22 2.94772 21.5523 2.5 21 2.5H16V1.5C16 0.947715 15.5523 0.5 15 0.5H9C8.44772 0.5 8 0.947715 8 1.5Z" fill="#9a3131"></path>
                                                                <path d="M3.9231 7.5H20.0767L19.1344 20.2216C19.0183 21.7882 17.7135 23 16.1426 23H7.85724C6.28636 23 4.98148 21.7882 4.86544 20.2216L3.9231 7.5Z" fill="#9a3131"></path>
                                                            </svg>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($post['image_path'])): ?>
                                <div class="post-image">
                                    <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>">
                                </div>
                                <?php endif; ?>
                                
                                <div class="post-content">
                                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <p class="post-excerpt"><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 170) . (strlen($post['content']) > 170 ? '...' : ''))); ?></p>
                                    <?php if (strlen($post['content']) > 170): ?>
                                        <a href="view-blog.php?id=<?php echo $post['id']; ?>" class="read-more">Read more...</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                                <a href="view-blog-admin.php?id=<?php echo $post['id']; ?>" class="post-link">
                                <div class="post-header">
                                    <div class="user-info">
                                        <span class="username"><?php echo htmlspecialchars($post['author']); ?></span>
                                        <div class="post-date">
                                            <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($post['image_path'])): ?>
                                    <div class="post-image">
                                        <img src="../../assets/uploaded-images-admin/<?php echo htmlspecialchars($post['image_path']); ?>" 
                                            alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-content">
                                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <p class="caption-text"><?php echo nl2br(htmlspecialchars(substr($post['description'], 0, 170) . (strlen($post['description']) > 170 ? '...' : ''))); ?></p>
                                    <?php if (strlen($post['description']) > 170): ?>
                                        <span class="read-more">Read more...</span>
                                    <?php endif; ?>
                                </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Product Modal -->
<div id="productModal" class="modal" style="display: none;">
    <div class="modal-content fade-in-pop">
        <span class="close" onclick="closeProductModal()">&times;</span>
        <div class="modal-body">
            <div class="product-images">
                <div class="main-image">
                    <img id="modalMainImage" src="/placeholder.svg" alt="Product Image">
                </div>
                <div class="thumbnail-container" id="thumbnailContainer">
                    <!-- Thumbnails will be added here dynamically -->
                </div>
            </div>
            <div class="product-details">
                <h2 class="m-title" id="modalProductName"></h2>
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
    function toggleActionBox(button) {
        // Get the action box element
        const actionBox = button.nextElementSibling;

        // Toggle the action box
        const allActionBoxes = document.querySelectorAll('.action-box');
        allActionBoxes.forEach(box => {
            if (box !== actionBox) {
                box.style.display = 'none';
            }
        });
        actionBox.style.display = actionBox.style.display === 'none' || actionBox.style.display === '' ? 'block' : 'none';

        // Add click event listener to document
        const closeActionBox = (event) => {
            if (!actionBox.contains(event.target) && !button.contains(event.target)) {
                actionBox.style.display = 'none';
                document.removeEventListener('click', closeActionBox);
            }
        };

        if (actionBox.style.display === 'block') {
            // Add small delay to prevent immediate closure
            setTimeout(() => {
                document.addEventListener('click', closeActionBox);
            }, 0);
        }
    }

    function deletePost(postId) {
        if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
            fetch('delete-blog.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting post: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting post. Please try again.');
            });
        }
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
        
        if (value > max) input.value = max;
        if (value < min) input.value = min;
        if (isNaN(value)) input.value = min;
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
        
        if (value > max) input.value = max;
        if (value < 1) input.value = 1;
        if (isNaN(value)) input.value = 1;
    }

    function addToCart(productId, button) {
        const quantityInput = button.parentElement.querySelector('input');
        const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

        fetch("../../php/users/add-to-cart.php", {
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

    function openProductModal(product) {
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
            mainImage.src = '../../' + product.images[0];
            
            // Clear existing thumbnails
            thumbnails.innerHTML = '';
            
            // Add all images as thumbnails
            product.images.forEach((image, index) => {
                const thumb = document.createElement('img');
                thumb.src = '../../' + image;
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
                addToCart(product.id);
                closeProductModal();
            };
        }

        modal.style.display = 'block';
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
</script>

<?php
// Include the footer
require_once "../user-includes/footer.php";
?>