<?php
require_once __DIR__ . "/../../../includes/session-manager.php";
require_once "../../php/includes/database.php";

if (!SessionManager::isAdminLoggedIn()) {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/database.php";

// Get start and end date from FullCalendar request
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-t');

// Query for confirmed orders within date range
$sql = "SELECT * FROM orders WHERE status = 'Confirmed' AND 
       ((delivery_method = 'Delivery' AND delivery_date BETWEEN ? AND ?) OR 
        (delivery_method = 'Pickup' AND pickup_date BETWEEN ? AND ?))";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $start, $end, $start, $end);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$events = [];

// Format orders as calendar events
while ($row = mysqli_fetch_assoc($result)) {
    $date = ($row['delivery_method'] == 'Delivery') ? $row['delivery_date'] : $row['pickup_date'];
    $time = ($row['delivery_method'] == 'Delivery') ? $row['delivery_time'] : $row['pickup_time'];
    
    // Create date string
    $startDateTime = $date . ' ' . $time;
    
    // Default event duration is 30 minutes
    $endDateTime = date('Y-m-d H:i:s', strtotime($startDateTime) + 1800);
    
    $events[] = [
        'id' => $row['order_id'],
        'title' => 'Order #' . $row['order_id'],
        'start' => $startDateTime,
        'end' => $endDateTime,
        'extendedProps' => [
            'type' => $row['delivery_method'],
            'customer' => $row['customer_name'],
            'contact' => $row['customer_contact'],
            'time' => $time,
            'totalAmount' => $row['total_amount'],
            'items' => $row['total_items']
        ],
        'backgroundColor' => ($row['delivery_method'] == 'Delivery') ? '#4CAF50' : '#2196F3',
        'borderColor' => ($row['delivery_method'] == 'Delivery') ? '#388E3C' : '#1976D2'
    ];
}

// Return events as JSON
header('Content-Type: application/json');
echo json_encode($events);