<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Polling Test Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .test-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .test-section h2 {
            color: #1a4a28;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .test-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            background: #fafafa;
        }
        
        .test-card h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .test-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .test-card code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
            color: #d63384;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #1a4a28;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            margin-right: 10px;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: #0f3d1a;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            margin-right: 5px;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .api-test {
            background: #f8f9fa;
            border-left: 4px solid #1a4a28;
            padding: 15px;
            margin: 15px 0;
        }
        
        .api-test h4 {
            color: #1a4a28;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .api-test pre {
            background: #fff;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
            border: 1px solid #dee2e6;
        }
        
        .checklist {
            list-style: none;
            padding: 0;
        }
        
        .checklist li {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .checklist li:last-child {
            border-bottom: none;
        }
        
        .checklist li:before {
            content: "☐ ";
            color: #1a4a28;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .implementation-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .status-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .status-icon.complete {
            background: #28a745;
            color: white;
        }
        
        .status-text {
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Product Polling Real-time Updates - Test Page</h1>
        <p class="subtitle">Comprehensive testing guide for the product polling system</p>
        
        <!-- Implementation Status -->
        <div class="test-section">
            <h2>✅ Implementation Status</h2>
            <div class="implementation-status">
                <div class="status-item">
                    <div class="status-icon complete">✓</div>
                    <div class="status-text">Frontend API Endpoint</div>
                </div>
                <div class="status-item">
                    <div class="status-icon complete">✓</div>
                    <div class="status-text">Backend API Endpoint</div>
                </div>
                <div class="status-item">
                    <div class="status-icon complete">✓</div>
                    <div class="status-text">Frontend Polling Client</div>
                </div>
                <div class="status-item">
                    <div class="status-icon complete">✓</div>
                    <div class="status-text">Backend Polling Client</div>
                </div>
                <div class="status-item">
                    <div class="status-icon complete">✓</div>
                    <div class="status-text">Backend CSS Styles</div>
                </div>
                <div class="status-item">
                    <div class="status-icon complete">✓</div>
                    <div class="status-text">Frontend Integration</div>
                </div>
                <div class="status-item">
                    <div class="status-icon complete">✓</div>
                    <div class="status-text">Backend Integration</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="test-section">
            <h2>🔗 Quick Links</h2>
            <a href="/NeoCafe/frontend/pages/products/product-dashboard.php" class="btn" target="_blank">
                Open Frontend Product Dashboard
            </a>
            <a href="/NeoCafe/backend/pages/products/product-list.php" class="btn" target="_blank">
                Open Backend Product List
            </a>
            <a href="/NeoCafe/frontend/api/get-product-list.php" class="btn btn-secondary" target="_blank">
                Test Frontend API
            </a>
            <a href="/NeoCafe/backend/api/get-product-list-admin.php" class="btn btn-secondary" target="_blank">
                Test Backend API
            </a>
        </div>
        
        <!-- API Testing -->
        <div class="test-section">
            <h2>🔌 API Endpoint Testing</h2>
            
            <div class="api-test">
                <h4>Frontend API: <code>/NeoCafe/frontend/api/get-product-list.php</code></h4>
                <p><span class="status-badge status-info">Public</span> No authentication required</p>
                <p><strong>Test URLs:</strong></p>
                <pre>
# All products
/NeoCafe/frontend/api/get-product-list.php

# Filter by category
/NeoCafe/frontend/api/get-product-list.php?category=beverages

# With timestamp (for polling)
/NeoCafe/frontend/api/get-product-list.php?since=2025-11-07%2014:30:00
                </pre>
                <button class="btn btn-secondary" onclick="testFrontendAPI()">Test Frontend API</button>
                <div id="frontend-api-result"></div>
            </div>
            
            <div class="api-test">
                <h4>Backend API: <code>/NeoCafe/backend/api/get-product-list-admin.php</code></h4>
                <p><span class="status-badge status-warning">Protected</span> Requires admin authentication</p>
                <p><strong>Test URLs:</strong></p>
                <pre>
# All products (page 1)
/NeoCafe/backend/api/get-product-list-admin.php?page=1

# Search products
/NeoCafe/backend/api/get-product-list-admin.php?search=coffee

# Filter by category and status
/NeoCafe/backend/api/get-product-list-admin.php?category=1&status=1

# With timestamp (for polling)
/NeoCafe/backend/api/get-product-list-admin.php?since=2025-11-07%2014:30:00
                </pre>
                <button class="btn btn-secondary" onclick="testBackendAPI()">Test Backend API</button>
                <div id="backend-api-result"></div>
            </div>
        </div>
        
        <!-- Frontend Testing -->
        <div class="test-section">
            <h2>🎨 Frontend Product Dashboard Testing</h2>
            <p><span class="status-badge status-success">Silent Updates</span> No loading indicators, seamless experience</p>
            
            <div class="test-grid">
                <div class="test-card">
                    <h3>Test 1: Basic Polling</h3>
                    <p>1. Open the frontend product dashboard</p>
                    <p>2. Open browser console (F12)</p>
                    <p>3. Look for polling logs every 5 seconds</p>
                    <p>4. Verify: <code>[ProductDashboardPoller] Polling:</code></p>
                </div>
                
                <div class="test-card">
                    <h3>Test 2: Stock Updates</h3>
                    <p>1. Note a product's stock quantity</p>
                    <p>2. In another tab, place an order for that product</p>
                    <p>3. Wait up to 5 seconds</p>
                    <p>4. Verify stock decrements silently (no flicker)</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 3: Availability Changes</h3>
                    <p>1. Find a product with low stock</p>
                    <p>2. Order all remaining stock</p>
                    <p>3. Wait up to 5 seconds</p>
                    <p>4. Verify "Add to Cart" button becomes disabled</p>
                    <p>5. Verify status changes to "Unavailable"</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 4: Category Filter</h3>
                    <p>1. Click on a category tab</p>
                    <p>2. Check console for category update log</p>
                    <p>3. Verify polling continues with new category</p>
                    <p>4. Place order and verify updates still work</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 5: Scroll Preservation</h3>
                    <p>1. Scroll down the product list</p>
                    <p>2. Wait for polling update (5 seconds)</p>
                    <p>3. Verify scroll position doesn't jump</p>
                    <p>4. Verify no page flicker or reload</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 6: Page Visibility</h3>
                    <p>1. Switch to another browser tab</p>
                    <p>2. Check console: polling should pause</p>
                    <p>3. Switch back to product dashboard</p>
                    <p>4. Verify polling resumes immediately</p>
                </div>
            </div>
            
            <h3 style="margin-top: 20px;">Expected Console Logs:</h3>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;">
[ProductDashboardPoller] Initialized with options: {...}
[ProductDashboardPoller] Starting polling loop
[ProductDashboardPoller] Stored 12 products
[ProductDashboardPoller] Polling: /NeoCafe/frontend/api/get-product-list.php?since=...
[ProductDashboardPoller] Received data: {success: true, products: [...]}
[ProductDashboardPoller] Product 5: Stock 45 → 44
[ProductDashboardPoller] Updated 1 product(s) silently
            </pre>
        </div>
        
        <!-- Backend Testing -->
        <div class="test-section">
            <h2>⚙️ Backend Product List Testing</h2>
            <p><span class="status-badge status-info">Visual Feedback</span> Loading indicator, highlights, and timestamp</p>
            
            <div class="test-grid">
                <div class="test-card">
                    <h3>Test 1: Loading Indicator</h3>
                    <p>1. Open backend product list</p>
                    <p>2. Watch top-right corner</p>
                    <p>3. Every 5 seconds, green indicator should appear</p>
                    <p>4. Verify: "Updating..." message shows briefly</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 2: Stock Highlights</h3>
                    <p>1. Place an order to decrement stock</p>
                    <p>2. Wait up to 5 seconds</p>
                    <p>3. Verify stock number gets yellow highlight</p>
                    <p>4. Verify highlight fades after 2 seconds</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 3: Last Updated Time</h3>
                    <p>1. Look for "Last updated:" text above table</p>
                    <p>2. Verify it shows "Just now" initially</p>
                    <p>3. Wait and watch it update: "5 seconds ago"</p>
                    <p>4. After polling, it resets to "Just now"</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 4: Dual Stock Display</h3>
                    <p>1. Find product with both preorder & same-day</p>
                    <p>2. Note both stock values</p>
                    <p>3. Place preorder - preorder stock decrements</p>
                    <p>4. Place same-day order - same-day stock decrements</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 5: Filter Preservation</h3>
                    <p>1. Use search to filter products</p>
                    <p>2. Wait for polling update</p>
                    <p>3. Verify search filter remains active</p>
                    <p>4. Verify only filtered products update</p>
                </div>
                
                <div class="test-card">
                    <h3>Test 6: Authentication</h3>
                    <p>1. Log out of admin panel</p>
                    <p>2. Try accessing backend API directly</p>
                    <p>3. Verify 401 Unauthorized response</p>
                    <p>4. Verify polling stops and redirects</p>
                </div>
            </div>
            
            <h3 style="margin-top: 20px;">Expected Console Logs:</h3>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;">
[ProductListPoller] Initialized with options: {...}
[ProductListPoller] Starting polling loop
[ProductListPoller] Stored 20 products
[ProductListPoller] Polling: ../api/get-product-list-admin.php?since=...
[ProductListPoller] Received data: {success: true, products: [...]}
[ProductListPoller] Product 5: Preorder stock 45 → 44
[ProductListPoller] Updated 1 product(s)
            </pre>
        </div>
        
        <!-- Manual Testing Checklist -->
        <div class="test-section">
            <h2>📋 Manual Testing Checklist</h2>
            
            <h3>Frontend Product Dashboard</h3>
            <ul class="checklist">
                <li>Polling starts automatically on page load</li>
                <li>Stock quantities update silently (no loading spinner)</li>
                <li>Availability status updates when stock depletes</li>
                <li>"Add to Cart" button disables when unavailable</li>
                <li>Scroll position preserved during updates</li>
                <li>Category filter preserved during updates</li>
                <li>No page flicker or visual disruption</li>
                <li>Polling pauses when tab is hidden</li>
                <li>Polling resumes when tab becomes visible</li>
                <li>Console shows polling logs every 5 seconds</li>
            </ul>
            
            <h3 style="margin-top: 20px;">Backend Product List</h3>
            <ul class="checklist">
                <li>Polling starts automatically on page load</li>
                <li>Loading indicator appears in top-right corner</li>
                <li>"Last updated" timestamp displays and updates</li>
                <li>Stock numbers highlight yellow when changed</li>
                <li>Highlight fades after 2 seconds</li>
                <li>Both preorder and same-day stock update correctly</li>
                <li>Scroll position preserved during updates</li>
                <li>Search filter preserved during updates</li>
                <li>Polling requires admin authentication</li>
                <li>Console shows polling logs every 5 seconds</li>
            </ul>
        </div>
        
        <!-- Troubleshooting -->
        <div class="test-section">
            <h2>🔧 Troubleshooting</h2>
            
            <div class="test-grid">
                <div class="test-card">
                    <h3>Polling Not Starting</h3>
                    <p><strong>Check:</strong></p>
                    <p>• Browser console for JavaScript errors</p>
                    <p>• Network tab for failed API requests</p>
                    <p>• Verify script files are loaded correctly</p>
                    <p>• Check <code>data-product-id</code> attributes exist</p>
                </div>
                
                <div class="test-card">
                    <h3>Stock Not Updating</h3>
                    <p><strong>Check:</strong></p>
                    <p>• API returns updated stock values</p>
                    <p>• Product IDs match between DOM and API</p>
                    <p>• CSS classes <code>.preorder-stock</code> and <code>.sameday-stock</code> exist</p>
                    <p>• Console logs show "Updated X product(s)"</p>
                </div>
                
                <div class="test-card">
                    <h3>Authentication Errors</h3>
                    <p><strong>Check:</strong></p>
                    <p>• Admin session is active</p>
                    <p>• Session cookies are not blocked</p>
                    <p>• Backend API returns 200 status</p>
                    <p>• Check PHP error logs for session issues</p>
                </div>
                
                <div class="test-card">
                    <h3>Performance Issues</h3>
                    <p><strong>Check:</strong></p>
                    <p>• Polling interval (default 5 seconds)</p>
                    <p>• Database query performance</p>
                    <p>• Number of products being polled</p>
                    <p>• Network latency in browser DevTools</p>
                </div>
            </div>
        </div>
        
        <!-- Files Created -->
        <div class="test-section">
            <h2>📁 Files Created/Modified</h2>
            
            <h3>New Files:</h3>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
frontend/api/get-product-list.php
frontend/assets/js/product-dashboard-polling.js
backend/api/get-product-list-admin.php
backend/assets/js/product-list-polling.js
backend/assets/css/product-list-polling.css
            </pre>
            
            <h3>Modified Files:</h3>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
frontend/pages/products/product-dashboard.php
  - Added data-product-id attribute to product cards
  - Added polling script include
  - Added polling initialization code

backend/pages/products/product-list.php
  - Added data-product-id attribute to table rows
  - Added CSS classes to stock cells (.preorder-stock, .sameday-stock)
  - Added loading indicator HTML
  - Added last update timestamp HTML
  - Added polling script and CSS includes
  - Added polling initialization code
            </pre>
        </div>
    </div>
    
    <script>
        async function testFrontendAPI() {
            const resultDiv = document.getElementById('frontend-api-result');
            resultDiv.innerHTML = '<p style="color: #666; margin-top: 10px;">Testing...</p>';
            
            try {
                const response = await fetch('/NeoCafe/frontend/api/get-product-list.php');
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <pre style="background: #fff; padding: 10px; margin-top: 10px; border-radius: 4px; border: 1px solid #dee2e6; max-height: 300px; overflow: auto;">
${JSON.stringify(data, null, 2)}
                    </pre>
                `;
            } catch (error) {
                resultDiv.innerHTML = `<p style="color: #dc3545; margin-top: 10px;">Error: ${error.message}</p>`;
            }
        }
        
        async function testBackendAPI() {
            const resultDiv = document.getElementById('backend-api-result');
            resultDiv.innerHTML = '<p style="color: #666; margin-top: 10px;">Testing...</p>';
            
            try {
                const response = await fetch('/NeoCafe/backend/api/get-product-list-admin.php?page=1');
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <pre style="background: #fff; padding: 10px; margin-top: 10px; border-radius: 4px; border: 1px solid #dee2e6; max-height: 300px; overflow: auto;">
${JSON.stringify(data, null, 2)}
                    </pre>
                `;
            } catch (error) {
                resultDiv.innerHTML = `<p style="color: #dc3545; margin-top: 10px;">Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>
