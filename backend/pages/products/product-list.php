<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: ../auth/login-signup.php");
        exit();
    }

    // Include config file for base URL
    require_once __DIR__ . "/../admin-includes/config.php";

    // Pagination settings
    $items_per_page = 12;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $items_per_page;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/backend/pages/products/product-list.css">
    <script src="/backend/pages/products/product-list.js" defer></script>
    <title>Product Management</title>
</head>
<body>
<?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>

<!-- FIXED: Wrap content in container with proper class -->
<div class="product-list-container">
    <div class="main-container">
        <!-- Header Section - FIXED: Removed conflicting header content -->
        <div class="page-header">
            <div class="header-content">
                <p class="page-subtitle">Manage your products here</p>
            </div>
                    
            <div class="search-group">
                <div class="search-container">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" class="search-input" placeholder="Search products..." id="searchInput" oninput="searchProducts()">
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="controls-section">
            <div class="filter-group">
                <label class="filter-label">Filter by Status:</label>
                <div class="filter-buttons">
                    <button class="filter-btn active" onclick="filterProducts('all', this)" data-filter="all">
                        <span class="filter-count" id="count-all">0</span>
                        All Products
                    </button>
                    <button class="filter-btn" onclick="filterProducts('Bread of the Week', this)" data-filter="featured">
                        <span class="filter-count" id="count-featured">0</span>
                        Featured
                    </button>
                    <button class="filter-btn" onclick="filterProducts('Available', this)" data-filter="available">
                        <span class="filter-count" id="count-available">0</span>
                        Available
                    </button>
                    <button class="filter-btn" onclick="filterProducts('Unavailable', this)" data-filter="unavailable">
                        <span class="filter-count" id="count-unavailable">0</span>
                        Unavailable
                    </button>
                </div>
            </div>
        </div>

        <!-- Sort Controls -->
        <div class="sort-controls">
            <div class="sort-group">
                <label class="sort-label">Sort by:</label>
                <div class="sort-buttons">
                    <button class="sort-btn" id="sort-sku" onclick="toggleSort(1)">
                        SKU
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    
                    <button class="sort-btn" id="sort-name" onclick="toggleSort(2)">
                        Name
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    
                    <button class="sort-btn" id="sort-price" onclick="toggleSort(3)">
                        Price
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid/Table -->
        <div class="products-container">
            <div class="table-wrapper">
                <table class="products-table">
                    <tbody id="productTableBody">
                        <?php
                            $conn = new mysqli("localhost", "root", "", "crud");
                            if ($conn->connect_error) {
                                die("Connection failed: " . $conn->connect_error);
                            }

                            // Count total products for pagination
                            $count_sql = "SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL";
                            $count_result = $conn->query($count_sql);
                            $total_products = $count_result->fetch_assoc()['total'];
                            $total_pages = ceil($total_products / $items_per_page);

                            // Query with LIMIT and OFFSET for pagination
                            $sql = "SELECT 
                                        p.id, p.sku, p.name, p.price, p.status_id, ps.name AS status_name, 
                                        pi.image_url, p.is_featured, p.show_when_unavailable, p.hide_when_unavailable,
                                        p.quantity 
                                    FROM products p
                                    LEFT JOIN product_statuses ps ON p.status_id = ps.id
                                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                    WHERE p.deleted_at IS NULL
                                    ORDER BY p.created_at DESC
                                    LIMIT $items_per_page OFFSET $offset";
                                    
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $status_id = isset($row["status_id"]) ? $row["status_id"] : 1;
                                    $quantity = isset($row["quantity"]) ? $row["quantity"] : 0;
                                    $quantityClass = $quantity <= 5 ? 'low-stock' : ($quantity <= 10 ? 'medium-stock' : 'good-stock');
                                    $statusClass = strtolower(str_replace(' ', '-', $row['status_name']));

                                    // Construct image path
                                    $imagePath = '/assets/images/no-image.jpg';
                                    if (!empty($row['image_url'])) {
                                        // Split path into directory and filename
                                        $pathParts = pathinfo($row['image_url']);
                                        $dirPath = $pathParts['dirname'];
                                        $fileName = $pathParts['basename'];
                                        
                                        // URL encode each path segment separately
                                        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $dirPath)));
                                        $encodedFileName = rawurlencode($fileName);
                                        
                                        // Combine encoded parts - remove extra /assets/ prefix since it's already in the DB path
                                        $imagePath = '/' . $encodedPath . '/' . $encodedFileName;
                                        
                                        // Verify file exists using relative path from project root
                                        $fullPath = __DIR__ . '/../../..' . $imagePath;
                                        if (!file_exists($fullPath)) {
                                            $imagePath = '/assets/images/no-image.jpg';
                                        }
                                    }

                                    echo "<tr data-status='" . $row['status_name'] . "' data-name='" . strtolower($row['name']) . "' data-sku='" . strtolower($row['sku']) . "'>
                                            <td>
                                                <div class='product-image-container'>
                                                    <img class='product-image' src='" . htmlspecialchars($imagePath) . "' alt='" . htmlspecialchars($row['name']) . "' loading='lazy' onerror=\"this.src='/assets/images/no-image.jpg'\">
                                                    " . ($row['is_featured'] ? "<span class='featured-badge'>★</span>" : "") . "
                                                </div>
                                            </td>
                                            <td>
                                                <span class='sku-text'>" . $row['sku'] . "</span>
                                            </td>
                                            <td>
                                                <div class='product-info'>
                                                    <span class='product-name'>" . htmlspecialchars($row['name']) . "</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class='price-text'>₱" . number_format($row['price'], 2) . "</span>
                                            </td>
                                            <td>
                                                <div class='status-container'>
                                                    <span class='status-badge status-" . $statusClass . "'>" . $row['status_name'] . "</span>
                                                    <span class='stock-badge " . $quantityClass . "'>
                                                        <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                            <path d='M20 7h-9'></path>
                                                            <path d='M14 17H5'></path>
                                                            <circle cx='17' cy='17' r='3'></circle>
                                                            <circle cx='7' cy='7' r='3'></circle>
                                                        </svg>
                                                        " . $quantity . " in stock
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class='action-buttons'>
                                                    <button class='btn-action btn-edit' onclick=\"openEditModal(
                                                        '" . $row["id"] . "',     
                                                        '" . addslashes($row["name"]) . "', 
                                                        '" . $row["price"] . "', 
                                                        '" . $status_id . "',
                                                        " . ($row["is_featured"] ? "true" : "false") . ",
                                                        " . ($row["show_when_unavailable"] ? "true" : "false") . ",
                                                        " . ($row["hide_when_unavailable"] ? "true" : "false") . ",
                                                        " . $quantity . "
                                                    )\" title='Edit Product'>
                                                        <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                            <path d='M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7'></path>
                                                            <path d='M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z'></path>
                                                        </svg>
                                                    </button>
                                                    <button class='btn-action btn-delete' onclick='softDeleteProduct(" . $row["id"] . ")' title='Delete Product'>
                                                        <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                            <polyline points='3,6 5,6 21,6'></polyline>
                                                            <path d='M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2'></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr class='no-results'><td colspan='6'>
                                        <div class='empty-state'>
                                            <svg width='48' height='48' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                <circle cx='11' cy='11' r='8'></circle>
                                                <path d='m21 21-4.35-4.35'></path>
                                            </svg>
                                            <h3>No products found</h3>
                                            <p>Start by adding your first product to the inventory.</p>
                                        </div>
                                      </td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?php echo min($total_products, $items_per_page * $current_page); ?> of <?php echo $total_products; ?> products
                </div>
                <nav class="pagination">
                    <?php
                    $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
                    echo '<a class="pagination-btn ' . $prev_disabled . '" ' . 
                         ($prev_disabled == '' ? 'href="?page=' . ($current_page - 1) . '"' : '') . '>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15,18 9,12 15,6"></polyline>
                            </svg>
                            Previous
                         </a>';
                    
                    $max_visible_pages = 5;
                    $start_page = max(1, min($current_page - floor($max_visible_pages / 2), $total_pages - $max_visible_pages + 1));
                    $end_page = min($start_page + $max_visible_pages - 1, $total_pages);
                    
                    if ($start_page > 1) {
                        echo '<a class="pagination-number" href="?page=1">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = ($i == $current_page) ? 'active' : '';
                        echo '<a class="pagination-number ' . $active . '" href="?page=' . $i . '">' . $i . '</a>';
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                        echo '<a class="pagination-number" href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                    }
                    
                    $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
                    echo '<a class="pagination-btn ' . $next_disabled . '" ' . 
                         ($next_disabled == '' ? 'href="?page=' . ($current_page + 1) . '"' : '') . '>
                            Next
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9,18 15,12 9,6"></polyline>
                            </svg>
                         </a>';
                    ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Product</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <form id="editProductForm" class="modal-form">
            <input type="hidden" id="editProductId">
            
            <div class="form-group">
                <label for="editProductName">Product Name</label>
                <input type="text" id="editProductName" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editProductPrice">Price (₱)</label>
                    <input type="number" id="editProductPrice" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="editProductQuantity">Stock Quantity</label>
                    <input type="number" id="editProductQuantity" min="0" step="1" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editProductStatus">Status</label>
                    <select id="editProductStatus">
                        <option value="1">Bread of the Week</option>
                        <option value="2">Available</option>
                        <option value="3">Unavailable</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editIsFeature">Featured Product</label>
                    <select id="editIsFeature">
                        <option value="0">Not Featured</option>
                        <option value="1">Featured</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="editVisibilityOption">Visibility When Unavailable</label>
                <select id="editVisibilityOption">
                    <option value="default">Default (Hidden)</option>
                    <option value="show">Show When Unavailable</option>
                    <option value="hide">Hide When Unavailable</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php $conn->close(); ?>
</body>
</html>
