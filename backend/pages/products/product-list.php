<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: ../auth/login-signup.php");
        exit();
    }

    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Include config file for base URL
    require_once __DIR__ . "/../admin-includes/config.php";
    include __DIR__ . "/../admin-includes/database.php";
    require_once __DIR__ . "/../admin-includes/settings-helper.php";
    require_once __DIR__ . "/todays-products-handler.php";
    
    // Try to include Cloudinary image fetcher (may fail if vendor/autoload.php is missing)
    try {
        require_once __DIR__ . "/../../includes/cloudinary-image-fetcher.php";
    } catch (Exception $e) {
        error_log("Failed to load cloudinary-image-fetcher.php: " . $e->getMessage());
    } catch (Error $e) {
        error_log("Fatal error loading cloudinary-image-fetcher.php: " . $e->getMessage());
    }
    
    // Clean up past dates automatically when page loads
    try {
        cleanupPastDates();
    } catch (Exception $e) {
        error_log("Error cleaning up past dates: " . $e->getMessage());
    }
    
    // Get global available days from settings
    $globalAvailableDays = getSetting('global_available_days', []);

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
    <script src="/backend/pages/products/sdo-quantity-manager.js" defer></script>
    <title>Product Management</title>
</head>
<body>
<?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>

<!-- Hidden CSRF token for AJAX requests -->
<input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

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
                    <button class="filter-btn" onclick="filterProducts('Delivery or Pick Up', this)" data-filter="delivery-pickup">
                        <span class="filter-count" id="count-delivery-pickup">0</span>
                        Delivery or Pick Up
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
                        <option value="unavailable-delivery-pickup">Unavailable Delivery or Pick Up</option>
                        <option value="unavailable-today">Unavailable for Same Day Order</option>
                    </select>
                </div>
            </div>
            
            <!-- Preorder Days Settings -->
            <div class="filter-group">
                <button type="button" class="preorder-days-toggle" onclick="togglePreorderDaysSettings()" id="preorderDaysToggle">
                    <svg class="toggle-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"></polyline>
                    </svg>
                    Preorder Days Settings
                    <span class="current-selection"><?php echo !empty($globalAvailableDays) ? '(' . count($globalAvailableDays) . ' days selected)' : '(No days selected)'; ?></span>
                </button>
                
                <div class="preorder-days-content" id="preorderDaysContent" style="display: none;">
                    <label class="filter-label">Set Available Days (for Pick Up, Delivery, and Delivery or Pick Up):</label>
                    <div class="checkbox-group days-group" style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                        <div class="checkbox-item">
                            <input type="checkbox" name="global_available_days[]" id="global_sunday" value="Sunday" <?php echo in_array('Sunday', $globalAvailableDays) ? 'checked' : ''; ?> onchange="updateGlobalAvailableDays()">
                            <label for="global_sunday" style="display: inline; cursor: pointer;">Sunday</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="global_available_days[]" id="global_monday" value="Monday" <?php echo in_array('Monday', $globalAvailableDays) ? 'checked' : ''; ?> onchange="updateGlobalAvailableDays()">
                            <label for="global_monday" style="display: inline; cursor: pointer;">Monday</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="global_available_days[]" id="global_tuesday" value="Tuesday" <?php echo in_array('Tuesday', $globalAvailableDays) ? 'checked' : ''; ?> onchange="updateGlobalAvailableDays()">
                            <label for="global_tuesday" style="display: inline; cursor: pointer;">Tuesday</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="global_available_days[]" id="global_wednesday" value="Wednesday" <?php echo in_array('Wednesday', $globalAvailableDays) ? 'checked' : ''; ?> onchange="updateGlobalAvailableDays()">
                            <label for="global_wednesday" style="display: inline; cursor: pointer;">Wednesday</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="global_available_days[]" id="global_thursday" value="Thursday" <?php echo in_array('Thursday', $globalAvailableDays) ? 'checked' : ''; ?> onchange="updateGlobalAvailableDays()">
                            <label for="global_thursday" style="display: inline; cursor: pointer;">Thursday</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="global_available_days[]" id="global_friday" value="Friday" <?php echo in_array('Friday', $globalAvailableDays) ? 'checked' : ''; ?> onchange="updateGlobalAvailableDays()">
                            <label for="global_friday" style="display: inline; cursor: pointer;">Friday</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="global_available_days[]" id="global_saturday" value="Saturday" <?php echo in_array('Saturday', $globalAvailableDays) ? 'checked' : ''; ?> onchange="updateGlobalAvailableDays()">
                            <label for="global_saturday" style="display: inline; cursor: pointer;">Saturday</label>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <p style="margin: 0; font-size: 13px; color: #666;">
                            <strong>Current Selection:</strong> <?php echo !empty($globalAvailableDays) ? implode(', ', $globalAvailableDays) : 'None selected'; ?>
                        </p>
                        <button type="button" class="btn btn-primary" onclick="applyGlobalAvailableDays()">Apply</button>
                    </div>
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
                            // Count total products for pagination
                            $count_sql = "SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL";
                            $count_result = $conn->query($count_sql);
                            $total_products = $count_result->fetch_assoc()['total'];
                            $total_pages = ceil($total_products / $items_per_page);

                            // Query with LIMIT and OFFSET for pagination
                            $sql = "SELECT 
                                        p.id, p.sku, p.name, p.description, p.price, p.status_id, ps.name AS status_name, 
                                        p.unavailable_status_id, ups.name AS unavailable_status_name,
                                        p.category_id, c.name AS category_name,
                                        p.is_featured, p.show_when_unavailable, p.hide_when_unavailable,
                                        p.quantity, p.availtoday_status_id, ats.name AS availtoday_status_name,
                                        qpd.quantity as sameday_stock_today,
                                        GROUP_CONCAT(DISTINCT pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days,
                                        GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ',') as todays_product_dates,
                                        GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ',') as regular_today_dates,
                                        -- Check if same day order has future dates
                                        CASE 
                                            WHEN p.status_id = 4 AND EXISTS (
                                                SELECT 1 FROM todays_products_dates tpd2 
                                                WHERE tpd2.product_id = p.id AND tpd2.available_date >= CURDATE()
                                            ) THEN 1
                                            ELSE 0
                                        END as has_future_sdo_dates,
                                        -- Check if product has stock (for same day order or regular)
                                        CASE 
                                            WHEN p.status_id = 4 THEN COALESCE(qpd.quantity, 0)
                                            ELSE p.quantity
                                        END as effective_stock
                                    FROM products p
                                    LEFT JOIN product_statuses ps ON p.status_id = ps.id
                                    LEFT JOIN unavail_products_status ups ON p.unavailable_status_id = ups.id
                                    LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    LEFT JOIN product_day pd ON p.id = pd.product_id
                                    LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
                                    LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
                                    LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
                                    WHERE p.deleted_at IS NULL AND p.id > 0
                                    GROUP BY p.id
                                    ORDER BY 
                                        -- 1. Same Day Order with future dates and stock first
                                        CASE 
                                            WHEN p.status_id = 4 AND has_future_sdo_dates = 1 AND effective_stock > 0 THEN 1
                                            -- 2. Pre-order products (Pickup, Delivery, Delivery or Pickup) with stock
                                            WHEN p.status_id IN (1, 2, 3) AND effective_stock > 0 THEN 2
                                            -- 3. Pre-order products without stock
                                            WHEN p.status_id IN (1, 2, 3) AND effective_stock = 0 THEN 3
                                            -- 4. Same Day Order without future dates or stock (unavailable)
                                            WHEN p.status_id = 4 THEN 4
                                            -- 5. Everything else
                                            ELSE 5
                                        END ASC,
                                        -- Within each group, sort by created date (newest first)
                                        p.created_at DESC
                                    LIMIT $items_per_page OFFSET $offset";
                                    
                            // Also get all products for JavaScript filtering (without pagination)
                            $all_products_sql = "SELECT 
                                                    p.id, p.sku, p.name, p.description, p.price, p.status_id, ps.name AS status_name, 
                                                    p.unavailable_status_id, ups.name AS unavailable_status_name,
                                                    p.category_id, c.name AS category_name,
                                                    p.is_featured, p.show_when_unavailable, p.hide_when_unavailable,
                                                    p.quantity, p.availtoday_status_id, ats.name AS availtoday_status_name,
                                                    qpd.quantity as sameday_stock_today,
                                                    GROUP_CONCAT(DISTINCT pd.day_of_week ORDER BY FIELD(pd.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') as available_days,
                                                    GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ',') as todays_product_dates,
                                                    GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ',') as regular_today_dates,
                                                    -- Check if same day order has future dates
                                                    CASE 
                                                        WHEN p.status_id = 4 AND EXISTS (
                                                            SELECT 1 FROM todays_products_dates tpd2 
                                                            WHERE tpd2.product_id = p.id AND tpd2.available_date >= CURDATE()
                                                        ) THEN 1
                                                        ELSE 0
                                                    END as has_future_sdo_dates,
                                                    -- Check if product has stock (for same day order or regular)
                                                    CASE 
                                                        WHEN p.status_id = 4 THEN COALESCE(qpd.quantity, 0)
                                                        ELSE p.quantity
                                                    END as effective_stock
                                                FROM products p
                                                LEFT JOIN product_statuses ps ON p.status_id = ps.id
                                                LEFT JOIN unavail_products_status ups ON p.unavailable_status_id = ups.id
                                                LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                                                LEFT JOIN categories c ON p.category_id = c.id
                                                LEFT JOIN product_day pd ON p.id = pd.product_id
                                                LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
                                                LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
                                                LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
                                                WHERE p.deleted_at IS NULL AND p.id > 0
                                                GROUP BY p.id
                                                ORDER BY 
                                                    -- 1. Same Day Order with future dates and stock first
                                                    CASE 
                                                        WHEN p.status_id = 4 AND has_future_sdo_dates = 1 AND effective_stock > 0 THEN 1
                                                        -- 2. Pre-order products (Pickup, Delivery, Delivery or Pickup) with stock
                                                        WHEN p.status_id IN (1, 2, 3) AND effective_stock > 0 THEN 2
                                                        -- 3. Pre-order products without stock
                                                        WHEN p.status_id IN (1, 2, 3) AND effective_stock = 0 THEN 3
                                                        -- 4. Same Day Order without future dates or stock (unavailable)
                                                        WHEN p.status_id = 4 THEN 4
                                                        -- 5. Everything else
                                                        ELSE 5
                                                    END ASC,
                                                    -- Within each group, sort by created date (newest first)
                                                    p.created_at DESC";
                                    
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
                            
                            // Collect product IDs for batch image fetching
                            $productIds = [];
                            $products = [];
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $productIds[] = $row['id'];
                                    $products[] = $row;
                                }
                            }
                            
                            // Batch fetch images from product_images table
                            $productImages = [];
                            if (!empty($productIds)) {
                                error_log("product-list.php: Attempting to fetch images for " . count($productIds) . " products");
                                try {
                                    // Query product_images table for primary images
                                    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                                    $sql = "SELECT product_id, cloud_url 
                                            FROM product_images 
                                            WHERE product_id IN ($placeholders) 
                                            AND is_primary = 1 
                                            AND is_removed = 0
                                            ORDER BY product_id";
                                    
                                    $stmt = $conn->prepare($sql);
                                    $types = str_repeat('i', count($productIds));
                                    $stmt->bind_param($types, ...$productIds);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    while ($row = $result->fetch_assoc()) {
                                        $productImages[$row['product_id']] = [
                                            'url' => $row['cloud_url'],
                                            'source' => 'cloudinary'
                                        ];
                                    }
                                    $stmt->close();
                                    
                                    error_log("product-list.php: Successfully fetched " . count($productImages) . " images from product_images table");
                                } catch (Exception $e) {
                                    error_log("product-list.php: Error fetching images: " . $e->getMessage());
                                }
                            } else {
                                error_log("product-list.php: No product IDs to fetch images for");
                            }

                            if (!empty($products)) {
                                foreach ($products as $row) {
                                    // Debug: Log availtoday_status data
                                    error_log("Product ID: " . $row['id'] . " | availtoday_status_id: " . ($row['availtoday_status_id'] ?? 'NULL') . " | availtoday_status_name: " . ($row['availtoday_status_name'] ?? 'NULL'), 3, __DIR__ . "/../../../logs/php_errors.log");
                                    
                                    $status_id = isset($row["status_id"]) ? $row["status_id"] : 1;
                                    $quantity = isset($row["quantity"]) ? $row["quantity"] : 0;
                                    
                                    // Determine stock display based on status_id
                                    $stockDisplay = '';
                                    $quantityClass = '';
                                    
                                    if ($status_id == 4) {
                                        // Status 4: Same Day Order - check if today is available
                                        $today_date = date('Y-m-d');
                                        $todays_dates = !empty($row['todays_product_dates']) ? explode(',', $row['todays_product_dates']) : [];
                                        $is_available_today = in_array($today_date, $todays_dates);
                                        
                                        if ($is_available_today && isset($row['sameday_stock_today'])) {
                                            // Today is available, show same-day stock
                                            $sameday_stock = intval($row['sameday_stock_today']);
                                            $stockDisplay = $sameday_stock . ' in stock';
                                            $quantityClass = $sameday_stock <= 5 ? 'low-stock' : ($sameday_stock <= 10 ? 'medium-stock' : 'good-stock');
                                        } else {
                                            // Today is not available
                                            $stockDisplay = 'N/A';
                                            $quantityClass = 'na-stock';
                                        }
                                    } else {
                                        // Status 1, 2, 3: Pre-order - show products.quantity
                                        $stockDisplay = $quantity . ' in stock';
                                        $quantityClass = $quantity <= 5 ? 'low-stock' : ($quantity <= 10 ? 'medium-stock' : 'good-stock');
                                    }

                                    $statusClass = strtolower(str_replace(' ', '-', $row['status_name'] ?? 'Unknown'));

                                    // Get image from Cloudinary with error handling
                                    $imagePath = 'https://res.cloudinary.com/dvdccumbs/image/upload/c_fill,w_400,h_400,g_center/e_blur:1000,co_rgb:cccccc,b_rgb:f0f0f0/sample.jpg'; // Cloudinary placeholder
                                    try {
                                        if (isset($productImages[$row['id']])) {
                                            $imagePath = $productImages[$row['id']]['url'];
                                            error_log("product-list.php: Product {$row['id']} using Cloudinary image: " . substr($imagePath, 0, 80));
                                        } else {
                                            error_log("product-list.php: Product {$row['id']} ({$row['name']}) NOT in productImages array - using placeholder");
                                        }
                                    } catch (Exception $e) {
                                        error_log("Error displaying image for product {$row['id']}: " . $e->getMessage());
                                    }

                                    $displayStatus = ($row['status_id'] == 4) ? 'Same Day Order' : ($row['status_name'] ?? 'Unknown');
                                    
                                    // Format status badge text based on status_id
                                    if ($row['status_id'] == 4) {
                                        // Status 4: Show just the status name
                                        $statusBadgeText = $displayStatus;
                                    } else {
                                        // Status 1, 2, 3: Show "P. Order: [status]"
                                        $statusBadgeText = "P. Order: " . $displayStatus;
                                    }
                                    
                                    echo "<tr data-status='" . $displayStatus . "' data-name='" . strtolower($row['name']) . "' data-sku='" . strtolower($row['sku']) . "'>
                                            <td>
                                                <div class='product-image-container'>
                                                    <img class='product-image' src='" . htmlspecialchars($imagePath) . "' alt='" . htmlspecialchars($row['name']) . "' loading='lazy' onerror=\"this.src='https://res.cloudinary.com/dvdccumbs/image/upload/c_fill,w_400,h_400,g_center/e_blur:1000,co_rgb:cccccc,b_rgb:f0f0f0/sample.jpg'\">
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
                                                <span class='category-text'>" . (!empty($row['category_name']) ? htmlspecialchars($row['category_name']) : '<span style="color: #9ca3af;">No Category</span>') . "</span>
                                            </td>
                                            <td>
                                                <span class='price-text'>₱" . number_format($row['price'], 2) . "</span>
                                            </td>
                                            <td>
                                                <div class='status-container'>
                                                    <span class='status-badge status-" . $statusClass . "'>" . $statusBadgeText . "</span>";
                                                    
                                                    // Show badge for availtoday_status
                                                    if (!empty($row['availtoday_status_name'])) {
                                                        if ($row['status_id'] == 4) {
                                                            // Same Day Order - show "For [status]" (blue)
                                                            echo "<span class='availtoday-badge'>S.D.O.: " . htmlspecialchars($row['availtoday_status_name']) . "</span>";
                                                        } else if ($row['status_id'] == 1 || $row['status_id'] == 2 || $row['status_id'] == 3) {
                                                            // Pick Up, Delivery, or Delivery or Pick Up - show "Also for SDO: [status]" (green)
                                                            echo "<span class='availtoday-badge-also'>S.D.O.: " . htmlspecialchars($row['availtoday_status_name']) . "</span>";
                                                        }
                                                    }
                                                    
                                                    echo "<span class='stock-badge " . $quantityClass . "'>
                                                        <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                            <path d='M20 7h-9'></path>
                                                            <path d='M14 17H5'></path>
                                                            <circle cx='17' cy='17' r='3'></circle>
                                                            <circle cx='7' cy='7' r='3'></circle>
                                                        </svg>
                                                        " . $stockDisplay . "
                                                    </span>
                                                </div>
                                            </td>
                                                                                         <td>
                                                 <span class='available-days-text'>" . formatAvailableDays($row['available_days']) . "</span>
                                             </td>
                                             <td>
                                                 <span class='selected-dates-text'>" . formatSelectedDates($row['status_id'] == 4 ? $row['todays_product_dates'] : $row['regular_today_dates']) . "</span>
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
                                                        '" . addslashes($row['regular_today_dates'] ?? '') . "',
                                                        '" . ($row['category_id'] ?? 'null') . "'
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

                    <div class="form-group">
                        <label for="editProductCategory">Category</label>
                        <select id="editProductCategory">
                            <option value="">No Category</option>
                            <?php
                            // Fetch active categories
                            $cat_sql = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
                            $cat_result = mysqli_query($conn, $cat_sql);
                            if ($cat_result) {
                                while ($cat_row = mysqli_fetch_assoc($cat_result)) {
                                    echo "<option value='" . $cat_row['id'] . "'>" . htmlspecialchars($cat_row['name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
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
                            <label>Order Types</label>
                            
                            <!-- Pre-order Checkbox -->
                            <div class="checkbox-item" style="margin-top: 8px;">
                                <input type="checkbox" id="editPreOrderCheckbox" onchange="handlePreOrderCheckboxChange()">
                                <label for="editPreOrderCheckbox">Pre-order</label>
                            </div>
                            
                            <!-- Pre-order Dropdown (conditional) -->
                            <div id="editPreOrderOptions" style="display: none; margin-left: 24px; margin-top: 8px;">
                                <label for="editPreOrderStatus" style="font-size: 0.75rem; margin-bottom: 4px;">Pre-order Shipping Method:</label>
                                <select id="editPreOrderStatus">
                                    <option value="1">Pick Up</option>
                                    <option value="2">Delivery</option>
                                    <option value="3">Delivery or Pick Up</option>
                                </select>
                            </div>
                            
                            <!-- Same-day Order Checkbox -->
                            <div class="checkbox-item" style="margin-top: 12px;">
                                <input type="checkbox" id="editSameDayCheckbox" onchange="handleSameDayCheckboxChange()">
                                <label for="editSameDayCheckbox">Same-day order</label>
                            </div>
                            
                            <!-- Same-day Order Dropdown (conditional) -->
                            <div id="editSameDayOptions" style="display: none; margin-left: 24px; margin-top: 8px;">
                                <label for="editSameDayStatus" style="font-size: 0.75rem; margin-bottom: 4px;">Same-day Order Shipping Method:</label>
                                <select id="editSameDayStatus">
                                    <option value="1">Pick Up</option>
                                    <option value="2">Delivery</option>
                                    <option value="3">Delivery and Pick Up</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="availabilityContainer">
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

                    <!-- Calendar for Same-day Orders (shown when same-day checkbox is checked) -->
                    <div class="form-group" id="todaysProductCalendarContainer" style="display: none;">
                        <label>Select dates for same day order:</label>
                        <div style="display: flex; gap: 20px; align-items: flex-start;">
                            <div style="flex: 0 0 auto;">
                                <div id="todaysProductCalendar"></div>
                            </div>
                            <div style="flex: 1; min-width: 250px;">
                                <!-- Quantity per day manager -->
                                <div id="sdoQuantityContainerToday">
                                    <p style="color: #6b7280; font-size: 13px;">Select dates to set quantities</p>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="todaysProductDates" name="todays_product_dates">
                    </div>
                    
                    <!-- Calendar for regular products that are also available today -->
                    <div class="form-group" id="availableTodayCalendarContainer" style="display: none;">
                        <label>Select dates for same day order:</label>
                        <div style="display: flex; gap: 20px; align-items: flex-start;">
                            <div style="flex: 0 0 auto;">
                                <div id="availableTodayCalendar"></div>
                            </div>
                            <div style="flex: 1; min-width: 250px;">
                                <!-- Quantity per day manager for regular products with SDO -->
                                <div id="sdoQuantityContainerRegular">
                                    <p style="color: #6b7280; font-size: 13px;">Select dates to set quantities</p>
                                </div>
                            </div>
                        </div>
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
