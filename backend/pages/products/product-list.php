<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: ../auth/login-signup.php");
        exit();
    }

    // Include config file for base URL
    require_once __DIR__ . "/../admin-includes/config.php";
    include __DIR__ . "/../admin-includes/database.php";

    // Function to format available days in compact format
    function formatAvailableDays($availableDays) {
        if (empty($availableDays)) return "Not Applicable";
        
        $dayMap = [
            'Sunday' => 'Sun',
            'Monday' => 'Mon', 
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sa'
        ];
        
        $days = explode(', ', $availableDays);
        $formattedDays = [];
        
        foreach ($days as $day) {
            $formattedDays[] = isset($dayMap[$day]) ? $dayMap[$day] : $day;
        }
        
        return implode(', ', $formattedDays);
    }

    // Function to format selected dates for display
    function formatSelectedDates($datesString) {
        if (empty($datesString)) return "";
        
        $dates = explode(',', $datesString);
        $dates = array_filter($dates); // Remove empty values
        
        if (empty($dates)) return "";
        
        $formattedDates = [];
        foreach ($dates as $date) {
            $dateObj = DateTime::createFromFormat('Y-m-d', trim($date));
            if ($dateObj) {
                $formattedDates[] = $dateObj->format('n/j'); // Format as M-D (e.g., 2-23)
            }
        }
        
        if (empty($formattedDates)) return "";
        
        if (count($formattedDates) <= 3) {
            return implode(' · ', $formattedDates);
        } else {
            // Show first 3 dates with hover tooltip for all dates
            $visibleDates = array_slice($formattedDates, 0, 3);
            $allDates = implode(' · ', $formattedDates);
            return '<span class="dates-display" data-tooltip="' . htmlspecialchars($allDates) . '">' . 
                   implode(' · ', $visibleDates) . ' <span class="more-dates">+' . (count($formattedDates) - 3) . '</span></span>';
        }
    }

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
    <link rel="stylesheet" href="/backend/pages/products/edit-modal.css">
    <link rel="stylesheet" href="/backend/pages/products/dates-tooltip.css">
    <script src="/backend/pages/products/product-list.js" defer></script>
    <script src="components/date-calendar.js" defer></script>
    <script src="/backend/pages/products/modal-calendar-handler.js" defer></script>
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
                    <button class="filter-btn" onclick="filterProducts('Pick Up', this)" data-filter="pickup">
                        <span class="filter-count" id="count-pickup">0</span>
                        Pickup
                    </button>
                    <button class="filter-btn" onclick="filterProducts('Delivery', this)" data-filter="delivery">
                        <span class="filter-count" id="count-delivery">0</span>
                        Delivery
                    </button>
                    <button class="filter-btn" onclick="filterProducts('Same Day Order', this)" data-filter="available-today">
                        <span class="filter-count" id="count-available-today">0</span>
                        Same Day
                    </button>
                    <button class="filter-btn" onclick="filterProducts('featured', this)" data-filter="featured">
                        <span class="filter-count" id="count-featured">0</span>
                        Featured
                    </button>
                    <button class="filter-btn" onclick="filterProducts('Unavailable', this)" data-filter="unavailable">
                        <span class="filter-count" id="count-unavailable">0</span>
                        Unavailable
                    </button>


                    <select id="unavailableTypeDropdown" class="unavailable-type-dropdown" style="display: none;" onchange="filterUnavailableByType()">
                        <option value="all-unavailable">All Unavailable</option>
                        <option value="unavailable-delivery">Unavailable Delivery</option>
                        <option value="unavailable-pickup">Unavailable Pick Up</option>
                        <option value="unavailable-today">Unavailable for Same Day Order</option>
                    </select>
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
                            // Include database connection
                            require_once "../admin-includes/database.php";

                            // Count total products for pagination
                            $count_sql = "SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL";
                            $count_result = $conn->query($count_sql);
                            $total_products = $count_result->fetch_assoc()['total'];
                            $total_pages = ceil($total_products / $items_per_page);

                            // Query with LIMIT and OFFSET for pagination
                            $sql = "SELECT 
                                        p.id, p.sku, p.name, p.description, p.price, p.status_id, ps.name AS status_name, 
                                        p.unavailable_status_id, ups.name AS unavailable_status_name,
                                        pi.image_url, p.is_featured, p.show_when_unavailable, p.hide_when_unavailable,
                                        p.quantity, p.availtoday_status_id, ats.name AS availtoday_status_name,
                                        GROUP_CONCAT(DISTINCT pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days,
                                        GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ',') as todays_product_dates,
                                        GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ',') as regular_today_dates
                                    FROM products p
                                    LEFT JOIN product_statuses ps ON p.status_id = ps.id
                                    LEFT JOIN unavail_products_status ups ON p.unavailable_status_id = ups.id
                                    LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                    LEFT JOIN product_day pd ON p.id = pd.product_id
                                    LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
                                    LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
                                    WHERE p.deleted_at IS NULL AND p.id > 0
                                    GROUP BY p.id
                                    ORDER BY p.created_at DESC
                                    LIMIT $items_per_page OFFSET $offset";
                                    
                            // Also get all products for JavaScript filtering (without pagination)
                            $all_products_sql = "SELECT 
                                                    p.id, p.sku, p.name, p.description, p.price, p.status_id, ps.name AS status_name, 
                                                    p.unavailable_status_id, ups.name AS unavailable_status_name,
                                                    pi.image_url, p.is_featured, p.show_when_unavailable, p.hide_when_unavailable,
                                                    p.quantity, p.availtoday_status_id, ats.name AS availtoday_status_name,
                                                    GROUP_CONCAT(DISTINCT pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days,
                                                    GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ',') as todays_product_dates,
                                                    GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ',') as regular_today_dates
                                                FROM products p
                                                LEFT JOIN product_statuses ps ON p.status_id = ps.id
                                                LEFT JOIN unavail_products_status ups ON p.unavailable_status_id = ups.id
                                                LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                                                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                                LEFT JOIN product_day pd ON p.id = pd.product_id
                                                LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
                                                LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
                                                WHERE p.deleted_at IS NULL AND p.id > 0
                                                GROUP BY p.id
                                                ORDER BY p.created_at DESC";
                                    
                            $all_products_result = $conn->query($all_products_sql);
                            $all_products_data = [];
                            
                            if ($all_products_result->num_rows > 0) {
                                while ($row = $all_products_result->fetch_assoc()) {
                                    $all_products_data[] = $row;
                                }
                            }else{
                                echo "<tr><td colspan='100%'>No products found</td></tr>";
                            }
                                    
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $status_id = isset($row["status_id"]) ? $row["status_id"] : 1;
                                    $quantity = isset($row["quantity"]) ? $row["quantity"] : 0;
                                    

                                    $quantityClass = $quantity <= 5 ? 'low-stock' : ($quantity <= 10 ? 'medium-stock' : 'good-stock');
                                    $statusClass = strtolower(str_replace(' ', '-', $row['status_name'] ?? 'Unknown'));

                                    // Construct image path
                                    $imagePath = '';
                                    if (!empty($row['image_url'])) {
                                        // Use the same path structure as weekly-product.php
                                        $imagePath = '/assets/' . $row['image_url'];
                                    }

                                    $displayStatus = ($row['status_id'] == 3) ? 'Same Day Order' : ($row['status_name'] ?? 'Unknown');
                                    echo "<tr data-status='" . $displayStatus . "' data-name='" . strtolower($row['name']) . "' data-sku='" . strtolower($row['sku']) . "'>
                                            <td>
                                                <div class='product-image-container'>
                                                    <img class='product-image' src='" . htmlspecialchars($imagePath) . "' alt='" . htmlspecialchars($row['name']) . "' loading='lazy'>
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
                                                    <span class='status-badge status-" . $statusClass . "'>" . $displayStatus . "</span>";
                                                    
                                                    // Show badge for Same Day Order (status_id = 3)
                                                    if ($row['status_id'] == 3 && !empty($row['availtoday_status_name'])) {
                                                        echo "<span class='availtoday-badge'>for " . htmlspecialchars($row['availtoday_status_name']) . "</span>";
                                                    }
                                                    
                                                    // Removed redundant 'also available today' badge for regular products
                                                    
                                                    echo "<span class='stock-badge " . $quantityClass . "'>
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
                                                 <span class='available-days-text'>" . formatAvailableDays($row['available_days']) . "</span>
                                             </td>
                                             <td>
                                                 <span class='selected-dates-text'>" . formatSelectedDates($row['status_id'] == 3 ? $row['todays_product_dates'] : '') . "</span>
                                             </td>
                                            <td>
                                                <div class='action-buttons'>
                                                    <button class='btn-action btn-edit' onclick=\"openEditModal(
                                                        '" . $row["id"] . "',     
                                                        '" . addslashes($row["name"]) . "', 
                                                        '" . addslashes($row["description"] ?? '') . "',
                                                        '" . $row["price"] . "', 
                                                        '" . $status_id . "',
                                                        '" . ($row["is_featured"] ? "true" : "false") . "',
                                                        '" . ($row["show_when_unavailable"] ? "true" : "false") . "',
                                                        '" . ($row["hide_when_unavailable"] ? "true" : "false") . "',
                                                        " . $quantity . ",
                                                        '" . addslashes($row['available_days']) . "',
                                                        '" . addslashes($row['status_name'] ?? 'Unknown') . "',
                                                        '" . ($row['unavailable_status_id'] ?? 'null') . "',
                                                        '" . addslashes($row['unavailable_status_name'] ?? '') . "',
                                                        '" . ($row['availtoday_status_id'] ?? 'null') . "',
                                                        '" . addslashes($row['availtoday_status_name'] ?? '') . "',
                                                        '" . addslashes($row['todays_product_dates'] ?? '') . "',
                                                        '" . addslashes($row['regular_today_dates'] ?? '') . "'
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
                                echo "<tr class='no-results'><td colspan='8'>
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

