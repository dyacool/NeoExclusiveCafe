# Design Document: Rider Delivery Proof System

## Overview

This design implements a mobile-responsive delivery proof system for riders, enabling them to capture photographic evidence of deliveries using device cameras. The system includes a dedicated rider interface, camera integration via WebRTC, proof storage, and display across admin and customer interfaces.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                  Rider Mobile Interface                      │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Orders Table (Delivery orders due today)            │   │
│  │  - Order #, Customer, Address, Products, Total       │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Proof of Delivery Modal                             │   │
│  │  - Camera Preview (WebRTC)                           │   │
│  │  - Capture Button                                    │   │
│  │  - [Close] [Confirm] Actions                         │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   API Layer                                  │
│  - submit-delivery-proof.php (Upload & Status Update)       │
│  - get-rider-orders.php (Fetch today's deliveries)          │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  Storage Layer                               │
│  - /uploads/delivery-proofs/ (Image files)                  │
│  - orders table (delivery_proof_path, timestamp)            │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Display Interfaces                              │
│  - order-list.php (Admin: Proof thumbnails)                 │
│  - view-orders.php (Admin: Full proof display)              │
│  - order-details.php (Customer: Proof display)              │
│  - profile.php (Customer: Proof indicators in history)      │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Database Schema Updates

#### Create pod_orders Table (Proof of Delivery)

```sql
CREATE TABLE IF NOT EXISTS `pod_orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `proof_image_path` VARCHAR(255) NOT NULL,
  `submitted_by` VARCHAR(100) NULL COMMENT 'Rider name or ID',
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image_size` INT(11) NULL COMMENT 'File size in bytes',
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order` (`order_id`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_submitted_at` (`submitted_at`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Stores proof of delivery images for completed orders';
```

**Fields:**
- `id`: Primary key
- `order_id`: Foreign key to orders table (unique - one proof per order)
- `proof_image_path`: Relative path to proof image (e.g., 'uploads/delivery-proofs/order_123_20251102_143022.jpg')
- `submitted_by`: Rider identifier who submitted the proof
- `submitted_at`: Timestamp when proof was submitted
- `image_size`: File size for monitoring
- `notes`: Optional notes from rider

**Design Decision:** Using a separate table allows for future expansion (multiple proofs, proof history, etc.) and keeps the orders table clean.



### 2. Rider Interface (rider/orders.php)

#### Mobile-Responsive Orders Table

**HTML Structure:**
```html
<div class="rider-container">
    <h1>Today's Deliveries</h1>
    <div class="orders-table-wrapper">
        <table class="rider-orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Products</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
                <!-- Orders populated via PHP/AJAX -->
            </tbody>
        </table>
    </div>
</div>
```

**Query for Today's Deliveries:**
```sql
SELECT o.order_id, o.customer_name, o.customer_address, 
       o.total_amount, o.delivery_time, o.status,
       pod.proof_image_path, pod.submitted_at,
       GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.product_name) SEPARATOR ', ') as products
FROM orders o
LEFT JOIN order_items oi ON o.order_id = oi.order_id
LEFT JOIN products p ON oi.product_id = p.product_id
LEFT JOIN pod_orders pod ON o.order_id = pod.order_id
WHERE o.delivery_method = 'Delivery'
AND o.delivery_date = CURDATE()
AND o.status IN ('Ready for Delivery', 'Out for Delivery')
GROUP BY o.order_id
ORDER BY o.delivery_time ASC
```



### 3. Proof of Delivery Modal

#### Modal HTML Structure

```html
<div id="proof-modal" class="proof-modal">
    <div class="proof-modal-content">
        <h2>Proof of Delivery</h2>
        <p class="order-info">Order #<span id="modal-order-id"></span></p>
        
        <div class="camera-container">
            <video id="camera-preview" autoplay playsinline></video>
            <canvas id="photo-canvas" style="display:none;"></canvas>
            <img id="captured-photo" style="display:none;" />
        </div>
        
        <div class="camera-error" id="camera-error" style="display:none;">
            <p>Camera access denied. Please enable camera permissions.</p>
        </div>
        
        <div class="modal-actions">
            <button id="close-modal-btn" class="btn-secondary">Close</button>
            <button id="capture-btn" class="btn-primary">Capture Photo</button>
            <button id="confirm-btn" class="btn-success" style="display:none;">Confirm Delivery</button>
        </div>
        
        <div class="upload-progress" id="upload-progress" style="display:none;">
            <div class="progress-bar"></div>
            <p>Uploading proof...</p>
        </div>
    </div>
</div>
```

#### Camera Integration (JavaScript)

```javascript
let videoStream = null;
let capturedImageBlob = null;

async function openProofModal(orderId) {
    document.getElementById('modal-order-id').textContent = orderId;
    document.getElementById('proof-modal').style.display = 'flex';
    
    try {
        // Request camera access (prefer rear camera on mobile)
        const constraints = {
            video: {
                facingMode: 'environment', // Rear camera
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        };
        
        videoStream = await navigator.mediaDevices.getUserMedia(constraints);
        document.getElementById('camera-preview').srcObject = videoStream;
        document.getElementById('camera-error').style.display = 'none';
    } catch (error) {
        console.error('Camera access error:', error);
        document.getElementById('camera-error').style.display = 'block';
        document.getElementById('capture-btn').disabled = true;
    }
}

function capturePhoto() {
    const video = document.getElementById('camera-preview');
    const canvas = document.getElementById('photo-canvas');
    const context = canvas.getContext('2d');
    
    // Set canvas size to video size
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw video frame to canvas
    context.drawImage(video, 0, 0);
    
    // Convert to blob
    canvas.toBlob((blob) => {
        capturedImageBlob = blob;
        
        // Show captured photo
        const img = document.getElementById('captured-photo');
        img.src = URL.createObjectURL(blob);
        img.style.display = 'block';
        video.style.display = 'none';
        
        // Show confirm button, hide capture button
        document.getElementById('capture-btn').style.display = 'none';
        document.getElementById('confirm-btn').style.display = 'inline-block';
    }, 'image/jpeg', 0.85); // 85% quality
}
```



### 4. Proof Submission API

#### File: rider/submit-delivery-proof.php

**Purpose:** Handle proof upload, update order status, send notifications

**Request:**
```
POST /rider/submit-delivery-proof.php
Content-Type: multipart/form-data

order_id: 123
proof_image: [binary image data]
```

**Response:**
```json
{
  "success": true,
  "message": "Delivery proof submitted successfully",
  "order_id": 123,
  "proof_path": "uploads/delivery-proofs/order_123_20251102_143022.jpg",
  "new_status": "Delivered"
}
```

**Implementation Logic:**
1. Validate rider session
2. Validate order_id and image file
3. Generate unique filename: `order_{order_id}_{timestamp}.jpg`
4. Save image to `uploads/delivery-proofs/`
5. Insert record into pod_orders table with proof_path, order_id, rider info
6. Update order status to "Delivered"
7. Set completion_date in orders table
8. Send email notification to customer
9. Create in-app notification
10. Log activity
11. Return success response



### 5. Admin Order List Integration (order-list.php)

#### Add Proof Column to Table

```html
<th>Delivery Proof</th>
```

```php
<td data-label="Delivery Proof">
    <?php if ($row['delivery_method'] == 'Delivery' && $row['status'] == 'Delivered'): ?>
        <?php 
        // Query pod_orders for proof
        $pod_sql = "SELECT proof_image_path, submitted_at FROM pod_orders WHERE order_id = ?";
        $pod_stmt = mysqli_prepare($conn, $pod_sql);
        mysqli_stmt_bind_param($pod_stmt, "i", $row['order_id']);
        mysqli_stmt_execute($pod_stmt);
        $pod_result = mysqli_stmt_get_result($pod_stmt);
        $pod = mysqli_fetch_assoc($pod_result);
        ?>
        <?php if ($pod && !empty($pod['proof_image_path']) && file_exists($pod['proof_image_path'])): ?>
            <div class="proof-thumbnail" onclick="showProofModal('<?php echo $pod['proof_image_path']; ?>')">
                <img src="<?php echo $pod['proof_image_path']; ?>" alt="Delivery Proof">
                <span class="proof-timestamp"><?php echo date('m/d H:i', strtotime($pod['submitted_at'])); ?></span>
            </div>
        <?php else: ?>
            <span class="no-proof">No proof yet</span>
        <?php endif; ?>
    <?php else: ?>
        <span class="not-applicable">N/A</span>
    <?php endif; ?>
</td>
```

#### Proof Modal for Full View

```html
<div id="proof-view-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Delivery Proof</h3>
        <img id="proof-full-image" src="" alt="Delivery Proof">
        <p id="proof-timestamp"></p>
        <a id="proof-download" href="" download class="btn-download">Download</a>
    </div>
</div>
```



### 6. Customer Order Details Integration (order-details.php)

#### Proof Container Section

```html
<?php if ($order['delivery_method'] == 'Delivery' && $order['status'] == 'Delivered'): ?>
<?php 
// Query pod_orders for proof
$pod_sql = "SELECT proof_image_path, submitted_at, submitted_by FROM pod_orders WHERE order_id = ?";
$pod_stmt = mysqli_prepare($conn, $pod_sql);
mysqli_stmt_bind_param($pod_stmt, "i", $order['order_id']);
mysqli_stmt_execute($pod_stmt);
$pod_result = mysqli_stmt_get_result($pod_stmt);
$pod = mysqli_fetch_assoc($pod_result);
?>
<div class="delivery-proof-section">
    <h3>Delivery Proof</h3>
    <?php if ($pod && !empty($pod['proof_image_path']) && file_exists($pod['proof_image_path'])): ?>
        <div class="proof-container">
            <img src="<?php echo $pod['proof_image_path']; ?>" alt="Delivery Proof" class="proof-image">
            <p class="proof-info">
                Delivered on: <?php echo date('F j, Y \a\t g:i A', strtotime($pod['submitted_at'])); ?>
                <?php if (!empty($pod['submitted_by'])): ?>
                    <br>By: <?php echo htmlspecialchars($pod['submitted_by']); ?>
                <?php endif; ?>
            </p>
            <a href="<?php echo $pod['proof_image_path']; ?>" download class="btn-download">
                Download Proof
            </a>
        </div>
    <?php else: ?>
        <p class="no-proof-message">Delivery proof not yet available</p>
    <?php endif; ?>
</div>
<?php endif; ?>
```

### 7. Customer Profile Order History Integration (profile.php)

#### Add Proof Indicator to Order History

```php
<?php if ($order['delivery_method'] == 'Delivery' && $order['status'] == 'Delivered'): ?>
    <?php 
    // Check if proof exists in pod_orders
    $pod_check_sql = "SELECT id FROM pod_orders WHERE order_id = ?";
    $pod_check_stmt = mysqli_prepare($conn, $pod_check_sql);
    mysqli_stmt_bind_param($pod_check_stmt, "i", $order['order_id']);
    mysqli_stmt_execute($pod_check_stmt);
    $has_proof = mysqli_stmt_get_result($pod_check_stmt)->num_rows > 0;
    ?>
    <div class="proof-indicator">
        <?php if ($has_proof): ?>
            <button class="proof-icon" onclick="showProofModal(<?php echo $order['order_id']; ?>)">
                📷 View Proof
            </button>
        <?php else: ?>
            <span class="no-proof-text">No proof</span>
        <?php endif; ?>
    </div>
<?php endif; ?>
```



## Data Models

### POD (Proof of Delivery) Model

```php
class ProofOfDelivery {
    public $id;
    public $order_id;
    public $proof_image_path;
    public $submitted_by;
    public $submitted_at;
    public $image_size;
    public $notes;
    
    public function __construct($conn, $order_id) {
        $sql = "SELECT * FROM pod_orders WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            foreach ($row as $key => $value) {
                $this->$key = $value;
            }
        }
    }
    
    public function exists() {
        return !empty($this->id);
    }
    
    public function hasValidImage() {
        return $this->exists() && 
               !empty($this->proof_image_path) && 
               file_exists($this->proof_image_path);
    }
    
    public function getImageUrl() {
        return $this->hasValidImage() ? 
               '/' . $this->proof_image_path : null;
    }
    
    public static function create($conn, $order_id, $image_path, $submitted_by, $image_size = null) {
        $sql = "INSERT INTO pod_orders (order_id, proof_image_path, submitted_by, image_size) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issi", $order_id, $image_path, $submitted_by, $image_size);
        return mysqli_stmt_execute($stmt);
    }
}
```

## Error Handling

### Camera Access Errors

1. **Permission Denied**
   - Display: "Camera access denied. Please enable camera permissions in your browser settings."
   - Action: Disable capture button, show instructions

2. **No Camera Available**
   - Display: "No camera detected on this device."
   - Action: Provide alternative upload option

3. **Camera In Use**
   - Display: "Camera is being used by another application."
   - Action: Prompt to close other apps

### Upload Errors

1. **Network Failure**
   - Retry: Automatic retry up to 3 times
   - Fallback: Queue for later upload
   - Display: "Upload failed. Retrying..."

2. **File Too Large**
   - Validation: Check file size < 5MB before upload
   - Display: "Image too large. Please try again."
   - Action: Compress image further

3. **Server Error**
   - Display: "Server error. Please try again later."
   - Action: Log error, allow retry

## Testing Strategy

### Unit Tests

1. **Image Capture**
   - Test: Camera stream initialization
   - Test: Photo capture from video stream
   - Test: Image compression and quality

2. **File Upload**
   - Test: Multipart form data submission
   - Test: File validation (type, size)
   - Test: Unique filename generation

3. **Status Update**
   - Test: Order status changes to "Delivered"
   - Test: Completion date is set
   - Test: Notifications are sent

### Integration Tests

1. **End-to-End Flow**
   - Test: Rider opens modal → captures photo → confirms → order marked delivered
   - Test: Proof appears in admin order list
   - Test: Proof appears in customer order details
   - Test: Customer receives email notification

2. **Mobile Responsiveness**
   - Test: Table layout on various screen sizes
   - Test: Modal display on mobile devices
   - Test: Camera access on iOS and Android

### Manual Testing Checklist

- [ ] Rider can view today's delivery orders
- [ ] Clicking order row opens proof modal
- [ ] Camera preview displays correctly
- [ ] Capture button takes photo
- [ ] Captured photo displays for review
- [ ] Confirm button uploads proof
- [ ] Order status changes to "Delivered"
- [ ] Proof appears in admin order list
- [ ] Proof appears in customer order details
- [ ] Proof indicator shows in customer order history
- [ ] Email notification sent to customer
- [ ] Download button works for proof images

## Security Considerations

1. **Authentication**
   - Verify rider session on all requests
   - Validate rider has permission to mark order as delivered

2. **File Upload Security**
   - Validate file type (JPEG, PNG only)
   - Sanitize filename
   - Store outside web root if possible
   - Limit file size to 5MB

3. **SQL Injection Prevention**
   - Use prepared statements for all queries
   - Validate and sanitize order_id input

4. **XSS Prevention**
   - Escape all output in HTML
   - Use htmlspecialchars() for user data

## Performance Considerations

1. **Image Optimization**
   - Compress images to 85% quality
   - Resize to max 1920x1080
   - Use progressive JPEG format

2. **Database Indexing**
   - Index on delivery_proof_path
   - Index on delivery_date for rider queries

3. **Caching**
   - Cache today's delivery orders for 5 minutes
   - Use browser caching for proof images

## Mobile Responsiveness

### Breakpoints

- **Desktop**: > 768px - Full table layout
- **Tablet**: 426px - 768px - Condensed table
- **Mobile**: < 426px - Card-based layout

### Touch Optimization

- Minimum touch target: 44x44px
- Swipe gestures for navigation
- Large, easy-to-tap buttons
- Optimized camera controls for touch

## Migration Plan

1. **Database Migration**
   - Create pod_orders table
   - Add foreign key constraint to orders table
   - Add indexes for performance

2. **Create Directories**
   - Create uploads/delivery-proofs/
   - Set proper permissions (755)

3. **Deploy Files**
   - Deploy rider/orders.php
   - Deploy rider/submit-delivery-proof.php
   - Update order-list.php
   - Update order-details.php
   - Update profile.php

4. **Testing**
   - Test on mobile devices
   - Test camera access
   - Test proof upload
   - Test proof display

5. **Rollback Plan**
   - Keep backup of original files
   - Document manual delivery marking process
   - Disable rider interface if issues arise
