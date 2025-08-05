<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../auth/login-signup.php");
    exit();
}

// Include config file for base URL
require_once __DIR__ . "/../admin-includes/config.php";

$conn = new mysqli("localhost", "root", "", "crud");
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
    $quantity = $_POST['quantity']; // Added this line to get quantity
    // Handle days_to_make - allow empty/null values since column is DEFAULT NULL
    $days_to_make = null;
    if (isset($_POST['days_to_make']) && $_POST['days_to_make'] !== '') {
        $days_to_make = filter_var($_POST['days_to_make'], FILTER_VALIDATE_INT);
        if ($days_to_make === false) {
            echo "Error: Invalid days to make value";
            exit();
        }
    }
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $show_when_unavailable = isset($_POST['show_when_unavailable']) ? 1 : 0;
    $hide_when_unavailable = isset($_POST['hide_when_unavailable']) ? 1 : 0;

    // Use different approach for nullable days_to_make
    if ($days_to_make === null) {
        $stmt = $conn->prepare("INSERT INTO products (sku, name, description, price, status_id, quantity, days_to_make, is_featured, show_when_unavailable, hide_when_unavailable) 
                                VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?)");
        $stmt->bind_param("sssdiiiii", $sku, $name, $description, $price, $status_id, $quantity, $is_featured, $show_when_unavailable, $hide_when_unavailable);
    } else {
        $stmt = $conn->prepare("INSERT INTO products (sku, name, description, price, status_id, quantity, days_to_make, is_featured, show_when_unavailable, hide_when_unavailable) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdiiiiis", $sku, $name, $description, $price, $status_id, $quantity, $days_to_make, $is_featured, $show_when_unavailable, $hide_when_unavailable);
    }
    
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
                        $dbImagePath = "/../../assets/product-images/" . $folderName . "/" . $cleanFileName;
                        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 0)");
                        $stmt->bind_param("is", $product_id, $dbImagePath);
                        $stmt->execute();
                    }
                }
            }
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
    <?php if(isset($_SESSION['success_message'])): ?>
    <div class="success-popup" id="successPopup">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
    </div>
    <?php endif; ?>
    <div class="mainContainer">
        <form method="post" enctype="multipart/form-data">
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
                    <select class="statusGrp" name="status_id">
                        <option value="1">Delivery</option>
                        <option value="2">Pickup</option>
                        <option value="3">Unavailable</option>
                    </select>

                    <!-- Added Quantity Available For Pre-Order field -->
                    <label>Quantity Available For Pre-Order:</label>
                    <input class="quantity" type="number" name="quantity" min="0" step="1" value="0" required>

                    <!-- Added Days to Make field -->
                    <label>Days to Make:</label>
                    <input class="days-to-make" type="number" name="days_to_make" min="1" step="1" placeholder="Enter number of days " required>

                    <label>Visibility</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="is_featured" id="is_featured">
                            <label class = "cb-itm" for="is_featured" style="display: inline;">Feature Product</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="show_when_unavailable" id="show_when_unavailable">
                            <label class = "cb-itm" for="show_when_unavailable" style="display: inline;">Show when unavailable</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="hide_when_unavailable" id="hide_when_unavailable">
                            <label class = "cb-itm" for="hide_when_unavailable" style="display: inline;">Hide when unavailable</label>
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
</div>

<?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>

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
    });

    // Global variables to track uploaded files
    let additionalImagesArray = [];

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
</script>
</body>
</html>