<!-- Hidden container with all products data for JavaScript -->
<div id="allProductsData" style="display: none;">
    <?php echo json_encode($all_products_data); ?>
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
        
        <div class="modal-content-wrapper">
            <!-- Single Panel - Form Fields -->
            <div class="modal-form-panel">
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

                    
                    <div class="form-group">
                        <label for="editProductDescription">Product Description</label>
                        <textarea id="editProductDescription" name="description" rows="3" placeholder="Enter product description..."></textarea>
                    </div>

                    <!-- Image Management Section -->
                    <div class="form-group image-management-section">
                        <h3>Product Images</h3>
                        
                        <div class="images-row">
                            <!-- Primary Image Section -->
                            <div class="primary-image-section">
                                <label>Primary Image</label>
                                <div class="primary-image-container" id="editPrimaryImageContainer">
                                    <div class="image-placeholder" id="editPrimaryPlaceholder">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21,15 16,10 5,21"></polyline>
                                        </svg>
                                        <span>Click to Upload Image</span>
                                    </div>
                                </div>
                                <div class="image-actions">
                                    <input type="file" id="editPrimaryImageInput" accept="image/*" style="display: none;">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editPrimaryImageInput').click()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7,10 12,15 17,10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                        Upload Primary
                                    </button>
                                    <button type="button" class="btn btn-danger" id="editRemovePrimaryBtn" style="display: none;" onclick="removePrimaryImage()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3,6 5,6 21,6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <!-- Additional Images Section -->
                            <div class="additional-images-section">
                                <label>Additional Images (Max 3)</label>
                                <div class="additional-images-container" id="editAdditionalImagesContainer">
                                    <div class="image-placeholder" id="editAdditionalPlaceholder">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21,15 16,10 5,21"></polyline>
                                        </svg>
                                        <span>Click to Upload Images</span>
                                    </div>
                                </div>
                                <div class="image-actions">
                                    <input type="file" id="editAdditionalImagesInput" accept="image/*" multiple style="display: none;">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editAdditionalImagesInput').click()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7,10 12,15 17,10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                        Add Images
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProductStatus">Status</label>
                            <select id="editProductStatus">
                                <option value="1">Pick Up</option>
                                <option value="2">Delivery</option>
                                <option value="3">Same Day Order</option>
                            </select>
                            
                            <!-- isAvailableToday radio button - only shown when Pick Up or Delivery is selected -->
                            <div id="isAvailableTodayContainer" style="display: none; margin-top: 10px;">
                                <div class="radio-group">
                                    <div class="radio-item">
                                        <input type="radio" id="isAvailableToday" name="isAvailableToday" value="true">
                                        <label for="isAvailableToday">Display as Same Day Order product</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" id="editAvailtodayOptions" style="display: none;">
                            <label for="editAvailtodayStatus">Same Day Order Options:</label>
                            <select id="editAvailtodayStatus">
                                <option value="1">Pick Up</option>
                                <option value="2">Delivery</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Availability:</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" id="editAvailable" name="editAvailability" value="available" checked>
                                    <label for="editAvailable">Available</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" id="editUnavailable" name="editAvailability" value="unavailable">
                                    <label for="editUnavailable">Unavailable</label>
                                </div>
                            </div>
                            <div id="editUnavailableTypeContainer" style="display: none; margin-top: 10px;">
                                <input type="hidden" id="editUnavailableType" value="">
                                <small style="color: #666; font-style: italic;">Unavailable type will be automatically set based on the product status above.</small>
                            </div>
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

                    <!-- Available Days for regular products (Pick Up/Delivery) -->
                    <div class="form-group" id="regularAvailableDaysContainer">
                        <label>Available Days:</label>
                        <div class="checkbox-group days-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="edit_available_days[]" id="edit_sunday" value="Sunday">
                                <label for="edit_sunday" style="display: inline;">Sunday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="edit_available_days[]" id="edit_monday" value="Monday">
                                <label for="edit_monday" style="display: inline;">Monday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="edit_available_days[]" id="edit_tuesday" value="Tuesday">
                                <label for="edit_tuesday" style="display: inline;">Tuesday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="edit_available_days[]" id="edit_wednesday" value="Wednesday">
                                <label for="edit_wednesday" style="display: inline;">Wednesday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="edit_available_days[]" id="edit_thursday" value="Thursday">
                                <label for="edit_thursday" style="display: inline;">Thursday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="edit_available_days[]" id="edit_friday" value="Friday">
                                <label for="edit_friday" style="display: inline;">Friday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="edit_available_days[]" id="edit_saturday" value="Saturday">
                                <label for="edit_saturday" style="display: inline;">Saturday</label>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar for Today's Products -->
                    <div class="form-group" id="todaysProductCalendarContainer" style="display: none;">
                        <label>Select Available Dates for Same Day Order Product:</label>
                        <div id="todaysProductCalendar"></div>
                        <input type="hidden" id="todaysProductDates" name="todays_product_dates">
                    </div>

                    <!-- Calendar for regular products that are also available today -->
                    <div class="form-group" id="availableTodayCalendarContainer" style="display: none;">
                        <label>Select Additional Dates for Today's Availability:</label>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="editAvailableTodayStatus">Same Day Options:</label>
                            <select id="editAvailableTodayStatus" name="available_today_status_id">
                                <option value="">Select...</option>
                                <option value="1">Pick Up</option>
                                <option value="2">Delivery</option>
                            </select>
                        </div>
                        <div id="availableTodayCalendar"></div>
                        <input type="hidden" id="availableTodayDates" name="available_today_dates">
                    </div>

                </form>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="modal-footer">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" form="editProductForm">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="delete-modal">
    <div class="delete-modal-content">
        <div class="delete-modal-header">
            <h3>Confirm Delete</h3>
        </div>
        <div class="delete-modal-body">
            <p>Are you sure you want to delete this product? This action will move it to the archive and cannot be undone.</p>
        </div>
        <div class="delete-modal-footer">
            <button class="delete-btn-cancel" onclick="hideDeleteModal()">Cancel</button>
            <button class="delete-btn-confirm" onclick="confirmDelete()">Delete Product</button>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

<!-- Hidden container for all products data -->
<script id="allProductsData" type="application/json">
<?php echo json_encode($all_products_data); ?>
</script>

</body>
</html>
