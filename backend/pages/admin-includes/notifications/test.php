<?php
/**
 * Notification System Test Script
 * 
 * This script tests the notification system functionality.
 * Run this script to create sample notifications and verify everything works.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/notification.php';
require_once __DIR__ . '/notification-integration.php';

// Only allow admin users
if (!isset($_SESSION['admin_user_id'])) {
    die('Admin access required');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Test - NeoCafe Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="notifications.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--gray-50);
        }
        
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .test-header {
            margin-bottom: 30px;
        }
        
        .test-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 10px;
        }
        
        .test-description {
            color: var(--gray-600);
            line-height: 1.5;
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0 0 15px;
        }
        
        .test-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .test-btn {
            padding: 10px 16px;
            background: var(--green-600);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .test-btn:hover {
            background: var(--green-700);
        }
        
        .test-btn.secondary {
            background: var(--gray-600);
        }
        
        .test-btn.secondary:hover {
            background: var(--gray-700);
        }
        
        .test-result {
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .test-result.success {
            background: var(--green-100);
            color: var(--green-800);
            border: 1px solid var(--green-200);
        }
        
        .test-result.error {
            background: var(--red-100);
            color: var(--red-800);
            border: 1px solid var(--red-200);
        }
        
        .navigation-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }
        
        .nav-link {
            color: var(--green-600);
            text-decoration: none;
            font-weight: 500;
        }
        
        .nav-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1 class="test-title">Notification System Test</h1>
            <p class="test-description">
                Use this test page to create sample notifications and verify the notification system is working correctly.
                Check the notification bell in the top-right corner after creating notifications.
            </p>
        </div>

        <div class="test-section">
            <h2 class="section-title">Order Notifications</h2>
            <div class="test-buttons">
                <button class="test-btn" onclick="createNotification('order_new')">New Order</button>
                <button class="test-btn" onclick="createNotification('order_status')">Order Status Update</button>
                <button class="test-btn" onclick="createNotification('order_warning')">Order Warning</button>
            </div>
            <div id="order-result" class="test-result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h2 class="section-title">Bulk Order Notifications</h2>
            <div class="test-buttons">
                <button class="test-btn" onclick="createNotification('bulk_new')">New Bulk Order</button>
                <button class="test-btn" onclick="createNotification('bulk_status')">Bulk Status Update</button>
                <button class="test-btn" onclick="createNotification('bulk_payment')">Bulk Payment</button>
            </div>
            <div id="bulk-result" class="test-result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h2 class="section-title">System Actions</h2>
            <div class="test-buttons">
                <button class="test-btn secondary" onclick="clearAllNotifications()">Clear All Notifications</button>
                <button class="test-btn secondary" onclick="refreshNotifications()">Refresh Notifications</button>
            </div>
            <div id="system-result" class="test-result" style="display: none;"></div>
        </div>

        <div class="navigation-links">
            <a href="all-notifications.php" class="nav-link">View All Notifications</a>
            <a href="javascript:history.back()" class="nav-link">Back to Admin</a>
        </div>
    </div>

    <!-- Include notification system -->
    <script src="notifications.js"></script>
    
    <script>
        async function createNotification(type) {
            const testData = {
                'order_new': {
                    title: 'Order #1001 - New Order Placed',
                    message: 'User @testuser placed an order for pickup today at 2:00 PM',
                    link: '/backend/pages/orders/view-orders.php?order_id=1001'
                },
                'order_status': {
                    title: 'Order #1002 - Status Updated',
                    message: 'User @testuser order status has been updated to Confirmed',
                    link: '/backend/pages/orders/view-orders.php?order_id=1002'
                },
                'order_warning': {
                    title: 'Order #1003 - ⚠️ Delivery Alert',
                    message: '⚠️ Heads up! User @testuser placed an order for delivery tomorrow at 10:00 AM — make sure everything is ready in time.',
                    link: '/backend/pages/orders/view-orders.php?order_id=1003'
                },
                'bulk_new': {
                    title: 'Bulk Order #501 - New Request',
                    message: 'User @testuser submitted a bulk order request for review.',
                    link: '/backend/pages/bulks/bulk-order.php?id=501'
                },
                'bulk_status': {
                    title: 'Bulk Order #502 - Status Updated',
                    message: 'User @testuser bulk order status has been updated to Approved',
                    link: '/backend/pages/bulks/bulk-order.php?id=502'
                },
                'bulk_payment': {
                    title: 'Bulk Order #503 - Payment Submitted',
                    message: 'User @testuser uploaded proof of payment. Please verify the details.',
                    link: '/backend/pages/bulks/bulk-order.php?id=503'
                }
            };

            const data = testData[type];
            if (!data) return;

            try {
                const formData = new FormData();
                formData.append('action', 'create_test');
                formData.append('type', type);
                formData.append('title', data.title);
                formData.append('message', data.message);
                formData.append('link', data.link);

                const response = await fetch('test-api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                const resultDiv = type.startsWith('order') ? 
                    document.getElementById('order-result') : 
                    document.getElementById('bulk-result');
                
                resultDiv.style.display = 'block';
                
                if (result.success) {
                    resultDiv.className = 'test-result success';
                    resultDiv.textContent = `✅ ${type} notification created successfully!`;
                    
                    // Refresh notification system if it exists
                    if (window.notificationSystem) {
                        setTimeout(() => {
                            window.notificationSystem.loadNotifications();
                        }, 100);
                    }
                } else {
                    resultDiv.className = 'test-result error';
                    resultDiv.textContent = `❌ Failed to create notification: ${result.error || 'Unknown error'}`;
                }
            } catch (error) {
                console.error('Error creating notification:', error);
                const resultDiv = type.startsWith('order') ? 
                    document.getElementById('order-result') : 
                    document.getElementById('bulk-result');
                
                resultDiv.style.display = 'block';
                resultDiv.className = 'test-result error';
                resultDiv.textContent = `❌ Error creating notification: ${error.message}`;
            }
        }

        async function clearAllNotifications() {
            if (!confirm('Are you sure you want to delete all notifications? This cannot be undone.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'clear_all');

                const response = await fetch('test-api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                const resultDiv = document.getElementById('system-result');
                resultDiv.style.display = 'block';

                if (result.success) {
                    resultDiv.className = 'test-result success';
                    resultDiv.textContent = `✅ All notifications cleared successfully!`;
                    
                    // Refresh notification system if it exists
                    if (window.notificationSystem) {
                        setTimeout(() => {
                            window.notificationSystem.loadNotifications();
                        }, 100);
                    }
                } else {
                    resultDiv.className = 'test-result error';
                    resultDiv.textContent = `❌ Failed to clear notifications: ${result.error || 'Unknown error'}`;
                }
            } catch (error) {
                console.error('Error clearing notifications:', error);
                const resultDiv = document.getElementById('system-result');
                resultDiv.style.display = 'block';
                resultDiv.className = 'test-result error';
                resultDiv.textContent = `❌ Error clearing notifications: ${error.message}`;
            }
        }

        function refreshNotifications() {
            if (window.notificationSystem) {
                window.notificationSystem.loadNotifications();
                const resultDiv = document.getElementById('system-result');
                resultDiv.style.display = 'block';
                resultDiv.className = 'test-result success';
                resultDiv.textContent = '✅ Notifications refreshed!';
            } else {
                const resultDiv = document.getElementById('system-result');
                resultDiv.style.display = 'block';
                resultDiv.className = 'test-result error';
                resultDiv.textContent = '❌ Notification system not found';
            }
        }
    </script>
</body>
</html>