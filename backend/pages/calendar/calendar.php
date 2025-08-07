<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Fetch statistics
$stats = [];

// Total Users
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = $conn->query($users_query);
$stats['total_users'] = $users_result->fetch_assoc()['total_users'];

// Total Income
$income_query = "SELECT COALESCE(SUM(total_amount), 0) as total_income FROM orders WHERE status IN ('Delivered', 'Picked-up')";
$income_result = $conn->query($income_query);
$stats['total_income'] = $income_result->fetch_assoc()['total_income'];

// Total Orders
$orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$orders_result = $conn->query($orders_query);
$stats['total_orders'] = $orders_result->fetch_assoc()['total_orders'];

// Orders in Progress
$progress_query = "SELECT COUNT(*) as in_progress FROM orders WHERE status NOT IN ('Completed', 'Delivered', 'Picked-up')";
$progress_result = $conn->query($progress_query);
$stats['in_progress'] = $progress_result->fetch_assoc()['in_progress'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <!-- CSS files -->
    <link rel="stylesheet" href="../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="../admin-includes/navbar/reset.css">
    <link rel="stylesheet" href="../admin-includes/navbar/admin-navigation.css">
    <link rel="stylesheet" href="calendar.css">
    <link rel="stylesheet" href="../counts.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Spectral", serif !important;
            background-color: #f5f5f5;
        }

        .Main {
            margin-left: 80px; 
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
            background-color: #f5f5f5;
            padding: 0;
        }

        .sidebar:not(.collapsed) ~ .Main {
            margin-left: 250px; 
        }

        .main-container {
            display: flex;
            flex-direction: column;
            padding: 20px;
            margin-top: 20px; 
        }

        .container2{
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 20px;
        }

        /* Date Limit Controls */
        .date-limit-controls {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }

        .limit-input-group {
            display: flex;
            gap: 10px;
        }

        .limit-input {
            padding: 0 8px;
            border: 1px solid #ddd;
            height: 40px;
            border-radius: 4px;
            width: 100px;
        }

        .not-accepting-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Not Accepting Orders Overlay */
        .not-accepting-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            color: #d32f2f;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .Main {
                margin-left: 0;
            }

            .sidebar:not(.collapsed) ~ .Main {
                margin-left: 0;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 20px;
            }

            .order-limit-control {
                width: 100%;
                justify-content: center;
            }

            .calendar-controls {
                flex-direction: column;
            }

            .calendar-btn {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .order-details-grid {
                grid-template-columns: 1fr;
            }

            .items-table {
                font-size: 0.9em;
            }

            .confirmation-content {
                width: 95%;
                margin: 20% auto;
            }
        }

        .modal-content {
            position: relative;
            padding-bottom: 70px; /* Make room for footer */
        }

        .confirmation-modal .modal-content {
            padding-bottom: 20px;
        }

        .confirmation-modal .modal-footer {
            position: static;
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .dashboard-flex {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        @media (max-width: 1100px) {
            .dashboard-flex {
                flex-direction: column;
            }
            .dashboard-right, .dashboard-left {
                width: 100%;
                min-width: 0;
            }
        }

        @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
        }

        .fade-in {
        opacity: 0;
        animation: fadeIn 1.5s ease forwards;
        }

        /* Calendar Styles */
        .calendar-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-limit-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-limit-control h3 {
            margin: 0;
            font-size: 16px;
        }

        .order-limit-control input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 80px;
        }

        .order-limit-control button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .calendar-controls {
            display: flex;
            gap: 10px;
        }

        .calendar-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .calendar-container {
            margin-top: 20px;
        }

        /* Modal Styles */
        .order-details-modal, .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content, .modal-content2 {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close-btnn {
            text-align: right;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .order-details-section h3 {
            margin-top: 0;
            color: #2f603c;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .total-label {
            font-weight: bold;
            text-align: right;
        }

        .total-value {
            font-weight: bold;
        }

        .modal-footer {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .complete-btn, .confirm-btn, .cancel-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .complete-btn, .confirm-btn {
            background: #4CAF50;
            color: white;
        }

        .cancel-btn {
            background: #f44336;
            color: white;
        }

        .confirmation-contents {
            text-align: center;
            margin-bottom: 20px;
        }

        .confirmation-contents h3 {
            color: #2f603c;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include '../admin-includes/navbar/navbar.php'; ?>

    <div class="main-container dashboard-flex fade-in">
        <div class="container1">
            <div class="content bg-teal">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['total_users']); ?></h2>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="content bg-orange">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2>₱<?php echo number_format($stats['total_income'], 2); ?></h2>
                    <p>Net Income</p>
                </div>
            </div>

            <div class="content bg-purple">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['total_orders']); ?></h2>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="content bg-blue">
                <div class="container1-icon ibg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['in_progress']); ?></h2>
                    <p>Orders in Progress</p>
                </div>
            </div>
        </div>

        <div class="container2">
            <div class="dashboard-left">
                <div class="dashboard-header">
                    <h1>Calendar Management</h1>
                </div>
                <div class="dashboard-content">
                    <!-- Calendar Section -->
                    <div class="calendar-section">
                        <div class="top-controls">
                            <div class="order-limit-control">
                                <h3>Daily Order Limit:</h3>
                                <input type="number" id="dailyLimit" min="1">
                                <button onclick="updateDailyLimit()">Save</button>
                                <button onclick="toggleCompletedOrders()" id="toggleCompletedBtn">Show Completed Orders</button>
                            </div>
                        </div>
                        <div class="calendar-container">
                            <div id="calendar">
                                <div class="header">
                                    <button id="prev">←</button>
                                    <span id="monthYear"></span>
                                    <button id="next">→</button>
                                </div>
                                <div class="days"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="order-details-modal" id="orderModal">
        <div class="modal-content">
            <div class="close-btnn">
                <span class="close">&times;</span>
            </div>
            <div id="orderInfo"></div>
            <div class="modal-footer">
                <button id="completeOrderBtn" class="complete-btn" style="display: none;">
                    Mark Order as Complete
                </button>
            </div>
        </div>
    </div>

    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content2">
            <div class="close-btnn">
                <span class="close ver2">&times;</span>
            </div>
            <div class="confirmation-contents">
                <h3>Complete Order</h3>
                <p>Are you sure you want to mark this order as completed?</p>
            </div>
            <div class="modal-footer">
                <button id="confirmComplete" class="confirm-btn">Yes</button>
                <button id="cancelComplete" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script src="calendar.js"></script>
</body>
</html>