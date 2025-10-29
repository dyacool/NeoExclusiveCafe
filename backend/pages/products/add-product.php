<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../auth/login-signup.php");
    exit();
}

// Include config file for base URL
require_once __DIR__ . "/../admin-includes/config.php";

include __DIR__ . "/../admin-includes/database.php";
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
    $status_id = $_POST['status_id'];
    $quantity = $_POST['quantity'];
    
    // Auto-set quantity to 0 if status is Same Day Order (status_id 4)
    if ($status_id == 4) {
        $quantity = 0;
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

    // Handle availtoday_status_id for Same Day Order products and regular products set as available today
    $availtoday_status_id = null;
    $isAvailableToday = isset($_POST['isAvailableToday']) && $_POST['isAvailableToday'] === 'true';
    
    if ($status_id == 4 && isset($_POST['availtoday_status_id']) && !empty($_POST['availtoday_status_id'])) {
        // Same Day Order product
        $availtoday_status_id = $_POST['availtoday_status_id'];
    } elseif (($status_id == 1 || $status_id == 2 || $status_id == 3) && $isAvailableToday && isset($_POST['availtoday_status_id']) && !empty($_POST['availtoday_status_id'])) {
        // Regular product (Pick Up/Delivery/Delivery or Pick Up) also set as available today
        $availtoday_status_id = $_POST['availtoday_status_id'];
    }
    
    // Handle available today dates for regular products
    $available_today_dates = [];
    if (($status_id == 1 || $status_id == 2 || $status_id == 3) && $isAvailableToday && isset($_POST['available_today_dates']) && !empty($_POST['available_today_dates'])) {
        $available_today_dates = explode(',', $_POST['available_today_dates']);
        $available_today_dates = array_filter($available_today_dates);
    }

    // Insert product with availtoday_status_id field
    // Note: Explicitly excluding 'id' from INSERT to ensure AUTO_INCREMENT works
    $stmt = $conn->prepare("INSERT INTO products (sku, name, description, price, status_id, quantity, is_featured, show_when_unavailable, hide_when_unavailable, availtoday_status_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiiiiii", $sku, $name, $description, $price, $status_id, $quantity, $is_featured, $show_when_unavailable, $hide_when_unavailable, $availtoday_status_id);
    
    // Debug: Check if the statement prepared correctly
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;

        // Handle Primary Image Upload - Direct to Cloudinary (no local storage)
        if (!empty($_FILES['primary_image']['name'])) {
            // Validate image file
            $validation = validateUploadedImage($_FILES['primary_image']);
            
            if (!$validation['valid']) {
                // Rollback product creation on validation failure
                $conn->query("DELETE FROM products WHERE id = $product_id");
                $_SESSION['error_message'] = "Primary image validation failed: " . $validation['error'];
                header("Location: /backend/pages/products/add-product.php");
                exit();
            }
            
            // Upload directly to Cloudinary
            require_once __DIR__ . '/../../includes/cloudinary-helper.php';
            
            // Generate sanitized public ID
            $sanitizedName = sanitizeFilenameForCloudinary($_FILES['primary_image']['name']);
            $publicId = 'product_' . $product_id . '_primary_' . time();
            
            try {
                error_log("add-product.php: Attempting Cloudinary upload for product $product_id with public_id: $publicId");
                error_log("add-product.php: Temp file: " . $_FILES['primary_image']['tmp_name'] . " exists: " . (file_exists($_FILES['primary_image']['tmp_name']) ? 'YES' : 'NO'));
                
                $cloudinaryResult = uploadToCloudinary(
                    $_FILES['primary_image']['tmp_name'], 
                    'neocafe/products', 
                    $publicId
                );
                
                error_log("add-product.php: Cloudinary result: " . json_encode($cloudinaryResult));
                
                if ($cloudinaryResult['success']) {
                    // Store Cloudinary URL in database
                    $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, NULL, ?, ?, 'cloudinary', 1)");
                    $stmt->bind_param("iss", $product_id, $cloudinaryResult['url'], $cloudinaryResult['public_id']);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to save image to database: " . $stmt->error);
                    }
                    
                    // Delete temporary file
                    @unlink($_FILES['primary_image']['tmp_name']);
                    
                    error_log("add-product.php: Successfully uploaded primary image to Cloudinary for product $product_id");
                } else {
                    $errorMsg = $cloudinaryResult['error'] ?? 'Unknown error';
                    $errorDetails = $cloudinaryResult['error_details'] ?? '';
                    error_log("add-product.php: Cloudinary upload returned failure - Error: $errorMsg, Details: $errorDetails");
                    throw new Exception($errorMsg);
                }
            } catch (Exception $e) {
                // Rollback product creation on upload failure
                $conn->query("DELETE FROM products WHERE id = $product_id");
                
                error_log("add-product.php: Cloudinary upload exception for product $product_id: " . $e->getMessage());
                error_log("add-product.php: Exception trace: " . $e->getTraceAsString());
                
                $_SESSION['error_message'] = "Failed to upload primary image to Cloudinary: " . $e->getMessage();
                header("Location: /backend/pages/products/add-product.php");
                exit();
            }
        }

        // Handle Additional Images Upload - Direct to Cloudinary (max 3 images)
        if (!empty($_FILES['additional_images']['name'][0])) {
            require_once __DIR__ . '/../../includes/cloudinary-helper.php';
            
            $uploadedCount = 0;
            $failedUploads = [];
            $maxAdditionalImages = 3;
            
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                // Stop if we've reached the maximum
                if ($uploadedCount >= $maxAdditionalImages) {
                    error_log("Maximum of $maxAdditionalImages additional images reached for product $product_id");
                    break;
                }
                
                // Skip empty uploads
                if (empty($tmp_name) || empty($_FILES['additional_images']['name'][$key])) {
                    continue;
                }
                
                // Create file array for validation
                $fileArray = [
                    'name' => $_FILES['additional_images']['name'][$key],
                    'type' => $_FILES['additional_images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['additional_images']['error'][$key],
                    'size' => $_FILES['additional_images']['size'][$key]
                ];
                
                // Validate image file
                $validation = validateUploadedImage($fileArray);
                
                if (!$validation['valid']) {
                    $failedUploads[] = "Image " . ($key + 1) . ": " . $validation['error'];
                    error_log("Additional image validation failed for product $product_id: " . $validation['error']);
                    continue;
                }
                
                // Generate sanitized public ID
                $sanitizedName = sanitizeFilenameForCloudinary($_FILES['additional_images']['name'][$key]);
                $publicId = 'product_' . $product_id . '_additional_' . ($uploadedCount + 1) . '_' . time();
                
                try {
                    $cloudinaryResult = uploadToCloudinary(
                        $tmp_name, 
                        'neocafe/products', 
                        $publicId
                    );
                    
                    if ($cloudinaryResult['success']) {
                        // Store Cloudinary URL in database
                        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, cloud_url, cloud_public_id, cloud_provider, is_primary) VALUES (?, NULL, ?, ?, 'cloudinary', 0)");
                        $stmt->bind_param("iss", $product_id, $cloudinaryResult['url'], $cloudinaryResult['public_id']);
                        
                        if ($stmt->execute()) {
                            $uploadedCount++;
                            error_log("Successfully uploaded additional image $uploadedCount to Cloudinary for product $product_id");
                        } else {
                            $failedUploads[] = "Image " . ($key + 1) . ": Database save failed";
                            error_log("Failed to save additional image to database for product $product_id: " . $stmt->error);
                        }
                        
                        // Delete temporary file
                        @unlink($tmp_name);
                    } else {
                        $failedUploads[] = "Image " . ($key + 1) . ": " . $cloudinaryResult['error'];
                        error_log("Cloudinary upload failed for additional image (product $product_id): " . $cloudinaryResult['error']);
                    }
                } catch (Exception $e) {
                    $failedUploads[] = "Image " . ($key + 1) . ": " . $e->getMessage();
                    error_log("Exception during additional image upload for product $product_id: " . $e->getMessage());
                }
            }
            
            // Log partial upload failures (but don't block product creation)
            if (!empty($failedUploads)) {
                error_log("Some additional images failed to upload for product $product_id: " . implode(", ", $failedUploads));
                $_SESSION['warning_message'] = "Product created, but some additional images failed to upload: " . implode(", ", $failedUploads);
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
        
        // Insert Today's product dates into todays_products_dates table (for Same Day Order - status_id 4)
        if ($status_id == 4 && !empty($todays_product_dates)) {
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

        // Insert available today dates for regular products into regular_products_today_dates table
        if (($status_id == 1 || $status_id == 2 || $status_id == 3) && !empty($available_today_dates)) {
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
    <script src="components/date-calendar.js" defer></script>
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

<div class="breadcrumb">
    <a href="/backend/pages/products/product-list.php">Products</a>
    <span class="separator">></span>
    <span class="current">Add Product</span>
</div>

    <?php if(isset($_SESSION['success_message'])): ?>
    <div class="success-popup" id="successPopup">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
    <div class="error-popup" id="errorPopup" style="background-color: #ef4444;">
        <?php 
        echo $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['warning_message'])): ?>
    <div class="warning-popup" id="warningPopup" style="background-color: #f59e0b;">
        <?php 
        echo $_SESSION['warning_message'];
        unset($_SESSION['warning_message']);
        ?>
    </div>
    <?php endif; ?>
    <div class="mainContainer">
        <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="container">
                <div class="grp1">
                    <label>SKU:</label>
                    <input class="sku" type="text" name="sku" value="<?php echo htmlspecialchars($sku); ?>" readonly>

                    <label>Product Name:</label>
                    <input class="pname" type="text" name="name" required>

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
                                    <input type="file" name="primary_image" id="primaryImageInput" accept="image/*" required style="display: none;">
                                    <label for="primaryImageInput" class="upload-btn add-img-btn" id="primaryUploadBtn">
                                        Click to Upload Image
                                    </label>
                                    <div class="primary-preview-container" id="primaryPreviewContainer"></div>
                                </div>
                            </div>

                            <div class="images-section">
                                <label class="additional-img">Additional Images (Up to 3):</label>
                                <div class="image-upload additional-images-upload">
                                    <input type="file" name="additional_images[]" id="additionalImagesInput" accept="image/*" multiple style="display: none;">
                                    <label for="additionalImagesInput" class="upload-btn add-img-btn" id="additionalUploadBtn">
                                        Click to Upload Image
                                    </label>
                                    <div class="additional-preview-container" id="additionalPreviewContainer"></div>
                                </div>
                            </div>
                        </div>
                        <small>Supported files: .png, .jpg, .webp</small>

                    </div>
                </div>

                <div class="grp2">
                    <label>Shipping Method:</label>
                     <select class="statusGrp" name="status_id" id="statusSelect">
                         <option value="1">Pick Up</option>
                         <option value="2">Delivery</option>
                         <option value="3">Delivery or Pick Up</option>
                         <option value="4">Same Day Order</option>
                     </select>
                     

                     <!-- isAvailableToday radio button - only shown when Pick Up or Delivery is selected -->
                     <div id="isAvailableTodayContainer" style="display: none; margin-top: 10px;">
                         <div class="radio-group">
                             <div class="radio-item">
                                 <input type="radio" id="isAvailableToday" name="isAvailableToday" value="true">
                                 <label for="isAvailableToday">Set to same day order too</label>
                             </div>
                         </div>
                     </div>

                     <!-- Same Day Order Shipping Method dropdown -->
                     <div id="availtodayOptions" style="display: none; margin-top: 10px;">
                         <label>Same Day Order Shipping Method:</label>
                         <select class="availtoday-status" name="availtoday_status_id" id="availtodayStatusSelect">
                             <option value="1">Pick Up</option>
                             <option value="2">Delivery</option>
                             <option value="3">Delivery and Pick Up</option>
                         </select>
                     </div>

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

        // Function to toggle calendar visibility and quantity field based on status
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

        // Initialize available days visibility based on initial status
        toggleAvailableDaysVisibility();

        // Add event listener to status dropdown
        const statusSelect = document.querySelector('select[name="status_id"]');
        if (statusSelect) {
            statusSelect.addEventListener('change', toggleAvailableDaysVisibility);
        }

        // Add event listener to "Set to same day order too" checkbox
        const isAvailableTodayCheckbox = document.getElementById('isAvailableToday');
        if (isAvailableTodayCheckbox) {
            isAvailableTodayCheckbox.addEventListener('change', toggleAvailableDaysVisibility);
        }

        // Primary image handling
        document.getElementById('primaryImageInput').addEventListener('change', function(event) {
            const previewContainer = document.getElementById('primaryPreviewContainer');
            const uploadBtn = document.getElementById('primaryUploadBtn');
            
            // Clear existing preview
            previewContainer.innerHTML = '';
            
            if (this.files && this.files[0]) {
                // Hide the upload button when an image is selected
                uploadBtn.style.display = 'none';
                
                // Create preview
                const previewItem = document.createElement('div');
                previewItem.className = 'primary-preview-item';
                
                const img = document.createElement('img');
                img.src = URL.createObjectURL(this.files[0]);
                img.alt = 'Primary image preview';
                
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '×';
                removeBtn.onclick = function(e) {
                    e.preventDefault();
                    document.getElementById('primaryImageInput').value = '';
                    previewContainer.innerHTML = '';
                    uploadBtn.style.display = 'flex';
                };
                
                previewItem.appendChild(img);
                previewItem.appendChild(removeBtn);
                previewContainer.appendChild(previewItem);
                
                // Make preview container visible and take up full space
                previewContainer.style.display = 'flex';
            }
        });

        // Additional images handling - modified to allow multiple selection at once
        document.getElementById('additionalImagesInput').addEventListener('change', function(event) {
            if (this.files && this.files.length > 0) {
                // Process each file
                for (let i = 0; i < this.files.length; i++) {
                    // Check if we already have 3 images
                    if (additionalImagesArray.length >= 3) {
                        alert('You can only upload up to 3 additional images.');
                        break;
                    }
                    
                    // Add file to our array
                    additionalImagesArray.push(this.files[i]);
                }
                
                // Clear the input so we can select the same file again if needed
                this.value = '';
                
                // Update the form file input with our array of files
                updateFormFileInput();
                
                // Update the preview
                updateAdditionalImagesPreview();
            }
        });

        // Function to update the form file input with our array of files
        function updateFormFileInput() {
            const input = document.getElementById('additionalImagesInput');
            const dataTransfer = new DataTransfer();
            
            // Add all files from our array to the DataTransfer object
            additionalImagesArray.forEach(file => {
                dataTransfer.items.add(file);
            });
            
            // Update the file input
            input.files = dataTransfer.files;
        }

        // Update the additional images preview
        function updateAdditionalImagesPreview() {
            const previewContainer = document.getElementById('additionalPreviewContainer');
            const uploadBtn = document.getElementById('additionalUploadBtn');
            
            // Clear existing preview
            previewContainer.innerHTML = '';
            
            // Toggle active class based on whether there are images
            if (additionalImagesArray.length > 0) {
                previewContainer.classList.add('active');
            } else {
                previewContainer.classList.remove('active');
            }
            
            // Create preview for each file in our array
            additionalImagesArray.forEach((file, index) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'image-preview-item';
                
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = `Additional image ${index + 1}`;
                
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '×';
                removeBtn.setAttribute('data-index', index);
                removeBtn.onclick = function(e) {
                    e.preventDefault();
                    
                    // Remove this file from our array
                    additionalImagesArray.splice(parseInt(this.getAttribute('data-index')), 1);
                    
                    // Update the form file input
                    updateFormFileInput();
                    
                    // Update preview
                    updateAdditionalImagesPreview();
                };
                
                previewItem.appendChild(img);
                previewItem.appendChild(removeBtn);
                previewContainer.appendChild(previewItem);
            });
            
            // Hide upload button only when we have 3 images (maximum)
            uploadBtn.style.display = additionalImagesArray.length >= 3 ? 'none' : 'flex';
        }

        function validateForm() {
            const statusSelect = document.getElementById('statusSelect');
            const todaysProductDates = document.getElementById('todaysProductDates');
            const availtodaySelect = document.querySelector('select[name="availtoday_status_id"]');
            const isAvailableTodayCheckbox = document.getElementById('isAvailableToday');
            const availableTodayDates = document.getElementById('availableTodayDates');

            // For Same Day Order products (status_id 4), ensure both date and availtoday status are selected
            if (statusSelect.value === '4') {
                if (!availtodaySelect || !availtodaySelect.value) {
                    alert('Please select a "Same Day Order Options".');
                    return false;
                }
                if (!todaysProductDates || !todaysProductDates.value.trim()) {
                    alert('Please select at least one date for Same Day Order.');
                    return false;
                }
            }

            // For regular products (Pick Up/Delivery/Delivery or Pick Up) set as available today
            if ((statusSelect.value === '1' || statusSelect.value === '2' || statusSelect.value === '3') && isAvailableTodayCheckbox && isAvailableTodayCheckbox.checked) {
                if (!availtodaySelect || !availtodaySelect.value) {
                    alert('Please select a "Same Day Order Options".');
                    return false;
                }
                if (!availableTodayDates || !availableTodayDates.value.trim()) {
                    alert('Please select at least one date for Same Day Order.');
                    return false;
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
