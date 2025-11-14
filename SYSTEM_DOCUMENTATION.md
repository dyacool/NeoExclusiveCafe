# NeoCafe System Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Technology Stack](#technology-stack)
3. [Database Architecture](#database-architecture)
4. [Product System](#product-system)
5. [Order System](#order-system)
6. [Payment System](#payment-system)
7. [Session Management](#session-management)
8. [Image Management](#image-management)
9. [Cart System](#cart-system)
10. [Coupon System](#coupon-system)
11. [User Roles & Authentication](#user-roles--authentication)
12. [Business Logic Rules](#business-logic-rules)
13. [Known Issues & Fixes](#known-issues--fixes)

---

## System Overview

**NeoCafe** is a comprehensive e-commerce platform for a cafe/restaurant with advanced pre-order and same-day ordering capabilities. The system supports multiple product types, flexible ordering options, and real-time inventory management.

### Key Features
- **Dual Ordering System**: Pre-order AND same-day ordering for products
- **Dynamic Availability**: Products can be available on specific dates
- **Payment Integration**: PayMongo sandbox/test mode for card payments
- **Multi-cart Support**: Separate carts for pre-orders and same-day orders
- **Coupon System**: Percentage, fixed amount, and free shipping coupons
- **Image Management**: Cloudinary integration for image hosting
- **Session Recovery**: Three-tier recovery system for checkout process
- **Rider Management**: Delivery proof submission and order tracking

---

## Technology Stack

### Backend
- **PHP 8.x**: Server-side scripting
- **MySQL/MariaDB**: Database management
- **Apache/XAMPP**: Local development server
- **Composer**: PHP dependency management

### Frontend
- **HTML5/CSS3**: Markup and styling
- **JavaScript (Vanilla)**: Client-side interactivity
- **Flatpickr**: Date/time picker library
- **Custom CSS**: Responsive design

### Third-Party Services
- **PayMongo API**: Payment gateway (sandbox mode)
  - Secret Key: `sk_test_yb8pkZvUA3WjHP6T4FKhgudU`
  - Public Key: `pk_test_1XUMJ3yMs8QZugdq3uWr8vYU`
- **Cloudinary**: Image hosting and transformation
- **Git**: Version control

### File Structure
```
NeoCafe/
├── backend/
│   ├── api/
│   ├── config/
│   ├── database/
│   ├── includes/
│   ├── login/
│   ├── migrations/
│   └── pages/
│       ├── admin-includes/
│       ├── products/
│       └── user-page-content/
├── frontend/
│   ├── app.php
│   ├── api/
│   ├── assets/
│   ├── images/
│   ├── login/
│   ├── pages/
│   │   ├── cart/
│   │   ├── products/
│   │   └── rider/
│   ├── search/
│   └── user-includes/
├── includes/
│   ├── domain-utils.php
│   ├── session-manager.php
│   └── SESSION_MANAGER_GUIDE.md
├── config/
│   ├── cloudinary-config.php
│   ├── database-config.php
│   └── domain-config.php
├── logs/
├── scripts/
├── vendor/
├── composer.json
└── docker-compose.yml
```

---

## Database Architecture

### Core Tables

#### **users**
Stores customer and admin user information.
```sql
- id (PK)
- firstname
- lastname
- email
- password (hashed)
- role (admin/user)
- created_at
- updated_at
```

#### **products**
Main product catalog.
```sql
- id (PK)
- name
- description
- price
- quantity (pre-order stock)
- status_id (FK to product_statuses)
  * 1 = Regular (Pre-order)
  * 2 = Featured (Pre-order)
  * 3 = Limited (Pre-order)
  * 4 = Same Day Order ONLY
- availtoday_status_id (FK to availtoday_status) - enables same-day capability
- category_id (FK to categories)
- is_featured (boolean)
- show_when_unavailable (boolean)
- hide_when_unavailable (boolean)
- created_at
- updated_at
- deleted_at (soft delete)
```

#### **product_statuses**
Product type definitions.
```sql
- id (PK)
- name (Regular/Featured/Limited/Same Day Order)
- description
```

#### **availtoday_status**
Same-day availability configuration.
```sql
- id (PK)
- name
- description
```

#### **categories**
Product categorization.
```sql
- id (PK)
- name
- slug
- display_order
- is_active
```

#### **product_images**
Product image storage.
```sql
- id (PK)
- product_id (FK)
- image_url (local path)
- cloud_url (Cloudinary URL)
- is_primary (boolean)
- display_order
```

#### **quantity_per_day_sdo**
Same-day order stock per date.
```sql
- id (PK)
- product_id (FK)
- date (Y-m-d)
- quantity (available stock for that date)
- created_at
- updated_at
```

#### **todays_products_dates**
Availability dates for status_id = 4 (Same Day Order ONLY) products.
```sql
- id (PK)
- product_id (FK)
- available_date (Y-m-d)
- created_at
```

#### **regular_products_today_dates**
Availability dates for status_id 1/2/3 products WITH same-day capability.
```sql
- id (PK)
- product_id (FK)
- available_date (Y-m-d)
- created_at
```

#### **cart**
Pre-order shopping cart.
```sql
- id (PK)
- user_id (FK)
- product_id (FK)
- quantity
- created_at
- updated_at
```

#### **availtoday_cart**
Same-day order shopping cart.
```sql
- id (PK)
- user_id (FK)
- product_id (FK)
- quantity
- selected_date (Y-m-d)
- created_at
- updated_at
```

#### **orders**
Order records.
```sql
- id (PK)
- user_id (FK)
- order_number (unique)
- order_type (pickup/delivery)
- payment_method (cash/card/gcash)
- total_amount
- delivery_fee
- discount_amount
- status (pending/confirmed/preparing/ready/completed/cancelled)
- delivery_date
- delivery_time
- customer_name
- contact_number
- delivery_address
- delivery_location_id (FK)
- notes
- created_at
- updated_at
```

#### **order_items**
Individual items in each order.
```sql
- id (PK)
- order_id (FK)
- product_id (FK)
- product_name
- quantity
- price
- subtotal
```

#### **pending_payments**
Payment session recovery.
```sql
- id (PK)
- user_id (FK)
- payment_id (PayMongo payment intent ID or source ID)
- payment_type (card/gcash)
- order_type (regular/availtoday)
- amount
- payment_method
- order_data (JSON)
- created_at
- expires_at (1 hour TTL)
```

#### **coupons**
Discount coupon management.
```sql
- id (PK)
- code (unique)
- type (percentage/fixed/free_shipping)
- value (discount percentage or amount)
- min_purchase (minimum order amount)
- max_discount (maximum discount cap for percentage)
- usage_limit (total uses allowed)
- usage_count (current usage)
- valid_from
- valid_until
- is_active
- created_at
- updated_at
```

#### **coupon_usage**
Tracks coupon usage per user.
```sql
- id (PK)
- coupon_id (FK)
- user_id (FK)
- order_id (FK)
- used_at
```

#### **delivery_locations**
Delivery zones and fees.
```sql
- id (PK)
- barangay
- municipality
- delivery_fee
- is_active
```

#### **business_hours**
Operating hours configuration.
```sql
- id (PK)
- opening_time (H:i:s)
- closing_time (H:i:s)
- created_at
- updated_at
```

---

## Product System

### Product Types

#### **1. Pre-Order Products (status_id 1, 2, 3)**
- **Regular (1)**: Standard menu items available for pre-order
- **Featured (2)**: Highlighted products with special badge
- **Limited (3)**: Limited availability items

**Characteristics:**
- Stock tracked in `products.quantity`
- Can be ordered in advance (minimum 2 days ahead)
- Badge: "Pre-Order"
- Checkout: Uses regular `cart` table

#### **2. Same Day Order ONLY (status_id 4)**
- Products ONLY available for same-day delivery
- Cannot be pre-ordered
- Stock tracked per date in `quantity_per_day_sdo`
- Available dates in `todays_products_dates`
- Badge: "Same Day Order"
- Checkout: Uses `availtoday_cart` table

#### **3. Dual Capability (status_id 1/2/3 WITH availtoday_status_id)**
- Products with BOTH pre-order AND same-day capability
- Pre-order stock: `products.quantity`
- Same-day stock: `quantity_per_day_sdo.quantity`
- Same-day dates: `regular_products_today_dates`
- Badge: "Same Day & Pre-Order" (when both available)

### Product Availability Logic

**File:** `frontend/pages/products/product-dashboard.php`

**Function:** `determineProductAvailability()`

```php
// Pre-order capability
$has_preorder = in_array($status_id, [1, 2, 3]) && $preorder_stock > 0;

// Same-day capability for status 4
if ($status_id == 4) {
    $has_valid_date = in_array($today_date, $todays_dates);
    $has_sameday = ($sameday_stock > 0) && $has_valid_date;
}

// Same-day capability for dual products
if (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
    $has_valid_date = in_array($today_date, $regular_dates);
    $has_sameday = ($sameday_stock > 0) && $has_valid_date;
}

// Product is unavailable if NO capabilities
$is_unavailable = !$has_preorder && !$has_sameday;
```

### Quantity Modal Logic

**File:** `frontend/pages/products/product-dashboard.php` (JavaScript)

**Dual Capability Products:**
```javascript
if ((statusId == 1 || statusId == 2 || statusId == 3) && availtodayStatusId) {
    const preorderQty = await fetchPreOrderQuantityValue(pendingCartProduct.id);
    const samedayQty = await fetchTodayQuantityValue(pendingCartProduct.id);
    
    if (hasPreorderStock && hasSamedayStock && !businessClosed) {
        // Show BOTH options
        orderTypeSelector.style.display = 'block';
    } else if (hasSamedayStock && !hasPreorderStock) {
        // Show ONLY same-day
        orderTypeSelector.style.display = 'none';
        pendingCartProduct.selectedOrderType = 'sameday';
    } else if (hasPreorderStock && !hasSamedayStock) {
        // Show ONLY pre-order
        orderTypeSelector.style.display = 'none';
        pendingCartProduct.selectedOrderType = 'preorder';
    }
}
```

**Stock Fetching:**
- **Pre-order:** `get-preorder-quantity.php` → `products.quantity`
- **Same-day:** `get-sdo-quantity.php` → `quantity_per_day_sdo.quantity WHERE date = TODAY`

### Product Display Rules

**Visibility Flags:**
1. `hide_when_unavailable = 1`: ALWAYS hide when out of stock (highest priority)
2. `show_when_unavailable = 1`: ALWAYS show even when out of stock
3. Default: Hide unavailable products

**Sorting Priority:**
1. Available products first
2. Available today (same-day capability)
3. Featured products
4. Alphabetical by name
5. Unavailable products last

---

## Order System

### Order Types

#### **1. Regular Orders (Pre-order)**
- **Cart Table:** `cart`
- **Minimum Lead Time:** 2 days in advance
- **Order Type:** `pickup` or `delivery`
- **Stock Source:** `products.quantity`

#### **2. Same-Day Orders**
- **Cart Table:** `availtoday_cart`
- **Lead Time:** Same day (must order during business hours)
- **Order Type:** Always `delivery` (or pickup, depending on config)
- **Stock Source:** `quantity_per_day_sdo.quantity`

### Order Flow

**1. Add to Cart:**
```
Product Selection → Quantity Modal → Select Order Type → Add to Cart
                                         ↓
                              (Pre-order OR Same-day)
```

**2. Checkout Process:**
```
Cart → Checkout Form → Payment Selection → Payment Processing
  ↓
  └→ Date Selection (2-day minimum for pre-order)
  └→ Shipping Method (pickup/delivery)
  └→ Customer Info (auto-filled from saved info)
  └→ Coupon Application (optional)
  └→ Payment Method (cash/card/gcash)
```

**3. Payment Flow (Card Payment):**
```
Place Order → process-payment.php → Create Payment Intent
                                         ↓
                          Save to pending_payments table
                                         ↓
                              card-payment.php
                                         ↓
                          Enter Card Details (or auto-fill test card)
                                         ↓
                      create-payment-method.php → PayMongo API
                                         ↓
                      attach-payment-method.php → PayMongo API
                                         ↓
                              payment-return.php
                                         ↓
                      Recover from pending_payments
                                         ↓
                          Create Order in Database
                                         ↓
                          Deduct Stock Quantities
                                         ↓
                              Clear Cart
                                         ↓
                          Redirect to Success Page
```

### Order Status Workflow

```
pending → confirmed → preparing → ready → completed
                                   ↓
                              cancelled
```

**Status Descriptions:**
- **pending**: Order placed, awaiting admin confirmation
- **confirmed**: Admin confirmed, preparing to cook/prepare
- **preparing**: Currently being prepared
- **ready**: Ready for pickup/delivery
- **completed**: Order fulfilled
- **cancelled**: Order cancelled (by admin or customer)

---

## Payment System

### PayMongo Integration

**Mode:** Sandbox/Test Mode

**API Credentials:**
- Secret Key: `sk_test_yb8pkZvUA3WjHP6T4FKhgudU`
- Public Key: `pk_test_1XUMJ3yMs8QZugdq3uWr8vYU`
- API URL: `https://api.paymongo.com/v1`

**Payment Methods:**
1. **Card Payment** (via Payment Intent API)
2. **GCash** (via Source API)
3. **Cash on Delivery/Pickup**

### Test Cards

**Success Card:**
- Number: `4343 4343 4343 4345`
- Expiry: `12/25`
- CVC: `123`

**Declined Card:**
- Number: `4571 7360 0000 0075`

**3D Secure Required:**
- Number: `4120 0000 0000 0007`

### Payment Architecture

**Key Files:**
1. `process-payment.php`: Creates payment intent, saves to database
2. `card-payment.php`: Payment form with test card UI
3. `create-payment-method.php`: Creates PayMongo payment method
4. `attach-payment-method.php`: Attaches payment to intent
5. `payment-return.php`: Handles callback, creates order
6. `paymongo-config.php`: API configuration and helpers

### Session Recovery System

**Three-Tier Recovery:**
```
1. URL Parameters (payment_intent_id, source_id)
     ↓ (if missing)
2. PHP Session ($_SESSION['pending_payment'])
     ↓ (if missing)
3. Database (pending_payments table)
```

**pending_payments Table:**
- Stores payment intent ID and order data as JSON
- 1-hour expiration (TTL)
- Cleaned up automatically after successful order creation

### Critical Payment Fix

**Issue:** Session conflict causing `user_id` to be NULL

**Solution:** Remove duplicate `session_start()` calls
```php
// WRONG (process-payment.php):
session_start(); // Creates NEW session
require_once 'database.php'; // Also calls session_start()

// CORRECT:
// Don't start session here - database.php will handle it
require_once 'database.php'; // Continues existing session
```

This ensures the session from `checkout.php` (with logged-in user) continues through to `process-payment.php`, preventing `user_id` from being NULL.

---

## Session Management

### SessionManager Class

**File:** `includes/session-manager.php`

**Key Methods:**
```php
SessionManager::isUserLoggedIn()      // Check if user is logged in
SessionManager::getUserId()           // Get current user ID
SessionManager::requireUserLogin()    // Redirect if not logged in
SessionManager::requireAdminLogin()   // Redirect if not admin
SessionManager::login($userId, $userData)  // Set session data
SessionManager::logout()              // Clear session
```

### Session Configuration

**File:** `backend/pages/admin-includes/database.php`

```php
session_set_cookie_params([
    'lifetime' => 0,              // Session cookie (expires when browser closes)
    'path' => '/',
    'domain' => '',
    'secure' => false,            // HTTP (not HTTPS)
    'httponly' => true,           // Prevent JavaScript access
    'samesite' => 'Lax'          // Allow cross-subdomain (critical for payment flow)
]);
session_start();
```

**Critical:** `SameSite=Lax` is required for payment redirects to work across different PHP files.

### Session Data Structure

```php
$_SESSION['user_id']           // User ID
$_SESSION['user_firstname']    // First name
$_SESSION['user_lastname']     // Last name
$_SESSION['user_role']         // Role (admin/user)
$_SESSION['user_data']         // Full user data array
$_SESSION['session_data']      // Additional session metadata

// Checkout-specific
$_SESSION['selected_cart_ids'] // Cart items for checkout
$_SESSION['cart_total']        // Subtotal
$_SESSION['shipping_method']   // pickup/delivery
```

---

## Image Management

### Cloudinary Integration

**Configuration:** `config/cloudinary-config.php`

**Environment Variables:**
```
CLOUDINARY_CLOUD_NAME
CLOUDINARY_API_KEY
CLOUDINARY_API_SECRET
```

**Image Transformations:**
```php
[
    'width' => 800,           // Responsive width
    'quality' => 'auto',      // Automatic quality optimization
    'fetch_format' => 'auto', // WebP/AVIF when supported
    'crop' => 'limit'         // Don't upscale
]
```

**Migration Scripts:**
- `scripts/migrate-images-to-cloudinary.php`: Upload product images
- `scripts/migrate-blog-images-to-cloudinary.php`: Upload blog images

**Database Storage:**
- `product_images.image_url`: Original local path (backup)
- `product_images.cloud_url`: Cloudinary URL (primary)

**Fallback Logic:**
```php
$image_url = COALESCE(cloud_url, image_url)
```

If Cloudinary URL is not available, fall back to local image.

---

## Cart System

### Dual Cart Architecture

**1. Pre-Order Cart (`cart` table)**
- Products with `status_id` 1, 2, or 3
- Stock from `products.quantity`
- Checkout: `frontend/pages/cart/checkout.php`

**2. Same-Day Cart (`availtoday_cart` table)**
- Products with `status_id` 4 OR dual-capability products in same-day mode
- Stock from `quantity_per_day_sdo`
- Checkout: `frontend/pages/cart/availtoday-checkout.php`

### Cart Operations

**Add to Cart API:**
- Pre-order: `frontend/pages/cart/add-to-cart.php`
- Same-day: `frontend/pages/products/availtoday-cart-api.php`

**Cart Management:**
```javascript
// JavaScript functions
addToCart(productId, button)
updateAvailableTodayCartDisplay()
removeFromCart(productId)
updateCartQuantity(productId, newQuantity)
```

**Stock Validation:**
- Check available stock BEFORE adding to cart
- Display: `Stock: 50 (5 in cart, 45 available)`
- Prevent adding more than available quantity

---

## Coupon System

### Coupon Types

**1. Percentage Discount**
```sql
type = 'percentage'
value = 20  -- 20% off
max_discount = 500  -- Cap at ₱500
```

**2. Fixed Amount**
```sql
type = 'fixed'
value = 100  -- ₱100 off
```

**3. Free Shipping**
```sql
type = 'free_shipping'
value = 0  -- No numeric value needed
```

### Coupon Validation

**File:** `backend/pages/user-page-content/validate-coupon.php`

**Validation Rules:**
1. Coupon exists and is active
2. Current date between `valid_from` and `valid_until`
3. Order total >= `min_purchase`
4. `usage_count` < `usage_limit`
5. User hasn't exceeded per-user limit (if configured)

### Coupon Application Logic

**File:** `frontend/pages/cart/checkout.php`

```javascript
async function applyCoupon() {
    // Validate coupon via API
    const result = await fetch('validate-coupon.php', {
        method: 'POST',
        body: JSON.stringify({
            coupon_code: couponCode,
            subtotal: subtotal,
            cart_items: cartItems
        })
    });
    
    // Calculate discount
    discountAmount = calculateDiscount(appliedCoupon, subtotal);
    
    // Update totals
    updateTotalAmount(currentShippingFee);
}
```

**Discount Calculation:**
```javascript
function calculateDiscount(coupon, subtotalAmount) {
    if (coupon.type === 'percentage') {
        discount = (subtotalAmount * coupon.value) / 100;
        return Math.min(discount, subtotalAmount, coupon.max_discount);
    } else if (coupon.type === 'fixed') {
        return Math.min(coupon.value, subtotalAmount);
    } else if (coupon.type === 'free_shipping') {
        return 0; // Shipping fee set to 0 separately
    }
}
```

### Coupon Removal Fix

**Issue:** After removing a coupon, delivery fee changed to ₱50 instead of actual fee

**Fix:** Always get actual delivery fee from selected location
```javascript
function removeCoupon() {
    let shippingFee = 0;
    
    if (pickupRadio.checked) {
        shippingFee = 0;
    } else {
        // Get actual delivery fee from selected location
        const deliveryLocationSelect = document.getElementById('delivery_location');
        if (deliveryLocationSelect && deliveryLocationSelect.value) {
            const selectedOption = deliveryLocationSelect.options[deliveryLocationSelect.selectedIndex];
            shippingFee = parseFloat(selectedOption.dataset.deliveryFee) || 50;
        } else {
            shippingFee = 50; // Default
        }
        
        // Update display
        shippingFeeElement.textContent = '₱' + shippingFee.toFixed(2);
    }
    
    updateTotalAmount(shippingFee);
}
```

---

## User Roles & Authentication

### Roles

**1. Admin**
- Full system access
- Product management
- Order management
- User management
- Reports and analytics

**2. User (Customer)**
- Browse products
- Add to cart
- Place orders
- View order history
- Manage profile

**3. Rider**
- View assigned deliveries
- Submit delivery proof
- Update delivery status

### Authentication Files

**Login:**
- Customer: `frontend/login/user/login-signup.php`
- Admin: `backend/login/admin-login.php`
- Rider: `rider/rider-login.php`

**Login Check:**
```php
SessionManager::requireUserLogin('path/to/login.php');
```

**Role Check:**
```php
if (SessionManager::isAdmin()) {
    // Admin-only content
}
```

---

## Business Logic Rules

### Pre-Order Requirements

**Minimum Lead Time:** 2 days in advance
```javascript
const minDaysAhead = 2;
const minDate = new Date();
minDate.setDate(today.getDate() + minDaysAhead);
```

**Calendar Availability:**
- Dates in the past: Disabled
- Today: Disabled
- Tomorrow: Disabled
- 2 days from today: First available date

### Shipping Method Rules

**Product-Based Shipping:**
- **Status 1 (Pick Up Only)**: Force pickup, disable delivery
- **Status 2 (Delivery Only)**: Force delivery, disable pickup
- **Status 3 (Flexible)**: User can choose
- **Mixing Rule**: Cannot mix Pick Up Only + Delivery Only in same cart

**Implementation:**
```php
if ($has_pickup_only && $has_delivery_only) {
    // ERROR: Cannot mix
    $_SESSION['error_message'] = "Cannot mix Pick Up Only and Delivery Only products";
    redirect('cart.php');
}
```

### Business Hours

**Configuration:** `business_hours` table
```sql
opening_time = '08:00:00'
closing_time = '21:00:00'
```

**Same-Day Cutoff:**
- Orders must be placed during business hours
- After closing time: Same-day option disabled

**JavaScript Check:**
```javascript
function isBusinessClosed() {
    const currentTime = new Date();
    const currentHours = currentTime.getHours();
    const currentMinutes = currentTime.getMinutes();
    
    const openingTime = '08:00';
    const closingTime = '21:00';
    
    return currentTimeStr < openingTime || currentTimeStr > closingTime;
}
```

---

## Known Issues & Fixes

### 1. Invalid Payment Session Error

**Issue:** "Invalid payment session. Please try again from checkout"

**Root Cause:** `SameSite=Strict` blocked cookies across payment redirects

**Fix:** Changed to `SameSite=Lax`
```php
session_set_cookie_params([
    'samesite' => 'Lax'  // Changed from 'Strict'
]);
```

**Status:** ✅ FIXED

---

### 2. PayMongo SDK Not Loading

**Issue:** "PaymongoPlugin is not defined" - SDK JavaScript failed to load (404)

**Root Cause:** PayMongo SDK URL doesn't exist

**Fix:** Built custom implementation without SDK
- Custom card form
- Direct API calls to PayMongo REST API
- No external JavaScript dependencies

**Status:** ✅ FIXED

---

### 3. HTTP 400 Error with Test Cards

**Issue:** PayMongo API returning 400 Bad Request

**Root Cause:** Year format mismatch (2-digit vs 4-digit)

**Fix:** Convert 2-digit year to 4-digit before API call
```javascript
const exp_year = parseInt(cardExpiry.split('/')[1]);
const fullYear = exp_year < 100 ? 2000 + exp_year : exp_year;
```

**Status:** ✅ FIXED

---

### 4. Payment Fails with "No pending payment session found"

**Issue:** Payment intent created successfully but order creation fails

**Root Cause:** Session conflict - `process-payment.php` creating new session, losing `user_id`

**Analysis:**
```
checkout.php → session with user_id=13
process-payment.php → session_start() → NEW session (user_id=NULL)
database.php → session_start() → conflicts
Result: Lost user_id, database save fails
```

**Fix:** Remove duplicate session_start()
```php
// process-payment.php - BEFORE (BROKEN):
session_start(); // Creates NEW session
require_once 'database.php';

// process-payment.php - AFTER (FIXED):
// DON'T start session here - database.php will handle it
require_once 'database.php'; // Continues existing session
```

**Status:** ✅ FIXED

---

### 5. Coupon Conflicts with Delivery Fee

**Issue:** After removing a discount coupon, delivery fee changed to ₱50 instead of actual location fee

**Root Cause:** `removeCoupon()` only checked for `free_shipping` coupon type, hardcoded ₱50 for others

**Fix:** Always get actual delivery fee from selected location
```javascript
function removeCoupon() {
    // Get actual delivery fee based on shipping method
    let shippingFee = 0;
    
    if (pickupRadio.checked) {
        shippingFee = 0;
    } else {
        const deliveryLocationSelect = document.getElementById('delivery_location');
        if (deliveryLocationSelect && deliveryLocationSelect.value) {
            const selectedOption = deliveryLocationSelect.options[deliveryLocationSelect.selectedIndex];
            shippingFee = parseFloat(selectedOption.dataset.deliveryFee) || 50;
        } else {
            shippingFee = 50;
        }
        
        shippingFeeElement.textContent = '₱' + shippingFee.toFixed(2);
    }
    
    updateTotalAmount(shippingFee);
}
```

**Status:** ✅ FIXED

---

### 6. Same-Day Badge Showing Without Date Validation

**Issue:** "Same Day Order" badge displayed even when no date configured for today

**Root Cause:** Badge logic relied on `$is_available_today` calculated before stock checks, didn't explicitly validate dates

**Fix:** Explicitly check dates when determining badge display
```php
// Check same-day capability with date validation
if ($row['status_id'] == 4) {
    // Status 4: Check stock AND date exists in todays_product_dates
    $has_date_today = false;
    if (!empty($row['todays_product_dates'])) {
        $todays_dates = explode(', ', $row['todays_product_dates']);
        $has_date_today = in_array($today_date, $todays_dates);
    }
    $has_sameday = ($sameday_stock > 0) && $has_date_today;
} elseif (!empty($row['availtoday_status_id'])) {
    // Status 1/2/3 with same-day: Check stock AND date in regular_products_today_dates
    $has_date_today = false;
    if (!empty($row['regular_today_dates'])) {
        $regular_dates = explode(', ', $row['regular_today_dates']);
        $has_date_today = in_array($today_date, $regular_dates);
    }
    $has_sameday = ($sameday_stock > 0) && $has_date_today;
}
```

**Status:** ✅ FIXED

---

## Development Workflow

### Local Development

**Environment:** XAMPP on Windows
- Document Root: `D:\XAMPP\htdocs\NeoCafe`
- PHP: 8.x
- MySQL: Via XAMPP Control Panel
- Apache: Port 80

**Access URLs:**
- Customer: `http://neocafe.cafe:8080` (domain-config.php routing)
- Admin: `http://localhost/NeoCafe/backend`
- Rider: `http://localhost/NeoCafe/rider`

### Database Management

**Backup Files:**
- `neoexclusivecafe_crud (backup db).sql`
- `crud (11).sql`

**Migration Files:**
- `sql_configs/` directory
- Individual table creation scripts
- `migrations/` directory (for schema changes)

### Git Workflow

**Main Branch:** `main` (assumed)

**Common Commands:**
```bash
git status
git add .
git commit -m "Description"
git push origin main
```

### Debugging

**Error Logging:**
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Debug message: " . print_r($variable, true));
```

**Log Files:**
- `logs/payment_errors.log`: Payment processing errors
- PHP error log (XAMPP: `xampp/php/logs/php_error_log`)

**Browser Console:**
- Network tab: Check API responses
- Console: JavaScript errors
- Application → Storage: Session/localStorage data

---

## API Endpoints

### Payment APIs

**PayMongo:**
- Create Payment Intent: `POST /v1/payment_intents`
- Create Payment Method: `POST /v1/payment_methods`
- Attach Payment Method: `POST /v1/payment_intents/{id}/attach`

**Internal:**
- `process-payment.php`: Create payment intent
- `create-payment-method.php`: Create PayMongo payment method
- `attach-payment-method.php`: Attach payment to intent
- `payment-return.php`: Handle payment callback

### Cart APIs

**Pre-Order Cart:**
- Add: `POST frontend/pages/cart/add-to-cart.php`
- Update: `POST frontend/pages/cart/update-cart.php`
- Remove: `POST frontend/pages/cart/remove-from-cart.php`

**Same-Day Cart:**
- Add/Update/Remove: `POST frontend/pages/products/availtoday-cart-api.php`
  - Action: `add`, `update`, `remove`, `clear`

### Product APIs

**Stock Check:**
- Pre-order: `GET get-preorder-quantity.php?product_id=X`
- Same-day: `GET get-sdo-quantity.php?product_id=X`

**Response Format:**
```json
{
    "success": true,
    "quantity": 50,
    "cart_quantity": 5,
    "has_date_today": true
}
```

### Coupon APIs

**Validate:**
- `POST backend/pages/user-page-content/validate-coupon.php`

**Request:**
```json
{
    "coupon_code": "SAVE20",
    "subtotal": 1000,
    "cart_items": [...]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Coupon applied successfully",
    "coupon": {
        "code": "SAVE20",
        "type": "percentage",
        "value": 20,
        "max_discount": 500
    }
}
```

---

## Security Considerations

### SQL Injection Prevention

**Always use prepared statements:**
```php
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
```

### XSS Prevention

**Escape output:**
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### CSRF Protection

**Session-based validation:**
- Check `$_SESSION['user_id']` on all sensitive operations
- Validate form submissions come from logged-in users

### Password Security

**Hashing:**
```php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$verified = password_verify($input_password, $hashed_password);
```

### Payment Security

**PayMongo Best Practices:**
- Never expose secret keys in client-side code
- Use public key only in frontend
- Process payments server-side
- Validate payment status via API before creating order

---

## Performance Optimization

### Image Optimization

**Cloudinary Transformations:**
- Automatic format selection (WebP/AVIF)
- Quality: `auto`
- Responsive sizing based on viewport
- Lazy loading: `loading="lazy"`

### Database Optimization

**Indexes:**
- Primary keys on all tables
- Foreign keys for relationships
- Index on frequently queried columns (`product_id`, `user_id`, `date`)

**Query Optimization:**
- Use `LIMIT` for pagination
- `LEFT JOIN` instead of multiple queries
- `GROUP_CONCAT` for aggregating related data

### Caching

**Session Caching:**
- Store cart totals in session
- Cache user data after login
- Avoid repeated database queries

**Browser Caching:**
```php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
```

---

## Future Enhancements

### Planned Features

1. **Email Notifications:**
   - Order confirmation emails
   - Order status updates
   - Payment receipts

2. **SMS Notifications:**
   - Order ready for pickup
   - Delivery status updates

3. **Advanced Analytics:**
   - Sales reports
   - Popular products
   - Revenue tracking

4. **Inventory Alerts:**
   - Low stock warnings
   - Automatic reorder points

5. **Customer Reviews:**
   - Product ratings
   - Review moderation
   - Featured reviews

6. **Loyalty Program:**
   - Points system
   - Reward tiers
   - Exclusive deals

7. **Live Order Tracking:**
   - Real-time delivery tracking
   - Estimated arrival time
   - Map integration

---

## Troubleshooting Guide

### Common Issues

**Issue: Session not persisting**
- Check `session_start()` is called in `database.php`
- Verify no output before `session_start()`
- Check `SameSite` cookie setting

**Issue: Images not loading**
- Check Cloudinary credentials
- Verify `cloud_url` in database
- Check fallback to local images
- Inspect network tab for 404 errors

**Issue: Payment failing**
- Check PayMongo API keys (sandbox vs live)
- Verify test card number format
- Check `pending_payments` table for records
- Review `logs/payment_errors.log`

**Issue: Cart not updating**
- Clear browser cache and cookies
- Check JavaScript console for errors
- Verify API endpoints returning success
- Check session data in PHP

**Issue: Product not showing**
- Check `deleted_at` is NULL
- Verify stock quantity > 0 (unless `show_when_unavailable = 1`)
- Check `status_id` is 1, 2, 3, or 4
- Verify category is active

---

## Configuration Files

### database-config.php
```php
$host = 'localhost';
$db_name = 'neoexclusivecafe_crud';
$username = 'root';
$password = '';
```

### cloudinary-config.php
```php
$cloudinary_cloud_name = getenv('CLOUDINARY_CLOUD_NAME');
$cloudinary_api_key = getenv('CLOUDINARY_API_KEY');
$cloudinary_api_secret = getenv('CLOUDINARY_API_SECRET');
```

### paymongo-config.php
```php
$mode = 'sandbox'; // or 'live'
$secret_key = 'sk_test_yb8pkZvUA3WjHP6T4FKhgudU';
$public_key = 'pk_test_1XUMJ3yMs8QZugdq3uWr8vYU';
```

### domain-config.php
```php
// Domain routing configuration
$primary_domain = 'neocafe.cafe';
$admin_subdomain = 'admin.neocafe.cafe';
$rider_subdomain = 'rider.neocafe.cafe';
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] Switch PayMongo to live mode
- [ ] Update API keys (live keys)
- [ ] Set `display_errors = 0` in PHP
- [ ] Enable HTTPS/SSL
- [ ] Update `SameSite=None; Secure` for cookies
- [ ] Backup database
- [ ] Test payment flow end-to-end
- [ ] Test all cart operations
- [ ] Verify coupon validation
- [ ] Check image loading (Cloudinary)

### Post-Deployment

- [ ] Monitor error logs
- [ ] Test live payments
- [ ] Verify email notifications
- [ ] Check mobile responsiveness
- [ ] Test checkout flow
- [ ] Verify stock deduction
- [ ] Test order status updates

---

## Maintenance Tasks

### Daily
- Monitor payment errors log
- Check order processing
- Verify stock levels

### Weekly
- Review coupon usage
- Check expired coupons
- Clean up old sessions
- Review order status

### Monthly
- Database backup
- Clean up old logs
- Review and optimize queries
- Update dependencies

---

## Contact & Support

**System Documentation Version:** 1.0
**Last Updated:** November 14, 2025
**Maintained By:** Development Team

---

## Glossary

**Pre-Order:** Orders placed in advance (minimum 2 days) for future fulfillment
**Same-Day Order:** Orders placed and fulfilled on the same day
**Dual Capability:** Products that support both pre-order and same-day ordering
**Status ID:** Product type identifier (1=Regular, 2=Featured, 3=Limited, 4=Same Day Only)
**Payment Intent:** PayMongo payment session for card transactions
**Source:** PayMongo payment reference for GCash/other methods
**Session Recovery:** Three-tier system to recover payment data after redirects
**TTL:** Time To Live - expiration time for pending payments (1 hour)
**Cloudinary:** Cloud-based image hosting and transformation service
**SameSite:** Cookie security attribute controlling cross-site sending
**XAMPP:** Local development stack (Apache, MySQL, PHP, Perl)

---

**End of Documentation**
