<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

// Include database configuration
require_once __DIR__ . '/database-config.php';

// Initialize database and create table if needed
$conn = getDBConnection();
createPromotionsTable($conn);
createCouponUsageTable($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link rel="stylesheet" href="/backend/pages/user-page-content/promotions-settings.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Promotions Management</title>
</head>
<body>
<?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
<?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>


<div class="promotions-container">

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-section">
                <p class="page-subtitle">Manage your website's coupon and promotions</p>
            </div>
        </div>
    </div>
    <div class="main-container">


        <!-- Header Section -->
        <div class="header-supply-order">
            <div class="all-controls-supply-order">
                <div class="controls-supply-order">
                    <button id="supply-order-new-btn">
                        <i class="fas fa-plus"></i>
                        <span>New</span>
                    </button>
                    <button id="view-supply-order-btn">
                        <i class="fas fa-eye"></i>
                        <span>View</span>
                    </button>
                    <button id="reactivate-voucher-btn" class="reactivate-btn" disabled>
                        <i class="fas fa-redo"></i>
                        <span>Reactivate</span>
                    </button>
                </div>
                <div class="controls-supply-order-right">
                    <!-- Filter Buttons -->
                    <div class="filter-group">
                        <div class="filter-buttons">
                            <button class="filter-btn active" onclick="filterVouchers('all', this)" data-filter="all">
                                <span class="filter-count" id="count-all">0</span>
                                All
                            </button>
                            <button class="filter-btn" onclick="filterVouchers('active', this)" data-filter="active">
                                <span class="filter-count" id="count-active">0</span>
                                Active
                            </button>
                            <button class="filter-btn" onclick="filterVouchers('expired', this)" data-filter="expired">
                                <span class="filter-count" id="count-expired">0</span>
                                Expired
                            </button>
                            <button class="filter-btn" onclick="filterVouchers('fixed', this)" data-filter="fixed">
                                <span class="filter-count" id="count-fixed">0</span>
                                Fixed Amount
                            </button>
                            <button class="filter-btn" onclick="filterVouchers('percentage', this)" data-filter="percentage">
                                <span class="filter-count" id="count-percentage">0</span>
                                Percentage
                            </button>
                            <button class="filter-btn" onclick="filterVouchers('free_shipping', this)" data-filter="free_shipping">
                                <span class="filter-count" id="count-free-shipping">0</span>
                                Free Shipping
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="table-div">
            <table id="supply-order-table" class="display stripe" style="width:100%">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Title</th>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Restrictions</th>
                            <th>Usage</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                <tbody></tbody>
                </table>
        </div>

        <!-- Custom Pagination Container -->
        <div class="pagination-container" style="display: none;" id="custom-pagination">
            <div class="pagination-info" id="pagination-info">
                Showing 0 of 0 promotions
            </div>
            <nav class="pagination" id="pagination-nav">
                <!-- Pagination buttons will be inserted here -->
            </nav>
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
                            <select id="discountType" name="type" required onchange="toggleDiscountValue()">
                                <option value="">Select Type</option>
                                <option value="free_shipping">Free Shipping</option>
                                <option value="percentage">Percentage Discount</option>
                                <option value="fixed">Fixed Amount Discount</option>
                            </select>
                        </div>
                        <div class="form-group" id="discountValueGroup" style="display: none;">
                            <label for="discountValue">Discount Value *</label>
                            <input type="number" id="discountValue" name="value" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="minSpend">Minimum Spend (₱)</label>
                        <input type="number" id="minSpend" name="min_purchase" step="0.01" min="0" value="0">
                    </div>
                </div>

                <!-- Validity -->
                <div class="form-section">
                    <h3>Restrictions</h3>
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
                            <input type="date" id="startDate" name="activation_date" required>
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date *</label>
                            <input type="date" id="endDate" name="expiration_date" required>
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

<!-- View Coupon Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="viewModalTitle">Coupon Details</h2>
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Title</label>
                        <div class="view-field" id="viewTitle">-</div>
                    </div>
                    <div class="form-group">
                        <label>Code</label>
                        <div class="view-field" id="viewCode">-</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Discount Type</label>
                        <div class="view-field" id="viewDiscountType">-</div>
                    </div>
                    <div class="form-group">
                        <label>Discount Value</label>
                        <div class="view-field" id="viewDiscount">-</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Minimum Spend</label>
                    <div class="view-field" id="viewMinSpend">-</div>
                </div>
            </div>

            <div class="form-section">
                <h3>Restrictions</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Usage Limit</label>
                        <div class="view-field" id="viewUsageLimit">-</div>
                    </div>
                    <div class="form-group">
                        <label>Limit Per User</label>
                        <div class="view-field" id="viewPerUserLimit">-</div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Validity & Status</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Valid Period</label>
                        <div class="view-field" id="viewValidPeriod">-</div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <div class="view-field" id="viewStatus">-</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Reactivate Voucher Modal -->
<div id="reactivate-voucher-modal" class="modal" style="display:none;z-index:2000;">
    <div class="modal-content voucher-modal-content" style="max-width: 28em; min-width: 20em;">
        <span class="close" id="reactivate-voucher-modal-close">&times;</span>
        <h2 class="voucher-modal-title">Reactivate Voucher</h2>
        <p class="voucher-modal-desc">Set new activation and expiration dates for this voucher.</p>
        <form id="reactivate-voucher-form">
            <div class="form-row">
                <label for="reactivate-activation-date">Activation Date <span class="required-asterisk">*</span></label>
                <input type="date" id="reactivate-activation-date" name="activation_date" required />
            </div>
            <div class="form-row">
                <label for="reactivate-expiration-date">Expiration Date <span class="required-asterisk">*</span></label>
                <input type="date" id="reactivate-expiration-date" name="expiration_date" required />
            </div>
            <div class="voucher-modal-footer">
                <button type="button" class="voucher-submit-btn" id="reactivate-voucher-submit">Reactivate</button>
            </div>
        </form>
        <div id="reactivate-voucher-loader-overlay" style="display:none;">
            <div class="form-loader-spinner"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.7/js/dataTables.js"></script>
<script src="/backend/pages/user-page-content/promotions-settings.js"></script>

</body>
</html>
