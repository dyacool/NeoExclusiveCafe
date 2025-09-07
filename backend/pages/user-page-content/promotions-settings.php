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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="/backend/pages/user-page-content/promotions-settings.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Promotions Management</title>
</head>
<body>
<?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>

<div class="promotions-container">
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-supply-order">
            <h4>Manage Promotions</h4>
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
                    <button id="filter-btn">
                        <i class="fas fa-filter"></i>
                        <span>Filter</span>
                    </button>
                    <div class="filter-container" style="display: none;">
                        <div class="filter-item">
                            <label for="voucher-type-filter">Voucher Type</label>
                            <select id="voucher-type-filter" name="voucher-type-filter">
                                <option value="" selected>All Types</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₱)</option>
                                <option value="free_shipping">Free Shipping Only</option>
                            </select>
                        </div>
                        <fieldset class="filter-fieldset" id="value-range-fieldset">
                            <legend>Value</legend>
                            <div class="field-group">
                                <div class="filter-item">
                                    <label for="value-min">Min</label>
                                    <input type="number" id="value-min" placeholder="Min Value">
                                </div>
                                <div class="filter-item">
                                    <label for="value-max">Max</label>
                                    <input type="number" id="value-max" placeholder="Max Value">
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="filter-fieldset">
                            <legend>Min Purchase</legend>
                            <div class="field-group">
                                <div class="filter-item">
                                    <label for="min-purchase-min">Min</label>
                                    <input type="number" id="min-purchase-min" placeholder="Min Purchase">
                                </div>
                                <div class="filter-item">
                                    <label for="min-purchase-max">Max</label>
                                    <input type="number" id="min-purchase-max" placeholder="Max Purchase">
                                </div>
                            </div>
                        </fieldset>
                        <div class="filter-item">
                            <label for="status-filter">Status</label>
                            <select id="status-filter" name="status-filter">
                                <option value="" selected>All Status</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="upcoming">Upcoming</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label for="applies-to-filter">Applies To</label>
                            <select id="applies-to-filter" name="applies-to-filter">
                                <option value="" selected>All</option>
                                <option value="all">All Products</option>
                                <option value="delivery">Delivery Products</option>
                                <option value="pickup">Pickup Products</option>
                                <option value="special">Special Products</option>
                            </select>
                        </div>
                        <fieldset class="filter-fieldset">
                            <legend>Usage Limit (Global)</legend>
                            <div class="field-group">
                                <div class="filter-item">
                                    <label for="usage-limit-min">Min</label>
                                    <input type="number" id="usage-limit-min" placeholder="Min Usage Limit">
                                </div>
                                <div class="filter-item">
                                    <label for="usage-limit-max">Max</label>
                                    <input type="number" id="usage-limit-max" placeholder="Max Usage Limit">
                                </div>
                            </div>
                            <div class="filter-item">
                                <label for="usage-limit-type">Global Usage Limit Type</label>
                                <select id="usage-limit-type">
                                    <option value="" selected>All</option>
                                    <option value="unlimited">Unlimited (∞)</option>
                                    <option value="limited">Limited</option>
                                </select>
                            </div>
                        </fieldset>
                        <fieldset class="filter-fieldset">
                            <legend>Usage Limit Per User</legend>
                            <div class="field-group">
                                <div class="filter-item">
                                    <label for="usage-limit-user-min">Min</label>
                                    <input type="number" id="usage-limit-user-min" placeholder="Min Usage/User">
                                </div>
                                <div class="filter-item">
                                    <label for="usage-limit-user-max">Max</label>
                                    <input type="number" id="usage-limit-user-max" placeholder="Max Usage/User">
                                </div>
                            </div>
                            <div class="filter-item">
                                <label for="usage-limit-user-type">Per User Usage Limit Type</label>
                                <select id="usage-limit-user-type">
                                    <option value="" selected>All</option>
                                    <option value="unlimited">Unlimited (∞)</option>
                                    <option value="limited">Limited</option>
                                </select>
                            </div>
                        </fieldset>
                        <fieldset class="filter-fieldset">
                            <legend>Validity Date</legend>
                            <div class="field-group">
                                <div class="filter-item">
                                    <label for="validity-from">From</label>
                                    <input type="date" id="validity-from">
                                </div>
                                <div class="filter-item">
                                    <label for="validity-to">To</label>
                                    <input type="date" id="validity-to">
                                </div>
                            </div>
                        </fieldset>
                        <div class="filter-btns">
                            <button id="reset-filters-btn" type="button">Reset</button>
                            <button id="apply-filters-btn" type="button">Apply</button>
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
                        <th>Method</th>
                            <th>Code</th>
                        <th>Discount</th>
                            <th>Restrictions</th>
                        <th>Usage</th>
                        <th>Valid Period</th>
                        <th>Sale Channel</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <th>Id</th>
                        <th>Title</th>
                        <th>Method</th>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Restrictions</th>
                        <th>Usage</th>
                        <th>Valid Period</th>
                        <th>Sale Channel</th>
                        <th>Status</th>
                    </tr>
                </tfoot>
                </table>
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
                    <div class="form-group">
                        <label for="applicationMethod">Application Method *</label>
                        <select id="applicationMethod" name="application_method" required>
                            <option value="voucher_code">Voucher Code</option>
                            <option value="automatic_discount">Automatic Discount</option>
                        </select>
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
                    <div class="form-group">
                        <label for="editApplicationMethod">Application Method *</label>
                        <select id="editApplicationMethod" name="application_method" required>
                            <option value="voucher_code">Voucher Code</option>
                            <option value="automatic_discount">Automatic Discount</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editDiscountType">Discount Type *</label>
                            <select id="editDiscountType" name="type" required onchange="toggleEditDiscountValue()">
                                <option value="">Select Type</option>
                                <option value="free_shipping">Free Shipping</option>
                                <option value="percentage">Percentage Discount</option>
                                <option value="fixed">Fixed Amount Discount</option>
                            </select>
                        </div>
                        <div class="form-group" id="editDiscountValueGroup">
                            <label for="editDiscountValue">Discount Value *</label>
                            <input type="number" id="editDiscountValue" name="value" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editMinSpend">Minimum Spend (₱)</label>
                        <input type="number" id="editMinSpend" name="min_purchase" step="0.01" min="0" value="0">
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
                            <input type="date" id="editStartDate" name="activation_date" required>
                        </div>
                        <div class="form-group">
                            <label for="editEndDate">End Date *</label>
                            <input type="date" id="editEndDate" name="expiration_date" required>
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
