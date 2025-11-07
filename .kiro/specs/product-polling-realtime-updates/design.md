# Design Document

## Overview

This design extends the existing polling-based realtime update system to product pages. The system will automatically update product stock quantities on both the frontend product dashboard (customer-facing) and backend product list (admin-facing) when orders are successfully placed. The implementation follows the exact same pattern as the existing order-list and admin-dashboard polling systems for consistency and maintainability.

## Architecture

### High-Level Architecture

```
┌─────────────────────┐                                    ┌──────────────────────┐
│  Frontend Product   │    AJAX Polling (every 5s)        │                      │
│  Dashboard          │ ─────────────────────────────────> │  PHP Backend API     │
│  (Customer)         │ <───────────────────────────────── │  get-product-list    │
└─────────────────────┘    JSON Response (products)        │  (Frontend)          │
                                                            └──────────────────────┘
                                                                     │
┌─────────────────────┐                                             │
│  Backend Product    │    AJAX Polling (every 5s)                 │
│  List (Admin)       │ ─────────────────────────────────>         │
│                     │ <───────────────────────────────── ┌────────▼──────────┐
└─────────────────────┘    JSON Response (products)        │                   │
                                                            │  MySQL Database   │
                           ┌─────────────────────┐         │  (products table) │
                           │  Payment Success    │         │                   │
                           │  payment-return.php │────────>│  Stock Decrement  │
                           └─────────────────────┘         └───────────────────┘
```

### Polling Flow

1. **Customer places order** → `payment-return.php` processes payment → Stock decremented in database
2. **Frontend polling** (every 5s) → Fetches updated product data → Updates product cards silently
3. **Backend polling** (every 5s) → Fetches updated product data → Updates product table with visual feedback

## Components and Interfaces

### 1. Frontend Product Dashboard Polling Client (JavaScript)

**File**: `frontend/assets/js/product-dashboard-polling.js`

**Responsibilities**:
- Initialize polling loop when product dashboard loads
- Make periodic AJAX requests to fetch product updates
- Update product cards with new stock quantities and availability
- Handle errors with exponential backoff
- Stop polling when user navigates away
- Update silently without loading indicators

**Key Functions**:
```javascript
class ProductDashboardPoller {
    constructor(options) {
        this.pollInterval = options.pollInterval || 5000; // 5 seconds
        this.maxRetries = options.maxRetries || 3;
        this.backoffMultiplier = 2;
        this.maxBackoff = 30000; // 30 seconds
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastUpdateTimestamp = null;
        this.currentCategory = null; // Track active category filter
        this.productsContainer = null; // Reference to products grid
    }

    start() { /* Start polling loop */ }
    stop() { /* Stop polling and cleanup */ }
    poll() { /* Make AJAX request */ }
    updateProducts(data) { /* Update product cards silently */ }
    updateProductCard(productId, newData) { /* Update individual product */ }
    handleError(error) { /* Error handling with backoff */ }
    resetBackoff() { /* Reset backoff on success */ }
}
```

**Silent Update Strategy**:
- No loading spinners or indicators
- Update only changed values (stock, availability status)
- Preserve user scroll position
- Don't interrupt modal views or add-to-cart operations

### 2. Backend Product List Polling Client (JavaScript)

**File**: `backend/assets/js/product-list-polling.js`

**Responsibilities**:
- Initialize polling loop when product list page loads
- Make periodic AJAX requests to fetch product updates
- Update product table rows with new stock quantities
- Show subtle loading indicator during updates
- Highlight changed values briefly
- Handle errors with exponential backoff

