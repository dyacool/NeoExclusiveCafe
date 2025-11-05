<?php
/**
 * Test script for EventQueue functionality
 * 
 * Run this script to verify the event queue is working correctly
 * Access via: http://localhost/backend/api/test-event-queue.php
 */

require_once 'event-queue.php';

header('Content-Type: application/json');

$results = [
    'success' => true,
    'tests' => [],
    'errors' => []
];

// Test 1: Initialize queue
$results['tests'][] = [
    'name' => 'Initialize Queue',
    'status' => EventQueue::init() ? 'PASS' : 'FAIL'
];

// Test 2: Add event
$eventId1 = EventQueue::addEvent('order_status', [
    'order_id' => 123,
    'status' => 'Ready for Delivery',
    'customer_id' => 45
], ['user_id' => 45]);

$results['tests'][] = [
    'name' => 'Add Event 1',
    'status' => $eventId1 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId1
];

// Test 3: Add another event
$eventId2 = EventQueue::addEvent('product_inventory', [
    'product_id' => 67,
    'quantity' => 15,
    'available' => true,
    'product_name' => 'Coffee Beans'
]);

$results['tests'][] = [
    'name' => 'Add Event 2',
    'status' => $eventId2 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId2
];

// Test 4: Add event for admin role
$eventId3 = EventQueue::addEvent('new_order', [
    'order_id' => 124,
    'customer_name' => 'John Doe',
    'total' => 1250.00
], ['role' => 'admin']);

$results['tests'][] = [
    'name' => 'Add Event 3 (Admin)',
    'status' => $eventId3 !== false ? 'PASS' : 'FAIL',
    'event_id' => $eventId3
];

// Test 5: Get all events
$allEvents = EventQueue::getEvents();
$results['tests'][] = [
    'name' => 'Get All Events',
    'status' => count($allEvents) === 3 ? 'PASS' : 'FAIL',
    'count' => count($allEvents)
];

// Test 6: Get events by channel
$orderEvents = EventQueue::getEvents(['order_status']);
$results['tests'][] = [
    'name' => 'Get Events by Channel (order_status)',
    'status' => count($orderEvents) === 1 ? 'PASS' : 'FAIL',
    'count' => count($orderEvents)
];

// Test 7: Get events since ID
$recentEvents = EventQueue::getEvents([], $eventId1);
$results['tests'][] = [
    'name' => 'Get Events Since ID',
    'status' => count($recentEvents) === 2 ? 'PASS' : 'FAIL',
    'count' => count($recentEvents)
];

// Test 8: Get last event ID
$lastId = EventQueue::getLastEventId();
$results['tests'][] = [
    'name' => 'Get Last Event ID',
    'status' => $lastId === $eventId3 ? 'PASS' : 'FAIL',
    'last_id' => $lastId
];

// Test 9: Multiple channels filter
$multiChannelEvents = EventQueue::getEvents(['order_status', 'new_order']);
$results['tests'][] = [
    'name' => 'Get Events by Multiple Channels',
    'status' => count($multiChannelEvents) === 2 ? 'PASS' : 'FAIL',
    'count' => count($multiChannelEvents)
];

// Display sample events
$results['sample_events'] = EventQueue::getEvents();

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
