<?php
session_start();

// Admin authentication check
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../auth/login-signup.php");
    exit();
}

// Include database configuration
require_once __DIR__ . '/database-config.php';

// Initialize database and create table if needed
$conn = getDBConnection();
createPromotionsTable($conn);

// Pagination settings
$items_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Search and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], ['title', 'code', 'discount_value', 'start_date', 'end_date']) ? $_GET['sort'] : 'title';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'DESC' ? 'DESC' : 'ASC';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/backend/pages/user-page-content/promotions-settings.css">
    <script src="/backend/pages/user-page-content/promotions-settings.js" defer></script>
    <title>Promotions Management</title>
</head>
<body>
<?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>

<div class="promotions-container">
    <div class="main-container">
        <!-- Header Section -->
        <div class="page-header">
            <div class="header-content">
                <h1>Promotions & Coupons</h1>
                <p class="page-subtitle">Manage your promotional codes and discounts</p>
            </div>
                    
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Coupon
                </button>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-group">
                <div class="search-container">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" class="search-input" placeholder="Search coupons..." id="searchInput" oninput="searchCoupons()">
                </div>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="table-controls">
            <div class="select-controls">
                <div class="select-group">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    <label for="selectAll">Select All</label>
                </div>
                <div class="bulk-actions">
                    <button class="btn btn-secondary" onclick="bulkDelete()" id="bulkDeleteBtn" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="m19,6 0,14 a2,2 0 0,1 -2,2 H7 a2,2 0 0,1 -2,-2 V6 m3,0 V4 a2,2 0 0,1 2,-2 h4 a2,2 0 0,1 2,2 v2"></path>
                        </svg>
                        Delete Selected
                    </button>
                </div>
            </div>

            <div class="sort-controls">
                <label class="sort-label">Sort by:</label>
                <div class="sort-buttons">
                    <button class="sort-btn active" id="sort-title" onclick="toggleSort('title')">
                        Title
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    <button class="sort-btn" id="sort-code" onclick="toggleSort('code')">
                        Code
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    <button class="sort-btn" id="sort-discount" onclick="toggleSort('discount')">
                        Discount
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    <button class="sort-btn" id="sort-validity" onclick="toggleSort('validity')">
                        Validity
                        <svg class="sort-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Promotions Table -->
        <div class="promotions-container-table">
            <div class="table-wrapper">
                <table class="promotions-table">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="headerSelectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>Title</th>
                            <th>Code</th>
                            <th>Discount Type</th>
                            <th>Restrictions</th>
                            <th>Date Validity</th>
                            <th>Status</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="promotionsTableBody">
                        <?php
                            // Database connection
                            $conn = new mysqli("localhost", "root", "", "crud");
                            if ($conn->connect_error) {
                                die("Connection failed: " . $conn->connect_error);
                            }

                            // Check if promotions table exists, if not create it
                            $create_table_sql = "CREATE TABLE IF NOT EXISTS promotions (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                title VARCHAR(255) NOT NULL,
                                code VARCHAR(10) UNIQUE NOT NULL,
                                discount_type ENUM('shipping', 'percentage', 'fixed_amount') NOT NULL,
                                discount_value DECIMAL(10,2) NOT NULL,
                                min_spend DECIMAL(10,2) DEFAULT 0,
                                applicable_to ENUM('delivery', 'pickup', 'all', 'special') NOT NULL DEFAULT 'all',
                                usage_limit INT DEFAULT NULL,
                                usage_limit_per_user INT DEFAULT NULL,
                                used_count INT DEFAULT 0,
                                start_date DATE NOT NULL,
                                end_date DATE NOT NULL,
                                status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                            )";
                            $conn->query($create_table_sql);

                            // Fetch promotions with search and pagination
                            $search = isset($_GET['search']) ? $_GET['search'] : '';
                            $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'title';
                            $sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

                            $where_clause = '';
                            if (!empty($search)) {
                                $search = $conn->real_escape_string($search);
                                $where_clause = "WHERE title LIKE '%$search%' OR code LIKE '%$search%'";
                            }

                            $sql = "SELECT * FROM promotions $where_clause ORDER BY $sort_by $sort_order LIMIT $items_per_page OFFSET $offset";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $status_class = '';
                                    $status_text = ucfirst($row['status']);
                                    
                                    // Check if expired
                                    if (strtotime($row['end_date']) < time()) {
                                        $status_class = 'status-expired';
                                        $status_text = 'Expired';
                                    } else if ($row['status'] == 'active') {
                                        $status_class = 'status-active';
                                    } else {
                                        $status_class = 'status-inactive';
                                    }

                                    $discount_display = '';
                                    if ($row['discount_type'] == 'percentage') {
                                        $discount_display = $row['discount_value'] . '%';
                                    } else if ($row['discount_type'] == 'fixed_amount') {
                                        $discount_display = '₱' . number_format($row['discount_value'], 2);
                                    } else {
                                        $discount_display = 'Free Shipping';
                                    }

                                    $restrictions = [];
                                    if ($row['min_spend'] > 0) {
                                        $restrictions[] = 'Min: ₱' . number_format($row['min_spend'], 2);
                                    }
                                    if ($row['usage_limit']) {
                                        $restrictions[] = 'Limit: ' . $row['usage_limit'];
                                    }
                                    if ($row['usage_limit_per_user']) {
                                        $restrictions[] = 'Per user: ' . $row['usage_limit_per_user'];
                                    }
                                    $restrictions_text = !empty($restrictions) ? implode(', ', $restrictions) : 'None';

                                    echo "<tr class='promotion-row' data-id='{$row['id']}'>
                                        <td>
                                            <input type='checkbox' class='row-select' value='{$row['id']}' onchange='updateBulkActions()'>
                                        </td>
                                        <td class='promotion-title'>{$row['title']}</td>
                                        <td class='promotion-code'><code>{$row['code']}</code></td>
                                        <td class='discount-type'>{$discount_display}</td>
                                        <td class='restrictions'>{$restrictions_text}</td>
                                        <td class='validity'>" . date('M j, Y', strtotime($row['start_date'])) . " - " . date('M j, Y', strtotime($row['end_date'])) . "</td>
                                        <td><span class='status-badge {$status_class}'>{$status_text}</span></td>
                                        <td class='actions'>
                                            <button class='action-btn edit-btn' onclick='openEditModal({$row['id']})' title='Edit'>
                                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                    <path d='M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z'></path>
                                                </svg>
                                            </button>
                                            <button class='action-btn delete-btn' onclick='deleteCoupon({$row['id']})' title='Delete'>
                                                <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                                    <polyline points='3,6 5,6 21,6'></polyline>
                                                    <path d='m19,6 0,14 a2,2 0 0,1 -2,2 H7 a2,2 0 0,1 -2,-2 V6 m3,0 V4 a2,2 0 0,1 2,-2 h4 a2,2 0 0,1 2,2 v2'></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='no-data'>No promotions found. <a href='#' onclick='openAddModal()'>Create your first coupon</a></td></tr>";
                            }

                            $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <?php
                // Get total count for pagination
                $conn = new mysqli("localhost", "root", "", "crud");
                $count_sql = "SELECT COUNT(*) as total FROM promotions";
                if (!empty($search)) {
                    $search = $conn->real_escape_string($search);
                    $count_sql = "SELECT COUNT(*) as total FROM promotions WHERE title LIKE '%$search%' OR code LIKE '%$search%'";
                }
                $count_result = $conn->query($count_sql);
                $total_items = $count_result->fetch_assoc()['total'];
                $total_pages = ceil($total_items / $items_per_page);

                if ($total_pages > 1) {
                    echo "<div class='pagination'>";
                    
                    // Previous button
                    if ($current_page > 1) {
                        echo "<a href='?page=" . ($current_page - 1) . "' class='pagination-btn'>Previous</a>";
                    }
                    
                    // Page numbers
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = ($i == $current_page) ? 'active' : '';
                        echo "<a href='?page=$i' class='pagination-btn $active_class'>$i</a>";
                    }
                    
                    // Next button
                    if ($current_page < $total_pages) {
                        echo "<a href='?page=" . ($current_page + 1) . "' class='pagination-btn'>Next</a>";
                    }
                    
                    echo "</div>";
                }
                $conn->close();
            ?>
        </div>
    </div>