**Key Functions**:
```javascript
class ProductListPoller {
    constructor(options) {
        this.pollInterval = options.pollInterval || 5000; // 5 seconds
        this.maxRetries = options.maxRetries || 3;
        this.backoffMultiplier = 2;
        this.maxBackoff = 30000; // 30 seconds
        this.currentBackoff = this.pollInterval;
        this.retryCount = 0;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastUpdateTimestamp = null;
        this.currentFilters = {}; // Track active filters
        this.productTableBody = null; // Reference to table body
    }

    start() { /* Start polling loop */ }
    stop() { /* Stop polling and cleanup */ }
    poll() { /* Make AJAX request */ }
    updateProductTable(data) { /* Update table rows */ }
    highlightChangedValue(element) { /* Briefly highlight changed stock */ }
    updateLastUpdateTime() { /* Update "Last updated" timestamp */ }
    handleError(error) { /* Error handling with backoff */ }
    resetBackoff() { /* Reset backoff on success */ }
}
```

**Visual Feedback Strategy**:
- Small loading indicator in corner during active request
- Highlight changed stock values with brief color flash
- Display "Last updated: X seconds ago" timestamp
- Preserve scroll position and active filters

### 3. Frontend Product List API Endpoint (PHP)

**File**: `frontend/api/get-product-list.php`

**Responsibilities**:
- Fetch current product data for customer-facing dashboard
- Accept category filter parameter
- Return product data with stock quantities and availability
- No authentication required (public endpoint)

**Request Parameters**:
- `category` (string, optional): Filter by category slug
- `since` (timestamp, optional): Only return products updated after this timestamp

**Response Format**:
```json
{
    "success": true,
    "timestamp": "2025-11-07 14:30:00",
    "products": [
        {
            "id": 1,
            "name": "Product Name",
            "price": 150.00,
            "quantity": 45,
            "sameday_stock_today": 10,
            "status_id": 1,
            "status_name": "Regular",
            "availtoday_status_id": 1,
            "is_available": true,
            "has_preorder": true,
            "has_sameday": true,
            "image_url": "https://...",
            "category_id": 2,
            "category_name": "Beverages"
        }
    ]
}
```

### 4. Backend Product List API Endpoint (PHP)

**File**: `backend/api/get-product-list-admin.php`

**Responsibilities**:
- Authenticate admin user
- Fetch current product data for admin product list
- Accept filter parameters (search, category, status)
- Return detailed product data including all stock fields

**Request Parameters**:
- `search` (string, optional): Search by product name
- `category` (int, optional): Filter by category ID
- `status` (int, optional): Filter by status ID
- `page` (int, optional): Pagination page number
- `since` (timestamp, optional): Only return products updated after this timestamp

**Response Format**:
```json
{
    "success": true,
    "timestamp": "2025-11-07 14:30:00",
    "products": [
        {
            "id": 1,
            "name": "Product Name",
            "price": 150.00,
            "quantity": 45,
            "sameday_stock_today": 10,
            "status_id": 1,
            "status_name": "Regular",
            "availtoday_status_id": 1,
            "availtoday_status_name": "Available Today",
            "category_id": 2,
            "category_name": "Beverages",
            "is_featured": 0,
            "show_when_unavailable": 0,
            "hide_when_unavailable": 0,
            "updated_at": "2025-11-07 14:25:00"
        }
    ],
    "total_pages": 5,
    "current_page": 1
}
```

### 5. Frontend Product Dashboard Updates

**File**: `frontend/pages/products/product-dashboard.php`

**Changes**:
- Add polling script include at bottom of page
- Initialize polling on page load
- Pass current category filter to polling client
- Ensure product cards have data attributes for easy updates (data-product-id)

**No Visual Changes**:
- No loading indicators
- No "last updated" timestamp (silent updates)
- No highlight effects

### 6. Backend Product List Updates

**File**: `backend/pages/products/product-list.php`

**Changes**:
- Add polling script include
- Initialize polling on page load
- Pass current filter state to polling client
- Add loading indicator element
- Add "Last updated" timestamp display
- Ensure table rows have data attributes (data-product-id)

## Data Models

### Frontend Product Response Model

