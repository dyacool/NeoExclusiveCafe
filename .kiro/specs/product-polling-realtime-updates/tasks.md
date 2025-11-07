# Implementation Plan

- [x] 1. Create frontend product list API endpoint


  - Create `frontend/api/get-product-list.php` that returns product data in JSON format
  - Query products table with category filter support
  - Include stock quantities (quantity, sameday_stock_today)
  - Calculate availability flags (is_available, has_preorder, has_sameday)
  - Return JSON response with products array and current timestamp
  - No authentication required (public endpoint)
  - _Requirements: 1.1, 4.1, 4.3, 4.5_




- [ ] 2. Create backend product list API endpoint
  - Create `backend/api/get-product-list-admin.php` that returns product data in JSON format
  - Implement authentication check to ensure only admin users can access
  - Accept filter parameters: search, category, status, page, since_timestamp


  - Query products table with proper filtering and pagination
  - Return JSON response with products array, pagination metadata, and current timestamp
  - _Requirements: 2.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 3. Create frontend product dashboard polling client
  - Create `frontend/assets/js/product-dashboard-polling.js` with ProductDashboardPoller class
  - Implement polling loop that makes AJAX requests every 5 seconds
  - Implement exponential backoff for failed requests (max 30 seconds)
  - Pass current category filter with each request
  - Handle successful responses by updating product cards silently


  - Update stock quantities, availability status, and button states
  - Handle errors gracefully without breaking the polling loop
  - Implement stop() method to cleanup when page unloads
  - NO loading indicators or visual feedback (silent updates)
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.1, 3.2, 3.3, 3.4, 3.5, 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 4. Create backend product list polling client
  - Create `backend/assets/js/product-list-polling.js` with ProductListPoller class
  - Implement polling loop that makes AJAX requests every 5 seconds
  - Implement exponential backoff for failed requests (max 30 seconds)



  - Pass current filter state and last update timestamp with each request
  - Handle successful responses by updating table rows
  - Highlight changed stock values with brief animation
  - Update "Last updated" timestamp display
  - Handle errors gracefully without breaking the polling loop
  - Implement stop() method to cleanup when page unloads


  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 3.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 5. Add CSS for backend product list visual feedback
  - Create `backend/assets/css/product-list-polling.css`
  - Add styles for loading indicator (small spinner in corner)
  - Add styles for "Last updated" timestamp display


  - Add styles for stock change highlight effect (.stock-updated class)
  - Add fade-in/fade-out animations for loading indicator
  - Add 2-second fade-out animation for stock highlights
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 6. Integrate polling into frontend product dashboard
  - Add `<script>` tag to include `product-dashboard-polling.js` in `frontend/pages/products/product-dashboard.php`
  - Initialize ProductDashboardPoller on page load with current category filter
  - Pass initial timestamp to polling client

  - Add `data-product-id` attributes to product cards for easy DOM updates
  - Ensure polling starts after page fully loads
  - _Requirements: 1.1, 1.2, 1.4, 1.5_

- [ ] 7. Integrate polling into backend product list
  - Add `<script>` tag to include `product-list-polling.js` in `backend/pages/products/product-list.php`
  - Add `<link>` tag to include `product-list-polling.css`
  - Initialize ProductListPoller on page load with current filter state
  - Pass initial timestamp to polling client


  - Add loading indicator HTML element
  - Add "Last updated" timestamp HTML element
  - Add `data-product-id` attributes to table rows for easy DOM updates
  - Add CSS classes to stock cells for targeting (.preorder-stock, .sameday-stock)
  - _Requirements: 2.1, 2.2, 2.4, 2.5, 6.1, 6.5_

- [ ] 8. Test frontend product dashboard polling
  - Verify product stock updates automatically when order is placed
  - Verify updates happen silently without loading indicators
  - Verify category filter is preserved during polling updates
  - Verify scroll position is maintained during updates
  - Verify "Add to Cart" button state updates when product becomes unavailable
  - Verify polling stops when navigating away from page
  - Verify error handling with network failures
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.2, 3.3, 3.4, 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 9. Test backend product list polling
  - Verify product stock updates automatically when order is placed
  - Verify loading indicator appears during requests
  - Verify changed stock values are highlighted briefly
  - Verify "Last updated" timestamp updates correctly
  - Verify filters are preserved during polling updates
  - Verify scroll position is maintained during updates
  - Verify polling stops when navigating away from page
  - Verify error handling with network failures
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.2, 3.3, 3.4, 6.1, 6.2, 6.3, 6.4, 6.5_
