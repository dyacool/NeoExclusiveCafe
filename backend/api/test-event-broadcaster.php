<?php
/**
 * Test script for EventBroadcaster functionality
 * 
 * Run this script to verify the event broadcaster is working correctly
 * Access via: http://localhost/backend/api/test-event-broadcaster.php
 */

require_once 'event-broadcaster.php';

header('Content-Type: application/json');

$results = [
    'success' => true,
    'tests' => [],
    'errors' => []
];

// Clear queue before testing
EventQueue::clearQueue();

// Test 1: Check availability
$results['tests'][] = [
    'name' => 'Check Broadcaster Availability',
    'status' => EventBroadcaster::isAvailable() ? 'PASS' : 'FAIL'
];

// Test 2: Broadcast order status update
$eventId1 = EventBroadcaster::broadcastOrderStatus(
    123,                    // order_id
    'Ready for Delivery',   // status
    45                      // customer_id
);

$results['tests'][] = [
    'name' => 'Broadcast Order Status',
    'status' => $eventId1 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId1
];

// Test 3: Broadcast new order to admins
$eventId2 = EventBroadcaster::broadcastNewOrder(
    124,                // order_id
    'John Doe',         // customer_name
    'delivery',         // order_type
    1250.00             // total
);

$results['tests'][] = [
    'name' => 'Broadcast New Order (to admins)',
    'status' => $eventId2 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId2
];

// Test 4: Broadcast product inventory update
$eventId3 = EventBroadcaster::broadcastProductInventory(
    67,             // product_id
    15,             // quantity
    'Coffee Beans'  // product_name
);

$results['tests'][] = [
    'name' => 'Broadcast Product Inventory',
    'status' => $eventId3 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId3
];

// Test 5: Send notification to user
$eventId4 = EventBroadcaster::sendNotification(
    45,                             // user_id
    'Your order is ready!',         // message
    'success',                      // type
    789                             // notification_id
);

$results['tests'][] = [
    'name' => 'Send User Notification',
    'status' => $eventId4 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId4
];

// Test 6: Broadcast delivery assignment to rider
$eventId5 = EventBroadcaster::broadcastDeliveryAssignment(
    99,                             // rider_id
    125,                            // order_id
    '123 Main St, City',            // customer_address
    '2025-11-05 15:00:00'          // delivery_time
);

$results['tests'][] = [
    'name' => 'Broadcast Delivery Assignment',
    'status' => $eventId5 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId5
];

// Test 7: Broadcast to specific user
$eventId6 = EventBroadcaster::broadcastToUser(
    50,
    'notification',
    ['message' => 'Test notification', 'type' => 'info']
);

$results['tests'][] = [
    'name' => 'Broadcast to Specific User',
    'status' => $eventId6 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId6
];

// Test 8: Broadcast to role
$eventId7 = EventBroadcaster::broadcastToRole(
    'admin',
    'notification',
    ['message' => 'Admin notification', 'type' => 'warning']
);

$results['tests'][] = [
    'name' => 'Broadcast to Role',
    'status' => $eventId7 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId7
];

// Test 9: Invalid channel (should fail)
$eventId8 = EventBroadcaster::broadcast(
    'invalid_channel',
    ['test' => 'data']
);

$results['tests'][] = [
    'name' => 'Invalid Channel (should fail)',
    'status' => $eventId8 === false ? 'PASS' : 'FAIL',
    'event_id' => $eventId8
];

// Test 10: Empty data (should fail)
$eventId9 = EventBroadcaster::broadcast(
    'notification',
    []
);

$results['tests'][] = [
    'name' => 'Empty Data (should fail)',
    'status' => $eventId9 === false ? 'PASS' : 'FAIL',
    'event_id' => $eventId9
];

// Verify events were added to queue
$allEvents = EventQueue::getEvents();
$results['tests'][] = [
    'name' => 'Verify Events in Queue',
    'status' => count($allEvents) === 7 ? 'PASS' : 'FAIL', // 7 successful events
    'count' => count($allEvents)
];

// Test filtering by channel
$orderEvents = EventQueue::getEvents(['order_status']);
$results['tests'][] = [
    'name' => 'Filter Events by Channel',
    'status' => count($orderEvents) === 1 ? 'PASS' : 'FAIL',
    'count' => count($orderEvents)
];

// Test filtering by user_id
$userFilteredEvents = array_filter($allEvents, function($event) {
    return isset($event['filters']['user_id']) && $event['filters']['user_id'] === 45;
});
$results['tests'][] = [
    'name' => 'Filter Events by User ID',
    'status' => count($userFilteredEvents) === 2 ? 'PASS' : 'FAIL', // order_status and notification
    'count' => count($userFilteredEvents)
];

// Test filtering by role
$roleFilteredEvents = array_filter($allEvents, function($event) {
    return isset($event['filters']['role']) && $event['filters']['role'] === 'admin';
});
$results['tests'][] = [
    'name' => 'Filter Events by Role',
    'status' => count($roleFilteredEvents) === 2 ? 'PASS' : 'FAIL', // new_order and admin notification
    'count' => count($roleFilteredEvents)
];

// Display sample events
$results['sample_events'] = $allEvents;

// Check for any failures
foreach ($results['tests'] as $test) {
    if ($test['status'] === 'FAIL') {
        $results['success'] = false;
        $results['errors'][] = $test['name'] . ' failed';
    }
}

// Summary
$passCount = count(array_filter($results['tests'], function($t) { return $t['status'] === 'PASS'; }));
$totalCount = count($results['tests']);

$results['summary'] = [
    'total_tests' => $totalCount,
    'passed' => $passCount,
    'failed' => $totalCount - $passCount,
    'success_rate' => round(($passCount / $totalCount) * 100, 2) . '%'
];

echo json_encode($results, JSON_PRETTY_PRINT);