```typescript
interface FrontendProductListResponse {
    success: boolean;
    timestamp: string;
    products: FrontendProduct[];
    error?: string;
}

interface FrontendProduct {
    id: number;
    name: string;
    price: number;
    quantity: number;
    sameday_stock_today: number;
    status_id: number;
    status_name: string;
    availtoday_status_id: number | null;
    is_available: boolean;
    has_preorder: boolean;
    has_sameday: boolean;
    image_url: string;
    category_id: number;
    category_name: string;
}
```

### Backend Product Response Model

```typescript
interface BackendProductListResponse {
    success: boolean;
    timestamp: string;
    products: BackendProduct[];
    total_pages: number;
    current_page: number;
    error?: string;
}

interface BackendProduct {
    id: number;
    name: string;
    price: number;
    quantity: number;
    sameday_stock_today: number;
    status_id: number;
    status_name: string;
    availtoday_status_id: number | null;
    availtoday_status_name: string | null;
    category_id: number;
    category_name: string;
    is_featured: number;
    show_when_unavailable: number;
    hide_when_unavailable: number;
    updated_at: string;
}
```

## Error Handling

### Client-Side Error Handling

1. **Network Errors**:
   - Implement exponential backoff (starting at 5s, max 30s)
   - Log errors to console
   - Automatically retry with backoff
   - Reset backoff on successful request

2. **Server Errors (500)**:
   - Log error to console
   - Implement backoff strategy
   - Continue polling after backoff period

3. **Authentication Errors (401/403)** - Backend only:
   - Stop polling immediately
   - Redirect to login page

### Server-Side Error Handling

1. **Database Errors**:
   - Return 500 status code with error message
   - Log error details to PHP error log
   - Return empty products array with error flag

2. **Invalid Parameters**:
   - Return 400 status code with validation errors
   - Provide clear error messages

## UI/UX Considerations

### Frontend Product Dashboard (Silent Updates)

1. **No Visual Indicators**:
   - No loading spinners
   - No "last updated" timestamp
   - No highlight effects
   - Updates happen completely silently

2. **Stock Updates**:
   - Update quantity displays in product cards
   - Update availability status (available/unavailable)
   - Update "Add to Cart" button state if product becomes unavailable
   - Preserve any open modals or user interactions

3. **Scroll Preservation**:
   - Maintain exact scroll position during updates
   - Don't interrupt user browsing

### Backend Product List (Visual Feedback)

1. **Loading Indicator**:
   - Small spinner icon in top-right corner of table
   - Fade in/out animation (300ms)
   - Only visible during active request

2. **Last Update Timestamp**:
   - Display "Last updated: X seconds ago" above table
   - Update every second
   - Format: "Just now", "30 seconds ago", "2 minutes ago"

3. **Stock Change Highlighting**:
   - Changed stock values get brief yellow background
   - Fade-out animation after 2 seconds
   - CSS class: `.stock-updated`

4. **Scroll Preservation**:
   - Maintain current scroll position during refresh
   - Preserve user's viewing context

## Performance Considerations

### Polling Optimization

1. **Conditional Requests**:
   - Send `since` timestamp with each request
   - Server only returns products updated after timestamp
   - Reduces response payload size

2. **Debouncing**:
   - If user changes filters, cancel current poll
   - Wait 500ms before starting new poll
   - Prevents rapid-fire requests

3. **Page Visibility API**:
   - Pause polling when tab is not visible
   - Resume polling when tab becomes visible
   - Reduces unnecessary server load

### Database Optimization

1. **Indexed Queries**:
   - Ensure `updated_at` column exists and is indexed
   - Ensure `category_id` and `status_id` are indexed
   - Use composite indexes for common filter combinations

2. **Query Limits**:
   - Frontend: Return all products (no pagination needed)
   - Backend: Use pagination (default 20 products per page)

## Update Strategy

### Frontend Product Dashboard

**DOM Update Approach**:
- Find product card by `data-product-id` attribute
- Update only changed elements:
  - Stock quantity text
  - Availability status badge
  - "Add to Cart" button state