</div>

<!-- Add Coupon Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Coupon</h2>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addCouponForm" onsubmit="addCoupon(event)">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="code">Code * (Max 10 characters)</label>
                        <input type="text" id="code" name="code" maxlength="10" required pattern="[A-Za-z0-9]+" title="Only letters and numbers allowed">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="discountType">Discount Type *</label>
                            <select id="discountType" name="discount_type" required onchange="toggleDiscountValue()">
                                <option value="">Select Type</option>
                                <option value="shipping">Free Shipping</option>
                                <option value="percentage">Percentage Discount</option>
                                <option value="fixed_amount">Fixed Amount Discount</option>
                            </select>
                        </div>
                        <div class="form-group" id="discountValueGroup" style="display: none;">
                            <label for="discountValue">Discount Value *</label>
                            <input type="number" id="discountValue" name="discount_value" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="minSpend">Minimum Spend (₱)</label>
                        <input type="number" id="minSpend" name="min_spend" step="0.01" min="0" value="0">
                    </div>
                </div>

                <!-- Applicable To -->
                <div class="form-section">
                    <h3>Applicable To</h3>
                    <div class="form-group">
                        <label for="applicableTo">Product Type *</label>
                        <select id="applicableTo" name="applicable_to" required>
                            <option value="all">All Products</option>
                            <option value="delivery">Delivery Products Only</option>
                            <option value="pickup">Pickup Products Only</option>
                            <option value="special">Special Products</option>
                        </select>
                    </div>
                    
                    <h4>Restrictions</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <div class="switch-group">
                                <label class="switch">
                                    <input type="checkbox" id="unlimitedUsage" onchange="toggleUsageLimit()">
                                    <span class="slider"></span>
                                </label>
                                <label for="unlimitedUsage">Unlimited Usage</label>
                            </div>
                            <div id="usageLimitGroup">
                                <label for="usageLimit">Usage Limit</label>
                                <input type="number" id="usageLimit" name="usage_limit" min="1">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="switch-group">
                                <label class="switch">
                                    <input type="checkbox" id="unlimitedPerUser" onchange="togglePerUserLimit()">
                                    <span class="slider"></span>
                                </label>
                                <label for="unlimitedPerUser">Unlimited Per User</label>
                            </div>
                            <div id="perUserLimitGroup">
                                <label for="perUserLimit">Limit Per User</label>
                                <input type="number" id="perUserLimit" name="usage_limit_per_user" min="1" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validity -->
                <div class="form-section">
                    <h3>Validity Period</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="startDate">Start Date *</label>
                            <input type="date" id="startDate" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date *</label>
                            <input type="date" id="endDate" name="end_date" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Coupon Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Coupon</h2>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editCouponForm" onsubmit="updateCoupon(event)">
                <input type="hidden" id="editId" name="id">
                
                <!-- Same form structure as add modal -->
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-group">
                        <label for="editTitle">Title *</label>
                        <input type="text" id="editTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="editCode">Code * (Max 10 characters)</label>
                        <input type="text" id="editCode" name="code" maxlength="10" required pattern="[A-Za-z0-9]+" title="Only letters and numbers allowed">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editDiscountType">Discount Type *</label>
                            <select id="editDiscountType" name="discount_type" required onchange="toggleEditDiscountValue()">
                                <option value="">Select Type</option>
                                <option value="shipping">Free Shipping</option>
                                <option value="percentage">Percentage Discount</option>
                                <option value="fixed_amount">Fixed Amount Discount</option>
                            </select>
                        </div>
                        <div class="form-group" id="editDiscountValueGroup">
                            <label for="editDiscountValue">Discount Value *</label>
                            <input type="number" id="editDiscountValue" name="discount_value" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editMinSpend">Minimum Spend (₱)</label>
                        <input type="number" id="editMinSpend" name="min_spend" step="0.01" min="0" value="0">
                    </div>
                </div>

                <div class="form-section">
                    <h3>Applicable To</h3>
                    <div class="form-group">
                        <label for="editApplicableTo">Product Type *</label>
                        <select id="editApplicableTo" name="applicable_to" required>
                            <option value="all">All Products</option>
                            <option value="delivery">Delivery Products Only</option>
                            <option value="pickup">Pickup Products Only</option>
                            <option value="special">Special Products</option>
                        </select>
                    </div>
                    
                    <h4>Restrictions</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <div class="switch-group">
                                <label class="switch">
                                    <input type="checkbox" id="editUnlimitedUsage" onchange="toggleEditUsageLimit()">
                                    <span class="slider"></span>
                                </label>
                                <label for="editUnlimitedUsage">Unlimited Usage</label>
                            </div>
                            <div id="editUsageLimitGroup">
                                <label for="editUsageLimit">Usage Limit</label>
                                <input type="number" id="editUsageLimit" name="usage_limit" min="1">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="switch-group">
                                <label class="switch">
                                    <input type="checkbox" id="editUnlimitedPerUser" onchange="toggleEditPerUserLimit()">
                                    <span class="slider"></span>
                                </label>
                                <label for="editUnlimitedPerUser">Unlimited Per User</label>
                            </div>
                            <div id="editPerUserLimitGroup">
                                <label for="editPerUserLimit">Limit Per User</label>
                                <input type="number" id="editPerUserLimit" name="usage_limit_per_user" min="1">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Validity Period</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editStartDate">Start Date *</label>
                            <input type="date" id="editStartDate" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="editEndDate">End Date *</label>
                            <input type="date" id="editEndDate" name="end_date" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Status</h3>
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select id="editStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
