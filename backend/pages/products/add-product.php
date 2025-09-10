<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../auth/login-signup.php");
    exit();
}

// Include config file for base URL
require_once __DIR__ . "/../admin-includes/config.php";

$conn = new mysqli("mysql-neoexclusivecafe.alwaysdata.net", "429123", "NeoCafe123", "neoexclusivecafe_crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to generate SKU **only when inserting a new product**
function generateSKU($conn) {
    $prefix = "SD-";

    // Fetch the last SKU
    $result = $conn->query("SELECT sku FROM products ORDER BY id DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_sku = $row['sku'];
        
        // Extract number part and increment
        $number = (int)substr($last_sku, 3) + 1;
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    } else {
        return $prefix . "00001"; // First product starts at SD-00001
    }
}

$sku = generateSKU($conn); // Generate SKU when the page loads

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $sku = generateSKU($conn);
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $status_id = $_POST['status_id'];
    $quantity = $_POST['quantity'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    // Map visibility radio selection to the two DB flags
    $visibility_option = $_POST['visibility_option'] ?? 'hide';
    $show_when_unavailable = ($visibility_option === 'show') ? 1 : 0;
    $hide_when_unavailable = ($visibility_option === 'hide') ? 1 : 0;

    // Handle available days - process for both Delivery and Pick Up
    $available_days = [];
    if (isset($_POST['available_days']) && is_array($_POST['available_days'])) {
        $available_days = $_POST['available_days'];
    }
    
    // Handle Today's product dates
    $todays_product_dates = [];
    if (isset($_POST['todays_product_dates']) && !empty($_POST['todays_product_dates'])) {
        $todays_product_dates = explode(',', $_POST['todays_product_dates']);
        $todays_product_dates = array_filter($todays_product_dates); // Remove empty values
    }

    // Handle availtoday_status_id for Available Today products
    $availtoday_status_id = null;
    if ($status_id == 3 && isset($_POST['availtoday_status_id']) && !empty($_POST['availtoday_status_id'])) {
        $availtoday_status_id = $_POST['availtoday_status_id'];
    }

    // Insert product with availtoday_status_id field
    $stmt = $conn->prepare("INSERT INTO products (sku, name, description, price, status_id, quantity, is_featured, show_when_unavailable, hide_when_unavailable, availtoday_status_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiiiiii", $sku, $name, $description, $price, $status_id, $quantity, $is_featured, $show_when_unavailable, $hide_when_unavailable, $availtoday_status_id);
    
    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;

        // Create product folder with unique timestamp to avoid conflicts
        $timestamp = time();
        $cleanProductName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $name);
        $folderName = $cleanProductName . '_' . $timestamp;
        $productFolder = __DIR__ . "/../../../assets/product-images/" . $folderName . "/";
        
        if (!file_exists($productFolder)) {
            mkdir($productFolder, 0777, true);
        }

        // Handle Primary Image Upload
        if (!empty($_FILES['primary_image']['name'])) {
            $fileName = basename($_FILES['primary_image']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Only allow certain image file formats
            $allowedTypes = array('jpg', 'jpeg', 'png', 'webp', 'jfif');
            if (in_array($fileExt, $allowedTypes)) {
                // Create a clean, web-safe filename
                $cleanFileName = 'primary_' . $timestamp . '.' . $fileExt;
                $filePath = $productFolder . $cleanFileName;
                
                if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $filePath)) {
                    // Store relative path in database without special characters
                    $dbImagePath = "product-images/" . $folderName . "/" . $cleanFileName;
                    $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 1)");
                    $stmt->bind_param("is", $product_id, $dbImagePath);
                    $stmt->execute();
                }
            }
        }

        // Handle Additional Images Upload
        if (!empty($_FILES['additional_images']['name'][0])) {
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                $fileName = basename($_FILES['additional_images']['name'][$key]);
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Only allow certain image file formats
                $allowedTypes = array('jpg', 'jpeg', 'png', 'webp', 'jfif');
                if (in_array($fileExt, $allowedTypes)) {
                    // Create a clean, web-safe filename
                    $cleanFileName = 'additional_' . $timestamp . '_' . ($key + 1) . '.' . $fileExt;
                    $filePath = $productFolder . $cleanFileName;
                    
                    if (move_uploaded_file($tmp_name, $filePath)) {
                        // Store relative path in database without special characters
                        $dbImagePath = "product-images/" . $folderName . "/" . $cleanFileName;
                        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 0)");
                        $stmt->bind_param("is", $product_id, $dbImagePath);
                        $stmt->execute();
                    }
                }
            }
        }

        // Insert available days into product_day table for Delivery and Pick Up
        if (($status_id == 1 || $status_id == 2) && !empty($available_days)) {
            $day_stmt = $conn->prepare("INSERT INTO product_day (product_id, day_of_week) VALUES (?, ?)");
            foreach ($available_days as $day) {
                $day_stmt->bind_param("is", $product_id, $day);
                $day_stmt->execute();
            }
            $day_stmt->close();
        }
        
        // Insert Today's product dates into todays_products_dates table
        if ($status_id == 3 && !empty($todays_product_dates)) {
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

        // Set success message in session
        $_SESSION['success_message'] = "Product has been added successfully!";
        
        // Redirect to prevent form resubmission
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
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
            background-color: #4CAF50;
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
    <div class="mainContainer">
        <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="container">
                <div class="grp1">
                    <label>SKU:</label>
                    <input class="sku" type="text" name="sku" value="<?php echo htmlspecialchars($sku); ?>" readonly>

                    <label>Product Name:</label>
                    <input class="pname" type="text" name="name" required>

                    <label>Description:</label>
                    <textarea class="description" name="description"></textarea>

                    <div class="image-upload-container">
                        <div class="labels">
                            <label class="main-img">Primary Image (1 Image Only):</label>
                            <label class="additional-img">Additional Images (Up to 3):</label>
                        </div>

                        <div class="inputs">
                            <div class="image-upload primary-image-upload">
                                <input type="file" name="primary_image" id="primaryImageInput" accept="image/*" required style="display: none;">
                                <label for="primaryImageInput" class="upload-btn" id="primaryUploadBtn">
                                    Click to Upload Image
                                </label>
                                <div class="primary-preview-container" id="primaryPreviewContainer"></div>
                            </div>

                            <div class="image-upload additional-images-upload">
                                <input type="file" name="additional_images[]" id="additionalImagesInput" accept="image/*" multiple style="display: none;">
                                <label for="additionalImagesInput" class="upload-btn add-img-btn" id="additionalUploadBtn">
                                    Click to Upload Image
                                </label>
                                <div class="additional-preview-container" id="additionalPreviewContainer"></div>
                            </div>
                        </div>
                        <small>Supported files: .png, .jpg, .webp</small>

                    </div>
                </div>

                <div class="grp2">
                    <label>Price:</label>
                    <input class="price" type="text" name="price" required pattern="^\d*\.?\d*$" oninput="this.value = this.value.replace(/[^0-9.]/g, '')">

                                         <label>Status:</label>
                     <select class="statusGrp" name="status_id" id="statusSelect">
                         <option value="1">Pick Up</option>
                         <option value="2">Delivery</option>
                         <option value="3">Same Day Order</option>
                     </select>

                     <!-- New dropdown for Available Today options -->
                     <div id="availtodayOptions" style="display: none;">
                         <label>Delivery Option:</label>
                         <select class="availtoday-status" name="availtoday_status_id">
                             <option value="">Select option...</option>
                             <option value="1">Pick Up</option>
                             <option value="2">Delivery</option>
                         </select>
                     </div>

                    <!-- Added Quantity Available For Pre-Order field -->
                    <label>Stocks:</label>
                    <input class="quantity" type="number" name="quantity" min="0" step="1" value="0" required>

                    <!-- Available Days for regular products (Pick Up/Delivery) -->
                    <div id="regularAvailableDaysContainer">
                        <label>Available Days:</label>
                        <div class="checkbox-group days-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="available_days[]" id="sunday" value="Sunday">
                                <label class="cb-itm" for="sunday" style="display: inline;">Sunday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="available_days[]" id="monday" value="Monday">
                                <label class="cb-itm" for="monday" style="display: inline;">Monday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="available_days[]" id="tuesday" value="Tuesday">
                                <label class="cb-itm" for="tuesday" style="display: inline;">Tuesday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="available_days[]" id="wednesday" value="Wednesday">
                                <label class="cb-itm" for="wednesday" style="display: inline;">Wednesday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="available_days[]" id="thursday" value="Thursday">
                                <label class="cb-itm" for="thursday" style="display: inline;">Thursday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="available_days[]" id="friday" value="Friday">
                                <label class="cb-itm" for="friday" style="display: inline;">Friday</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="available_days[]" id="saturday" value="Saturday">
                                <label class="cb-itm" for="saturday" style="display: inline;">Saturday</label>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar for Today's Products -->
                    <div id="todaysProductCalendarContainer" style="display: none;">
                        <label>Select Available Dates:</label>
                        <div id="todaysProductCalendar"></div>
                        <input type="hidden" id="todaysProductDates" name="todays_product_dates">
                    </div>

                    <label>Visibility</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="radio" name="visibility_option" id="visibility_show" value="show">
                            <label class="cb-itm" for="visibility_show" style="display: inline;">Show when unavailable</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="radio" name="visibility_option" id="visibility_hide" value="hide" checked>
                            <label class="cb-itm" for="visibility_hide" style="display: inline;">Hide when unavailable</label>
                        </div>
                    </div>

                    <label>Featured</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="is_featured" id="is_featured">
                            <label class="cb-itm" for="is_featured" style="display: inline;">Feature Product</label>
                        </div>
                    </div>

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
    // Show success popup if it exists
    document.addEventListener('DOMContentLoaded', function() {
        const successPopup = document.getElementById('successPopup');
        if (successPopup) {
            successPopup.style.display = 'block';
            setTimeout(() => {
                successPopup.style.animation = 'fadeOut 1s ease-out forwards';
                setTimeout(() => {
                    successPopup.style.display = 'none';
                }, 500);
            }, 500);
        }

        // Global variables to track uploaded files
        let additionalImagesArray = [];

        // Function to toggle available days/calendar visibility based on status
        function toggleAvailableDaysVisibility() {

            const statusSelect = document.querySelector('select[name="status_id"]');
            const regularDaysContainer = document.getElementById('regularAvailableDaysContainer');
            const todaysCalendarContainer = document.getElementById('todaysProductCalendarContainer');
            const availtodayOptions = document.getElementById('availtodayOptions');
            const availtodaySelect = document.querySelector('select[name="availtoday_status_id"]');
            
            if (statusSelect) {
                const selectedValue = statusSelect.value;

                
                if (selectedValue === '1' || selectedValue === '2') { // Pick Up or Delivery
                    if (regularDaysContainer) regularDaysContainer.style.display = 'block';
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
                    if (availtodayOptions) availtodayOptions.style.display = 'none';
                    if (availtodaySelect) {
                        availtodaySelect.removeAttribute('required');
                    }
                } else if (selectedValue === '3') { // Today's Product
                    // For Today's Product: Show calendar and availtoday options
                    if (regularDaysContainer) regularDaysContainer.style.display = 'none';
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'block';
                    if (availtodayOptions) availtodayOptions.style.display = 'block'; // Show availtoday options
                    if (availtodaySelect) {
                        availtodaySelect.setAttribute('required', 'required'); // Keep required
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
                    if (regularDaysContainer) regularDaysContainer.style.display = 'none';
                    if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
                    if (availtodayOptions) availtodayOptions.style.display = 'none';
                    if (availtodaySelect) {
                        availtodaySelect.removeAttribute('required');
                    }
                }
            }
        }

        // Initialize available days visibility based on initial status
        toggleAvailableDaysVisibility();

        // Add event listener to status dropdown
        const statusSelect = document.querySelector('select[name="status_id"]');
        if (statusSelect) {
            statusSelect.addEventListener('change', toggleAvailableDaysVisibility);
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



            // For Today's products, ensure both date and availtoday status are selected
            if (statusSelect.value === '3') {
                if (!availtodaySelect || !availtodaySelect.value) {
                    alert('Please select an option for "Available Today".');
                    return false;
                }
                if (!todaysProductDates || !todaysProductDates.value.trim()) {
                    alert('Please select at least one date for Today\'s product.');
                    return false;
                }
            }
            return true;
        }
    });
</script>
</body>
</html>
