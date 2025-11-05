<?php
/**
 * Test script for Notifications API
 * 
 * Tests the notification CRUD operations
 * Access via: http://localhost/backend/api/test-notifications-api.php
 */

session_start();

// Simulate admin login for testing (remove in production)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Admin user
    $_SESSION['user_role'] = 'admin';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #333;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            background: #007bff;
            color: white;
        }
        button:hover {
            opacity: 0.9;
        }
        button.danger {
            background: #dc3545;
        }
        button.success {
            background: #28a745;
        }
        .result {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #007bff;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .result.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .result.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        input, select, textarea {
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            width: 100%;
            min-height: 80px;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>📬 Notifications API Test</h1>
    
    <div class="container">
        <h2>1. Create Notification</h2>
        <label>User ID:</label>
        <input type="number" id="createUserId" value="1" min="1">
        
        <label>Message:</label>
        <textarea id="createMessage">Your order #123 is ready for pickup!</textarea>
        
        <label>Type:</label>
        <select id="createType">
            <option value="info">Info</option>
            <option value="success" selected>Success</option>
            <option value="warning">Warning</option>
            <option value="error">Error</option>
        </select>
        
        <br><br>
        <button onclick="createNotification()">Create Notification</button>
        <div id="createResult"></div>
    </div>
    
    <div class="container">
        <h2>2. Get Notifications</h2>
        <label>Limit:</label>
        <input type="number" id="getLimit" value="10" min="1" max="100">
        
        <label>
            <input type="checkbox" id="getUnreadOnly"> Unread Only
        </label>
        
        <br><br>
        <button onclick="getNotifications()">Get Notifications</button>
        <div id="getResult"></div>
    </div>
    
    <div class="container">
        <h2>3. Mark as Read</h2>
        <label>Notification ID (comma-separated for multiple):</label>
        <input type="text" id="markIds" placeholder="1,2,3">
        
        <br><br>
        <button onclick="markAsRead()">Mark as Read</button>
        <button onclick="markAllAsRead()" class="success">Mark All as Read</button>
        <div id="markResult"></div>
    </div>
    
    <div class="container">
        <h2>4. Quick Tests</h2>
        <button onclick="runFullTest()" class="success">Run Full Test Suite</button>
        <button onclick="createTestNotifications()" class="success">Create 5 Test Notifications</button>
        <div id="testResult"></div>
    </div>

    <script>
        function showResult(elementId, data, isError = false) {
            const el = document.getElementById(elementId);
            el.className = 'result ' + (isError ? 'error' : 'success');
            el.textContent = JSON.stringify(data, null, 2);
        }

        async function createNotification() {
            const userId = document.getElementById('createUserId').value;
            const message = document.getElementById('createMessage').value;
            const type = document.getElementById('createType').value;
            
            try {
                const response = await fetch('/backend/api/create-notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: parseInt(userId), message, type })
                });
                
                const data = await response.json();
                showResult('createResult', data, !data.success);
            } catch (error) {
                showResult('createResult', { error: error.message }, true);
            }
        }

        async function getNotifications() {
            const limit = document.getElementById('getLimit').value;
            const unreadOnly = document.getElementById('getUnreadOnly').checked;
            
            try {
                const url = `/backend/api/get-notifications.php?limit=${limit}&unread_only=${unreadOnly}`;
                const response = await fetch(url);
                const data = await response.json();
                showResult('getResult', data, !data.success);
            } catch (error) {
                showResult('getResult', { error: error.message }, true);
            }
        }

        async function markAsRead() {
            const idsInput = document.getElementById('markIds').value;
            const ids = idsInput.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
            
            if (ids.length === 0) {
                showResult('markResult', { error: 'Please enter valid notification IDs' }, true);
                return;
            }
            
            try {
                const response = await fetch('/backend/api/mark-notification-read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: ids })
                });
                
                const data = await response.json();
                showResult('markResult', data, !data.success);
            } catch (error) {
                showResult('markResult', { error: error.message }, true);
            }
        }

        async function markAllAsRead() {
            try {
                const response = await fetch('/backend/api/mark-notification-read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mark_all: true })
                });
                
                const data = await response.json();
                showResult('markResult', data, !data.success);
            } catch (error) {
                showResult('markResult', { error: error.message }, true);
            }
        }

        async function createTestNotifications() {
            const messages = [
                { message: 'Your order #101 has been confirmed', type: 'success' },
                { message: 'Payment received for order #102', type: 'success' },
                { message: 'Your order #103 is out for delivery', type: 'info' },
                { message: 'Low stock alert: Coffee Beans', type: 'warning' },
                { message: 'Your order #104 is ready for pickup', type: 'success' }
            ];
            
            let results = [];
            
            for (const msg of messages) {
                try {
                    const response = await fetch('/backend/api/create-notification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: 1, ...msg })
                    });
                    const data = await response.json();
                    results.push(data);
                } catch (error) {
                    results.push({ error: error.message });
                }
            }
            
            showResult('testResult', { created: results.length, results }, false);
        }

        async function runFullTest() {
            const results = {
                tests: [],
                passed: 0,
                failed: 0
            };
            
            // Test 1: Create notification
            try {
                const createResp = await fetch('/backend/api/create-notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        user_id: 1, 
                        message: 'Test notification', 
                        type: 'info' 
                    })
                });
                const createData = await createResp.json();
                results.tests.push({ 
                    name: 'Create Notification', 
                    status: createData.success ? 'PASS' : 'FAIL',
                    data: createData
                });
                if (createData.success) results.passed++; else results.failed++;
            } catch (error) {
                results.tests.push({ name: 'Create Notification', status: 'FAIL', error: error.message });
                results.failed++;
            }
            
            // Test 2: Get notifications
            try {
                const getResp = await fetch('/backend/api/get-notifications.php?limit=10');
                const getData = await getResp.json();
                results.tests.push({ 
                    name: 'Get Notifications', 
                    status: getData.success ? 'PASS' : 'FAIL',
                    count: getData.notifications?.length || 0
                });
                if (getData.success) results.passed++; else results.failed++;
            } catch (error) {
                results.tests.push({ name: 'Get Notifications', status: 'FAIL', error: error.message });
                results.failed++;
            }
            
            // Test 3: Mark all as read
            try {
                const markResp = await fetch('/backend/api/mark-notification-read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mark_all: true })
                });
                const markData = await markResp.json();
                results.tests.push({ 
                    name: 'Mark All as Read', 
                    status: markData.success ? 'PASS' : 'FAIL',
                    marked: markData.marked_count || 0
                });
                if (markData.success) results.passed++; else results.failed++;
            } catch (error) {
                results.tests.push({ name: 'Mark All as Read', status: 'FAIL', error: error.message });
                results.failed++;
            }
            
            results.summary = `${results.passed}/${results.tests.length} tests passed`;
            showResult('testResult', results, results.failed > 0);
        }
    </script>
</body>
</html>
