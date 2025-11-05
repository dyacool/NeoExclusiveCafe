<?php
/**
 * SSE Event Stream Server
 * 
 * Maintains persistent HTTP connections with clients and streams realtime events
 * Uses Server-Sent Events (SSE) protocol
 */

// Prevent any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Set error reporting but don't display errors (they break SSE)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set execution time to unlimited for persistent connection
set_time_limit(0);

// Start session
session_start();

// Validate authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get user info
$userId = intval($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'user';

// Get requested channels (comma-separated)
$requestedChannels = isset($_GET['channels']) ? explode(',', $_GET['channels']) : ['notifications'];
$requestedChannels = array_map('trim', $requestedChannels);

// Validate channels
$validChannels = ['order_status', 'product_inventory', 'new_order', 'notification', 'delivery_assignment'];
$channels = array_intersect($requestedChannels, $validChannels);

if (empty($channels)) {
    $channels = ['notifications']; // Default channel
}

error_log("[SSE] User $userId (role: $userRole) connected to channels: " . implode(', ', $channels));

// Set SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Disable Apache output compression
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}

// Send initial connection success event
echo "event: connected\n";
echo "data: " . json_encode(['status' => 'connected', 'timestamp' => date('Y-m-d H:i:s')]) . "\n\n";
flush();

// Load event queue
require_once __DIR__ . '/event-queue.php';

// Track last event ID to avoid duplicates
$lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? intval($_SERVER['HTTP_LAST_EVENT_ID']) : 0;

// Connection tracking
$startTime = time();
$lastKeepalive = time();
$connectionTimeout = 300; // 5 minutes
$keepaliveInterval = 30; // 30 seconds

// Main event loop
while (true) {
    // Check connection timeout
    if (time() - $startTime > $connectionTimeout) {
        error_log("[SSE] Connection timeout for user $userId");
        echo "event: timeout\n";
        echo "data: " . json_encode(['message' => 'Connection timeout']) . "\n\n";
        flush();
        break;
    }
    
    // Check if client disconnected
    if (connection_aborted()) {
        error_log("[SSE] Client disconnected: user $userId");
        break;
    }
    
    // Get new events from queue
    $events = EventQueue::getEvents($channels, $lastEventId);
    
    // Process and send events
    foreach ($events as $event) {
        // Check if user is authorized to receive this event
        if (canUserReceiveEvent($userId, $userRole, $event)) {
            // Send event to client
            echo "id: {$event['id']}\n";
            echo "event: {$event['channel']}\n";
            echo "data: " . json_encode($event['data']) . "\n\n";
            flush();
            
            $lastEventId = $event['id'];
            error_log("[SSE] Sent event {$event['id']} ({$event['channel']}) to user $userId");
        }
    }
    
    // Send keepalive every 30 seconds
    if (time() - $lastKeepalive >= $keepaliveInterval) {
        echo "event: keepalive\n";
        echo "data: " . json_encode(['timestamp' => date('Y-m-d H:i:s')]) . "\n\n";
        flush();
        $lastKeepalive = time();
    }
    
    // Sleep for 1 second before next poll
    sleep(1);
}

// Connection closed
error_log("[SSE] Connection closed for user $userId");

/**
 * Check if user is authorized to receive an event
 * 
 * @param int $userId User ID
 * @param string $userRole User role
 * @param array $event Event data
 * @return bool True if user can receive event
 */
function canUserReceiveEvent($userId, $userRole, $event) {
    $filters = $event['filters'] ?? [];
    
    // If no filters, event is public (broadcast to all)
    if (empty($filters)) {
        return true;
    }
    
    // Check user_id filter
    if (isset($filters['user_id'])) {
        if ($filters['user_id'] === $userId) {
            return true;
        }
        // If user_id filter exists but doesn't match, deny
        return false;
    }
    
    // Check role filter
    if (isset($filters['role'])) {
        if ($filters['role'] === $userRole) {
            return true;
        }
        // If role filter exists but doesn't match, deny
        return false;
    }
    
    // No matching filters
    return false;
}