- Use `textContent` for simple text updates
- Avoid full card replacement to prevent flicker

**Example Update**:
```javascript
updateProductCard(productId, newData) {
    const card = document.querySelector(`[data-product-id="${productId}"]`);
    if (!card) return;
    
    // Update stock
    const stockEl = card.querySelector('.stock');
    if (stockEl) stockEl.textContent = `Stock: ${newData.quantity}`;
    
    // Update availability
    const statusEl = card.querySelector('.status-badge');
    if (statusEl && !newData.is_available) {
        statusEl.textContent = 'Unavailable';
        statusEl.classList.add('status-unavailable');
    }
    
    // Update button
    const btn = card.querySelector('.add-to-cart');
    if (btn && !newData.is_available) {
        btn.disabled = true;
        btn.classList.add('unavailable');
    }
}
```

### Backend Product List

**DOM Update Approach**:
- Find table row by `data-product-id` attribute
- Update stock cells with new values
- Apply highlight class to changed cells
- Remove highlight after 2 seconds

**Example Update**:
```javascript
updateProductRow(productId, newData, oldData) {
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (!row) return;
    
    // Update preorder stock
    const preorderCell = row.querySelector('.preorder-stock');
    if (preorderCell && newData.quantity !== oldData.quantity) {
        preorderCell.textContent = newData.quantity;
        this.highlightChangedValue(preorderCell);
    }
    
    // Update same-day stock
    const samedayCell = row.querySelector('.sameday-stock');
    if (samedayCell && newData.sameday_stock_today !== oldData.sameday_stock_today) {
        samedayCell.textContent = newData.sameday_stock_today;
        this.highlightChangedValue(samedayCell);
    }
}

highlightChangedValue(element) {
    element.classList.add('stock-updated');
    setTimeout(() => {
        element.classList.remove('stock-updated');
    }, 2000);
}
```

## Testing Strategy

### Unit Tests

1. **JavaScript Polling Clients**:
   - Test polling loop initialization
   - Test backoff calculation
   - Test error handling
   - Test DOM updates
   - Test filter preservation

2. **PHP API Endpoints**:
   - Test authentication checks (backend only)
   - Test filter parameter handling
   - Test query construction
   - Test JSON response format
   - Test error responses

### Integration Tests

1. **End-to-End Polling**:
   - Place test order and verify stock updates
   - Test category filter preservation during polling
   - Test pagination during polling (backend)
   - Test multiple simultaneous users

2. **Error Scenarios**:
   - Test network failure handling
   - Test server error handling
   - Test database connection loss

### Manual Testing

1. **User Experience**:
   - Verify silent updates on frontend (no flicker)
   - Verify visual feedback on backend
   - Verify scroll position preservation
   - Verify filter preservation

2. **Performance**:
   - Monitor network traffic
   - Verify polling interval accuracy
   - Check server resource usage
   - Verify database query performance

## Security Considerations

1. **Authentication**:
   - Backend API requires admin session verification
   - Frontend API is public (no authentication)
   - Use CSRF tokens for state-changing operations

2. **Input Validation**:
   - Sanitize all filter parameters
   - Validate timestamp format
   - Prevent SQL injection with prepared statements
   - Limit query result sizes

3. **Rate Limiting**:
   - Consider implementing rate limiting on API endpoints
   - Prevent abuse from rapid polling
   - Log suspicious activity

## Future Enhancements

1. **Differential Updates**:
   - Only send changed products in API response
   - Reduce bandwidth usage
   - Improve performance for large product catalogs

2. **WebSocket Upgrade**:
   - Could add WebSocket support as optional enhancement
   - Use polling as fallback for compatibility
   - Instant updates instead of 5-second delay

3. **Stock Reservation**:
   - Reserve stock when added to cart
   - Release after timeout or checkout
   - Prevent overselling during high traffic
