<?php
// Use admin-auth for authentication (it loads database.php and SessionManager in correct order)
require_once __DIR__ . '/../../login/admin/admin-auth.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include config file for base URL (database.php already loaded by admin-auth.php)
require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

/**
 * Validate uploaded image file
 * 
 * @param array $file $_FILES array element
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateUploadedImage($file) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error: ' . $file['error']];
    }
    
    // Validate file exists
    if (!file_exists($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Uploaded file not found'];
    }
    
    // Validate file type using getimagesize
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['valid' => false, 'error' => 'File is not a valid image'];
    }
    
    // Check allowed MIME types (JPEG, PNG, GIF, WebP)
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed'];
    }
    
    // Check file size (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB in bytes
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File size exceeds 10MB limit'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Sanitize filename for Cloudinary public ID
 * 
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitizeFilenameForCloudinary($filename) {
    // Remove extension
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Replace spaces and special characters with underscores
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    
    // Remove multiple consecutive underscores
    $name = preg_replace('/_+/', '_', $name);
    
    // Trim underscores from start and end
    $name = trim($name, '_');
    
    return $name;
}

// Function to generate SKU **only when inserting a new product**
function generateSKU($conn) {
    $prefix = "SD-";

    // Fetch the last SKU from non-deleted products only
    $result = $conn->query("SELECT sku FROM products WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_sku = $row['sku'];
        
        // Extract number part and increment
        $number = (int)substr($last_sku, 3) + 1;
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    } else {
        // If no active products exist, find the highest SKU number from all products (including deleted)
        // to ensure we don't reuse SKU numbers
        $result = $conn->query("SELECT sku FROM products ORDER BY CAST(SUBSTRING(sku, 4) AS UNSIGNED) DESC LIMIT 1");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_sku = $row['sku'];
            $number = (int)substr($last_sku, 3) + 1;
            return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
        } else {
            return $prefix . "00001"; // First product starts at SD-00001
        }
    }
}

$sku = generateSKU($conn); // Generate SKU when the page loads

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    require_once __DIR__ . "/../admin-includes/settings-helper.php";
    
    $sku = generateSKU($conn);
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    
    // Handle category_id - allow NULL if not selected
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    
    // Determine status_id based on checkbox selections
    $preOrderChecked = isset($_POST['preOrderCheckbox']) && $_POST['preOrderCheckbox'] === 'on';
    $sameDayChecked = isset($_POST['sameDayCheckbox']) && $_POST['sameDayCheckbox'] === 'on';
    
    $status_id = null;
    $availtoday_status_id = null;
    
    if ($preOrderChecked && $sameDayChecked) {
        // Both pre-order and same-day: use pre-order status_id, set availtoday_status_id
        $status_id = isset($_POST['status_id']) ? intval($_POST['status_id']) : 1;
        $availtoday_status_id = isset($_POST['availtoday_status_id']) ? intval($_POST['availtoday_status_id']) : null;
    } elseif ($preOrderChecked) {
        // Only pre-order: use pre-order status_id
        $status_id = isset($_POST['status_id']) ? intval($_POST['status_id']) : 1;
    } elseif ($sameDayChecked) {
        // Only same-day: use status_id 4 (Same Day Order)
        $status_id = 4;
        $availtoday_status_id = isset($_POST['availtoday_status_id']) ? intval($_POST['availtoday_status_id']) : null;
        $quantity = 0; // Auto-set quantity to 0 for Same Day Order only
    } else {
        // Neither checked - validation should have caught this, but default to pre-order
        $status_id = 1;
    }
    
    // Handle is_featured - now a select dropdown
    $is_featured = isset($_POST['is_featured']) ? intval($_POST['is_featured']) : 0;
    
    // Map visibility select option to the two DB flags
    $visibility_option = $_POST['visibility_option'] ?? 'default';
    $show_when_unavailable = ($visibility_option === 'show') ? 1 : 0;
    $hide_when_unavailable = ($visibility_option === 'hide' || $visibility_option === 'default') ? 1 : 0;

    // Handle available days - get from global settings for status 1, 2, 3
    $available_days = [];
    if ($status_id == 1 || $status_id == 2 || $status_id == 3) {
        $available_days = getSetting('global_available_days', []);
    }
    
    // Handle Today's product dates
    $todays_product_dates = [];
    if (isset($_POST['todays_product_dates']) && !empty($_POST['todays_product_dates'])) {
        $todays_product_dates = explode(',', $_POST['todays_product_dates']);
        $todays_product_dates = array_filter($todays_product_dates); // Remove empty values
    }

    // Handle available today dates for regular products (when both checkboxes are checked)
    $available_today_dates = [];
    if ($preOrderChecked && $sameDayChecked && isset($_POST['available_today_dates']) && !empty($_POST['available_today_dates'])) {
        $available_today_dates = explode(',', $_POST['available_today_dates']);
        $available_today_dates = array_filter($available_today_dates);
    }

    // Insert product with availtoday_status_id and category_id fields
    // Note: Explicitly excluding 'id' from INSERT to ensure AUTO_INCREMENT works
    $stmt = $conn->prepare("INSERT INTO products (sku, name, description, price, status_id, quantity, is_featured, show_when_unavailable, hide_when_unavailable, availtoday_status_id, category_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiiiiiii", $sku, $name, $description, $price, $status_id, $quantity, $is_featured, $show_when_unavailable, $hide_when_unavailable, $availtoday_status_id, $category_id);
    
    // Debug: Check if the statement prepared correctly
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;

        // Handle Primary Image - Get from AJAX upload metadata
        $primaryImageUrl = $_POST['primary_image_url'] ?? '';
        $primaryImagePublicId = $_POST['primary_image_public_id'] ?? '';
        
        if (!empty($primaryImageUrl) && !empty($primaryImagePublicId)) {
            try {
                // Store primary image metadata in database (already uploaded via AJAX)
                $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, NULL, ?, ?, 'cloudinary', 1)");
                $stmt->bind_param("iss", $product_id, $primaryImageUrl, $primaryImagePublicId);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save primary image to database: " . $stmt->error);
                }
                
                // Remove from temp tracking (no longer orphaned)
                $conn->query("DELETE FROM temp_uploaded_images WHERE public_id = '$primaryImagePublicId'");
                
                error_log("add-product.php: Successfully saved primary image metadata for product $product_id");
            } catch (Exception $e) {
                // Rollback product creation on database failure
                $conn->query("DELETE FROM products WHERE id = $product_id");
                
                error_log("add-product.php: Failed to save primary image for product $product_id: " . $e->getMessage());
                
                $_SESSION['error_message'] = "Failed to save primary image: " . $e->getMessage();
                header("Location: /backend/pages/products/add-product.php");
                exit();
            }
        }

        // Handle Additional Images - Get from AJAX upload metadata
        $additionalImageUrls = $_POST['additional_image_urls'] ?? '';
        $additionalImagePublicIds = $_POST['additional_image_public_ids'] ?? '';
        
        if (!empty($additionalImageUrls) && !empty($additionalImagePublicIds)) {
            $urls = json_decode($additionalImageUrls, true);
            $publicIds = json_decode($additionalImagePublicIds, true);
            
            if (is_array($urls) && is_array($publicIds) && count($urls) === count($publicIds)) {
                $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, NULL, ?, ?, 'cloudinary', 0)");
                
                foreach ($urls as $index => $url) {
                    $publicId = $publicIds[$index];
                    
                    try {
                        $stmt->bind_param("iss", $product_id, $url, $publicId);
                        
                        if ($stmt->execute()) {
                            // Remove from temp tracking (no longer orphaned)
                            $conn->query("DELETE FROM temp_uploaded_images WHERE public_id = '$publicId'");
                            error_log("Successfully saved additional image " . ($index + 1) . " for product $product_id");
                        } else {
                            error_log("Failed to save additional image to database for product $product_id: " . $stmt->error);
                        }
                    } catch (Exception $e) {
                        error_log("Exception saving additional image for product $product_id: " . $e->getMessage());
                    }
                }
                
                $stmt->close();
            }
        }

        // Insert available days into product_day table for Pick Up, Delivery, and Delivery or Pick Up
        if (($status_id == 1 || $status_id == 2 || $status_id == 3) && !empty($available_days)) {
            $day_stmt = $conn->prepare("INSERT INTO product_day (product_id, day_of_week) VALUES (?, ?)");
            foreach ($available_days as $day) {
                $day_stmt->bind_param("is", $product_id, $day);
                $day_stmt->execute();
            }
            $day_stmt->close();
        }
        
        // Insert Today's product dates based on checkbox selections
        if ($sameDayChecked && !$preOrderChecked && !empty($todays_product_dates)) {
            // Only same-day checked: insert into todays_products_dates table
            $date_stmt = $conn->prepare("INSERT INTO todays_products_dates (product_id, available_date, availtoday_status_id) VALUES (?, ?, ?)");
            
            foreach ($todays_product_dates as $date) {
                $trimmed_date = trim($date);
                // Validate date format
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed_date)) {
                    $date_stmt->bind_param("isi", $product_id, $trimmed_date, $availtoday_status_id);
                    if (!$date_stmt->execute()) {
                        throw new Exception("Failed to insert date $trimmed_date: " . $date_stmt->error);
                    }
                }
            }
            $date_stmt->close();
        }

        // Insert available today dates for products with both pre-order and same-day
        if ($preOrderChecked && $sameDayChecked && !empty($available_today_dates)) {
            // Both checkboxes checked: insert into regular_products_today_dates table
            $regular_date_stmt = $conn->prepare("INSERT INTO regular_products_today_dates (product_id, available_date, availtoday_status_id) VALUES (?, ?, ?)");
            
            foreach ($available_today_dates as $date) {
                $trimmed_date = trim($date);
                // Validate date format
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed_date)) {
                    $regular_date_stmt->bind_param("isi", $product_id, $trimmed_date, $availtoday_status_id);
                    if (!$regular_date_stmt->execute()) {
                        error_log("Failed to insert regular product date $trimmed_date: " . $regular_date_stmt->error);
                    }
                }
            }
            $regular_date_stmt->close();
        }

        // Set success message in session
        $_SESSION['success_message'] = "Product has been added successfully!";
        
        // Log the activity
        logAdminActivity($conn, 'CREATE', "Added new product: $name (SKU: $sku)", 'products', $product_id);
        
        // Redirect to prevent form resubmission
        header("Location: /backend/pages/products/product-list.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
// Don't close connection here - navbar needs it
// $conn->close(); - Moved to end of file
?>

  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/backend/pages/products/add-product.css">
    <link rel="stylesheet" href="/backend/pages/products/css/product-image-ajax.css">
    <link rel="stylesheet" href="/backend/pages/products/css/moderation-overlay.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="components/date-calendar.js" defer></script>
    <script src="/backend/pages/products/js/product-image-ajax.js" defer></script>
    <style>
        .success-popup {
            display: none;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #86efac;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translate(-50%, 0);
            }
            to {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
<?php include __DIR__ . '/../admin-includes/breadcrumbs/admin-breadcrumb.php'; ?>

    <?php if(isset($_SESSION['success_message'])): ?>
    <div class="success-popup" id="successPopup">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($sessionData['error_message'])): ?>
    <div class="error-popup" id="errorPopup" style="background-color: #ef4444;">
        <?php 
        echo $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($sessionData['warning_message'])): ?>
    <div class="warning-popup" id="warningPopup" style="background-color: #f59e0b;">
        <?php 
        echo $_SESSION['warning_message'];
        unset($_SESSION['warning_message']);
        ?>
    </div>
    <?php endif; ?>
    <div class="mainContainer">
        <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <!-- Hidden fields for AJAX-uploaded image metadata -->
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" id="primary_image_url" name="primary_image_url" value="">
            <input type="hidden" id="primary_image_public_id" name="primary_image_public_id" value="">
            <input type="hidden" id="additional_image_urls" name="additional_image_urls" value="">
            <input type="hidden" id="additional_image_public_ids" name="additional_image_public_ids" value="">
            
            <div class="container">
                <div class="grp1">
                    <label>SKU:</label>
                    <input class="sku" type="text" name="sku" value="<?php echo htmlspecialchars($sku); ?>" readonly>

                    <label>Product Name:</label>
                    <input class="pname" type="text" name="name" required>

                    <label>Category:</label>
                    <select name="category_id" id="productCategory">
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

                    <div class="price-stock-container">
                        <div class="price-field">
                            <label>Price:</label>
                            <input class="price" type="text" name="price" required pattern="^\d*\.?\d*$" oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                        </div>

                        <div class="stock-field">
                            <label>Stocks:</label>
                            <input class="quantity" type="number" name="quantity" min="0" step="1" value="0" required>
                        </div>
                    </div>

                    <label>Description:</label>
                    <textarea class="description" name="description"></textarea>

                    <div class="image-upload-container">
                        <div class="inputs">
                            <div class="images-section">
                                <label class="main-img">Primary Image (1 Image Only):</label>
                                <div class="image-upload primary-image-upload">
                                    <input type="file" id="primaryImageInput" accept="image/*" style="display: none;">
                                    <label for="primaryImageInput" class="upload-btn add-img-btn" id="primaryUploadBtn">
                                        Click to Upload Image
                                    </label>
                                    <div class="primary-preview-container" id="primaryPreviewContainer"></div>
                                    <div class="loading-indicator" id="primaryLoadingIndicator">
                                        <i class="fas fa-spinner fa-spin"></i> Uploading...
                                    </div>
                                    <div class="success-indicator" id="primarySuccessIndicator">
                                        <i class="fas fa-check-circle"></i> Upload successful!
                                    </div>
                                </div>
                            </div>

                            <div class="images-section">
                                <label class="additional-img">Additional Images (Up to 3):</label>
                                <div class="image-upload additional-images-upload">
                                    <input type="file" id="additionalImagesInput" accept="image/*" multiple style="display: none;">
                                    <label for="additionalImagesInput" class="upload-btn add-img-btn" id="additionalUploadBtn">
                                        Click to Upload Additional Images (0/3)
                                    </label>
                                    <div class="additional-preview-container" id="additionalPreviewContainer"></div>
                                    <div class="loading-indicator" id="additionalLoadingIndicator">
                                        <i class="fas fa-spinner fa-spin"></i> Uploading...
                                    </div>
                                    <div class="success-indicator" id="additionalSuccessIndicator">
                                        <i class="fas fa-check-circle"></i> Upload successful!
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small>Supported files: .png, .jpg, .webp</small>

                    </div>
                </div>

                <div class="grp2">
                    <label>Order Types:</label>
                    
                    <!-- Pre-order Checkbox -->
                    <div class="checkbox-item" style="margin-top: 8px;">
                        <input type="checkbox" id="preOrderCheckbox" name="preOrderCheckbox">
                        <label for="preOrderCheckbox">Pre-order</label>
                    </div>
                    
                    <!-- Pre-order Dropdown (conditional) -->
                    <div id="preOrderOptions" style="display: none; margin-left: 24px; margin-top: 8px;">
                        <label for="preOrderStatus" style="font-size: 0.75rem; margin-bottom: 4px;">Pre-order Shipping Method:</label>
                        <select id="preOrderStatus" name="status_id">
                            <option value="1">Pick Up</option>
                            <option value="2">Delivery</option>
                            <option value="3">Delivery or Pick Up</option>
                        </select>
                    </div>
                    
                    <!-- Same-day Order Checkbox -->
                    <div class="checkbox-item" style="margin-top: 12px;">
                        <input type="checkbox" id="sameDayCheckbox" name="sameDayCheckbox">
                        <label for="sameDayCheckbox">Same-day order</label>
                    </div>
                    
                    <!-- Same-day Order Dropdown (conditional) -->
                    <div id="sameDayOptions" style="display: none; margin-left: 24px; margin-top: 8px;">
                        <label for="sameDayStatus" style="font-size: 0.75rem; margin-bottom: 4px;">Same-day Order Shipping Method:</label>
                        <select id="sameDayStatus" name="availtoday_status_id">
                            <option value="1">Pick Up</option>
                            <option value="2">Delivery</option>
                            <option value="3">Delivery and Pick Up</option>
                        </select>
                    </div>
                    
                    <!-- Hidden field to track isAvailableToday for backend compatibility -->
                    <input type="hidden" id="isAvailableToday" name="isAvailableToday" value="false">

                    <!-- Calendar for Same Day Order Products -->
                    <div id="todaysProductCalendarContainer" style="display: none;">
                        <label>Select dates for same day order:</label>
                        <div id="todaysProductCalendar"></div>
                        <input type="hidden" id="todaysProductDates" name="todays_product_dates">
                    </div>

                    <!-- Calendar for regular products that are also available today -->
                    <div id="availableTodayCalendarContainer" style="display: none;">
                        <label>Select dates for same day order:</label>
                        <div id="availableTodayCalendar"></div>
                        <input type="hidden" id="availableTodayDates" name="available_today_dates">
                    </div>

                    <label>Availability:</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="available" name="availability" value="available" checked>
                            <label for="available">Available</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="unavailable" name="availability" value="unavailable">
                            <label for="unavailable">Unavailable</label>
                        </div>
                    </div>

                    <label>Featured Product:</label>
                    <select name="is_featured" id="is_featured">
                        <option value="0">Not Featured</option>
                        <option value="1">Featured</option>
                    </select>

                    <label>Visibility When Unavailable:</label>
                    <select name="visibility_option" id="visibility_option">
                        <option value="default">Default (Hidden)</option>
                        <option value="show">Show When Unavailable</option>
                        <option value="hide" selected>Hide When Unavailable</option>
                    </select>

                    <div class="btn-changes">
                        <button class="discardBtn" type="button" onclick="if(confirm('Are you sure you want to discard changes?')) { window.location.href='product-list.php'; }">Discard</button>
                        <button class="submitBtn" type="submit">Add Product</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

<?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>

<script>

</script>

<script>
    // Show success, error, and warning popups if they exist
    document.addEventListener('DOMContentLoaded', function() {
        const successPopup = document.getElementById('successPopup');
        if (successPopup) {
            successPopup.style.display = 'block';
            setTimeout(() => {
                successPopup.style.animation = 'fadeOut 1s ease-out forwards';
                setTimeout(() => {
                    successPopup.style.display = 'none';
                }, 500);
            }, 3000);
        }
        
        const errorPopup = document.getElementById('errorPopup');
        if (errorPopup) {
            errorPopup.style.display = 'block';
            setTimeout(() => {
                errorPopup.style.animation = 'fadeOut 1s ease-out forwards';
                setTimeout(() => {
                    errorPopup.style.display = 'none';
                }, 500);
            }, 5000);
        }
        
        const warningPopup = document.getElementById('warningPopup');
        if (warningPopup) {
            warningPopup.style.display = 'block';
            setTimeout(() => {
                warningPopup.style.animation = 'fadeOut 1s ease-out forwards';
                setTimeout(() => {
                    warningPopup.style.display = 'none';
                }, 500);
            }, 5000);
        }

        // Global variables to track uploaded files
        let additionalImagesArray = [];

        // Checkbox event handlers for new UI
        function handlePreOrderCheckboxChange() {
            const checkbox = document.getElementById('preOrderCheckbox');
            const optionsDiv = document.getElementById('preOrderOptions');
            const dropdown = document.getElementById('preOrderStatus');
            const sameDayCheckbox = document.getElementById('sameDayCheckbox');
            
            if (checkbox.checked) {
                optionsDiv.style.display = 'block';
                // Set default value if not already set
                if (!dropdown.value) {
                    dropdown.value = '1'; // Default to Pick Up
                }
                
                // If same-day is also checked, switch to availableTodayCalendar
                if (sameDayCheckbox && sameDayCheckbox.checked) {
                    const todaysCalendarContainer = document.getElementById('todaysProductCalendarContainer');
                    const availableTodayCalendarContainer = document.getElementById('availableTodayCalendarContainer');
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
                    if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'block';
                    // Reinitialize calendar for the new state
                    initializeCalendars();
                }
            } else {
                optionsDiv.style.display = 'none';
                
                // If same-day is checked, switch to todaysProductCalendar
                if (sameDayCheckbox && sameDayCheckbox.checked) {
                    const todaysCalendarContainer = document.getElementById('todaysProductCalendarContainer');
                    const availableTodayCalendarContainer = document.getElementById('availableTodayCalendarContainer');
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'block';
                    if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'none';
                    // Reinitialize calendar for the new state
                    initializeCalendars();
                }
            }
            
            // Update quantity field state and hidden isAvailableToday field
            updateQuantityFieldState();
        }

        function handleSameDayCheckboxChange() {
            const checkbox = document.getElementById('sameDayCheckbox');
            const optionsDiv = document.getElementById('sameDayOptions');
            const dropdown = document.getElementById('sameDayStatus');
            const preOrderCheckbox = document.getElementById('preOrderCheckbox');
            
            // Determine which calendar to show based on whether pre-order is also checked
            const todaysCalendarContainer = document.getElementById('todaysProductCalendarContainer');
            const availableTodayCalendarContainer = document.getElementById('availableTodayCalendarContainer');
            
            if (checkbox.checked) {
                optionsDiv.style.display = 'block';
                
                // Show appropriate calendar based on pre-order checkbox state
                if (preOrderCheckbox && preOrderCheckbox.checked) {
                    // Both pre-order and same-day: use availableTodayCalendar
                    if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'block';
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
                } else {
                    // Only same-day: use todaysProductCalendar
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'block';
                    if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'none';
                }
                
                // Set default value if not already set
                if (!dropdown.value) {
                    dropdown.value = '1'; // Default to Pick Up
                }
                
                // Initialize calendar if needed
                initializeCalendars();
            } else {
                optionsDiv.style.display = 'none';
                if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
                if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'none';
            }
            
            // Update quantity field state and hidden isAvailableToday field
            updateQuantityFieldState();
        }

        function updateQuantityFieldState() {
            const preOrderChecked = document.getElementById('preOrderCheckbox').checked;
            const sameDayChecked = document.getElementById('sameDayCheckbox').checked;
            const quantityField = document.querySelector('input[name="quantity"]');
            const isAvailableTodayHidden = document.getElementById('isAvailableToday');
            
            // Update hidden field for backend compatibility
            if (isAvailableTodayHidden) {
                isAvailableTodayHidden.value = (preOrderChecked && sameDayChecked) ? 'true' : 'false';
            }
            
            // Disable quantity if only same-day is checked (not pre-order)
            if (sameDayChecked && !preOrderChecked) {
                quantityField.value = '0';
                quantityField.disabled = true;
                quantityField.style.opacity = '0.5';
                quantityField.style.cursor = 'not-allowed';
            } else {
                quantityField.disabled = false;
                quantityField.style.opacity = '1';
                quantityField.style.cursor = 'text';
            }
        }

        function initializeCalendars() {
            const preOrderChecked = document.getElementById('preOrderCheckbox').checked;
            const sameDayChecked = document.getElementById('sameDayCheckbox').checked;
            
            // Add a small delay to ensure DOM is ready
            setTimeout(() => {
                try {
                    if (sameDayChecked) {
                        if (preOrderChecked) {
                            // Both checked: initialize availableTodayCalendar
                            if (typeof DateCalendar !== 'undefined') {
                                // Destroy existing calendar if it exists
                                if (window.availableTodayCalendar && typeof window.availableTodayCalendar.destroy === 'function') {
                                    window.availableTodayCalendar.destroy();
                                }
                                window.availableTodayCalendar = new DateCalendar('availableTodayCalendar', {
                                    onSelectionChange: function(selectedDates) {
                                        const hiddenInput = document.getElementById('availableTodayDates');
                                        if (hiddenInput) {
                                            hiddenInput.value = selectedDates.join(',');
                                        }
                                    }
                                });
                            }
                        } else {
                            // Only same-day: initialize todaysProductCalendar
                            if (typeof DateCalendar !== 'undefined') {
                                // Destroy existing calendar if it exists
                                if (window.todaysProductCalendar && typeof window.todaysProductCalendar.destroy === 'function') {
                                    window.todaysProductCalendar.destroy();
                                }
                                window.todaysProductCalendar = new DateCalendar('todaysProductCalendar', {
                                    onSelectionChange: function(selectedDates) {
                                        const hiddenInput = document.getElementById('todaysProductDates');
                                        if (hiddenInput) {
                                            hiddenInput.value = selectedDates.join(',');
                                        }
                                    }
                                });
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error initializing calendar:', error);
                }
            }, 100);
        }

        // Function to toggle calendar visibility and quantity field based on status (LEGACY - kept for compatibility)
        function toggleAvailableDaysVisibility() {
            const statusSelect = document.querySelector('select[name="status_id"]');
            const todaysCalendarContainer = document.getElementById('todaysProductCalendarContainer');
            const availtodayOptions = document.getElementById('availtodayOptions');
            const availtodaySelect = document.querySelector('select[name="availtoday_status_id"]');
            const isAvailableTodayContainer = document.getElementById('isAvailableTodayContainer');
            const availableTodayCalendarContainer = document.getElementById('availableTodayCalendarContainer');
            const quantityField = document.querySelector('input[name="quantity"]');
            
            if (statusSelect) {
                const selectedValue = statusSelect.value;
                
                if (selectedValue === '1' || selectedValue === '2' || selectedValue === '3') { // Pick Up, Delivery, or Delivery or Pick Up
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
                    if (isAvailableTodayContainer) isAvailableTodayContainer.style.display = 'block';
                    
                    // Enable quantity field
                    if (quantityField) {
                        quantityField.disabled = false;
                        quantityField.style.opacity = '1';
                        quantityField.style.cursor = 'text';
                    }
                    
                    // Show availtoday options and calendar only if checkbox is checked
                    const isAvailableTodayCheckbox = document.getElementById('isAvailableToday');
                    if (isAvailableTodayCheckbox && isAvailableTodayCheckbox.checked) {
                        if (availtodayOptions) availtodayOptions.style.display = 'block';
                        if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'block';
                        if (availtodaySelect) {
                            availtodaySelect.setAttribute('required', 'required');
                        }
                        
                        // Initialize available today calendar if not already initialized
                        if (!window.availableTodayCalendar) {
                            initializeAvailableTodayCalendar();
                        }
                    } else {
                        if (availtodayOptions) availtodayOptions.style.display = 'none';
                        if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'none';
                        if (availtodaySelect) {
                            availtodaySelect.removeAttribute('required');
                        }
                    }
                } else if (selectedValue === '4') { // Same Day Order
                    // For Same Day Order: Show calendar and availtoday options, disable quantity
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'block';
                    if (availtodayOptions) availtodayOptions.style.display = 'block';
                    if (isAvailableTodayContainer) isAvailableTodayContainer.style.display = 'none';
                    if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'none';
                    if (availtodaySelect) {
                        availtodaySelect.setAttribute('required', 'required');
                    }
                    
                    // Disable and set quantity to 0 for Same Day Order
                    if (quantityField) {
                        quantityField.value = '0';
                        quantityField.disabled = true;
                        quantityField.style.opacity = '0.5';
                        quantityField.style.cursor = 'not-allowed';
                    }
                    
                    // Initialize calendar for Today's products

                    
                    // Always ensure calendar has proper callback, even if it exists
                    try {
                        // Try DateCalendar class first, fallback to initializeDateCalendar function
                        if (typeof DateCalendar !== 'undefined') {

                            window.todaysProductCalendar = new DateCalendar('todaysProductCalendar', {
                                onSelectionChange: function(selectedDates) {

                                    const hiddenInput = document.getElementById('todaysProductDates');
                                    if (hiddenInput) {
                                        hiddenInput.value = selectedDates.join(',');

                                    } else {

                                    }
                                }
                            });
                        } else if (typeof initializeDateCalendar !== 'undefined') {

                            window.todaysProductCalendar = initializeDateCalendar('todaysProductCalendar', {
                                onSelectionChange: function(selectedDates) {

                                    const hiddenInput = document.getElementById('todaysProductDates');
                                    if (hiddenInput) {
                                        hiddenInput.value = selectedDates.join(',');

                                    } else {

                                    }
                                }
                            });
                        } else {

                        }

                    } catch (error) {
                        console.error('Error creating calendar:', error);
                    }
                } else {
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
                    if (availtodayOptions) availtodayOptions.style.display = 'none';
                    if (isAvailableTodayContainer) isAvailableTodayContainer.style.display = 'none';
                    if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'none';
                    if (availtodaySelect) {
                        availtodaySelect.removeAttribute('required');
                    }
                    
                    // Enable quantity field for other statuses
                    if (quantityField) {
                        quantityField.disabled = false;
                        quantityField.style.opacity = '1';
                        quantityField.style.cursor = 'text';
                    }
                }
            }
        }

        // Function to initialize Available Today calendar for regular products
        function initializeAvailableTodayCalendar() {
            try {
                if (typeof DateCalendar !== 'undefined') {
                    window.availableTodayCalendar = new DateCalendar('availableTodayCalendar', {
                        onSelectionChange: function(selectedDates) {
                            const hiddenInput = document.getElementById('availableTodayDates');
                            if (hiddenInput) {
                                hiddenInput.value = selectedDates.join(',');
                            }
                        }
                    });
                } else if (typeof initializeDateCalendar !== 'undefined') {
                    window.availableTodayCalendar = initializeDateCalendar('availableTodayCalendar', {
                        onSelectionChange: function(selectedDates) {
                            const hiddenInput = document.getElementById('availableTodayDates');
                            if (hiddenInput) {
                                hiddenInput.value = selectedDates.join(',');
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error creating available today calendar:', error);
            }
        }

        // Initialize quantity field state on page load
        updateQuantityFieldState();
        
        // Add event listeners to checkboxes
        const preOrderCheckbox = document.getElementById('preOrderCheckbox');
        if (preOrderCheckbox) {
            preOrderCheckbox.addEventListener('change', handlePreOrderCheckboxChange);
        }
        
        const sameDayCheckbox = document.getElementById('sameDayCheckbox');
        if (sameDayCheckbox) {
            sameDayCheckbox.addEventListener('change', handleSameDayCheckboxChange);
        }

        // Image upload handling is now managed by product-image-ajax.js
        // Old inline event listeners removed to prevent conflicts with AJAX upload

        function validateForm() {
            const preOrderChecked = document.getElementById('preOrderCheckbox').checked;
            const sameDayChecked = document.getElementById('sameDayCheckbox').checked;
            const todaysProductDates = document.getElementById('todaysProductDates');
            const availableTodayDates = document.getElementById('availableTodayDates');
            const sameDaySelect = document.getElementById('sameDayStatus');

            // Validate that at least one order type is selected
            if (!preOrderChecked && !sameDayChecked) {
                alert('Please select at least one order type (Pre-order or Same-day order).');
                return false;
            }

            // If same-day is checked, validate same-day specific fields
            if (sameDayChecked) {
                if (!sameDaySelect || !sameDaySelect.value) {
                    alert('Please select a "Same-day Order Shipping Method".');
                    return false;
                }
                
                // Check which calendar should have dates based on pre-order checkbox
                if (preOrderChecked) {
                    // Both pre-order and same-day: check availableTodayDates
                    if (!availableTodayDates || !availableTodayDates.value.trim()) {
                        alert('Please select at least one date for Same-day Order.');
                        return false;
                    }
                } else {
                    // Only same-day: check todaysProductDates
                    if (!todaysProductDates || !todaysProductDates.value.trim()) {
                        alert('Please select at least one date for Same-day Order.');
                        return false;
                    }
                }
            }

            return true;
        }
    });
</script>
</body>
</html>
<?php
// Close database connection at the end
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
