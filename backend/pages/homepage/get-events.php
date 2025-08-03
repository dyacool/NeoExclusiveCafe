<?php
require_once __DIR__ . "/../../login/admin/admin-auth.php";
require_once "../includes/database.php";

header('Content-Type: application/json');

try {
    // Get all orders with their details
    $query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.status != 'Cancelled' 
              ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll();

    $events = [];
    foreach ($orders as $order) {
        // Determine the date to use (pickup or delivery)
        $date = $order['pickup_date'] ?? $order['delivery_date'] ?? $order['created_at'];
        
        // Format the date for FullCalendar
        $date = date('Y-m-d', strtotime($date));
        
        // Determine event color based on status
        $color = '#4CAF50'; // Default green
        switch ($order['status']) {
            case 'Pending':
                $color = '#FFA500'; // Orange
                break;
            case 'Processing':
                $color = '#2196F3'; // Blue
                break;
            case 'Completed':
                $color = '#4CAF50'; // Green
                break;
            case 'Cancelled':
                $color = '#f44336'; // Red
                break;
        }

        // Create event object
        $event = [
            'id' => $order['id'],
            'title' => "Order #" . $order['id'] . " - " . $order['customer_name'],
            'start' => $date,
            'color' => $color,
            'extendedProps' => [
                'customer' => $order['customer_name'],
                'email' => $order['customer_email'],
                'phone' => $order['customer_phone'],
                'status' => $order['status'],
                'total' => $order['total_amount']
            ]
        ];

        $events[] = $event;
    }

    echo json_encode($events);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 