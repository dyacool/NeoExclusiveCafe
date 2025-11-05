<?php
/**
 * Event Queue System
 * 
 * File-based queue for storing and retrieving realtime notification events
 * Provides thread-safe operations with file locking
 */

class EventQueue {
    private static $queueDir = __DIR__ . '/events/';
    private static $queueFile = 'queue.json';
    private static $lockTimeout = 5; // seconds
    private static $eventTTL = 3600; // 1 hour in seconds
    
    /**
     * Initialize the queue directory and file
     */
    public static function init() {
        // Create events directory if it doesn't exist
        if (!file_exists(self::$queueDir)) {
            if (!mkdir(self::$queueDir, 0755, true)) {
                error_log("[EventQueue] Failed to create events directory: " . self::$queueDir);
                return false;
            }
        }
        
        // Create queue file if it doesn't exist
        $queuePath = self::$queueDir . self::$queueFile;
        if (!file_exists($queuePath)) {
            $initialData = [
                'events' => [],
                'last_id' => 0
            ];
            if (file_put_contents($queuePath, json_encode($initialData, JSON_PRETTY_PRINT)) === false) {
                error_log("[EventQueue] Failed to create queue file: " . $queuePath);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Add an event to the queue
     * 
     * @param string $channel Event channel (order_status, product_inventory, etc.)
     * @param array $data Event payload
     * @param array $filters Optional filters (user_id, role, etc.)
     * @return int|false Event ID on success, false on failure
     */
    public static function addEvent($channel, $data, $filters = []) {
        self::init();
        
        $queuePath = self::$queueDir . self::$queueFile;
        $lockFile = $queuePath . '.lock';
        
        // Acquire lock with timeout
        $lockHandle = fopen($lockFile, 'c');
        if (!$lockHandle) {
            error_log("[EventQueue] Failed to open lock file");
            return false;
        }
        
        $lockAcquired = false;
        $startTime = time();
        
        while (!$lockAcquired && (time() - $startTime) < self::$lockTimeout) {
            $lockAcquired = flock($lockHandle, LOCK_EX | LOCK_NB);
            if (!$lockAcquired) {
                usleep(100000); // 100ms
            }
        }
        
        if (!$lockAcquired) {
            error_log("[EventQueue] Failed to acquire lock within timeout");
            fclose($lockHandle);
            return false;
        }
        
        try {
            // Read current queue
            $queueData = self::readQueueData($queuePath);
            if ($queueData === false) {
                throw new Exception("Failed to read queue data");
            }
            
            // Generate new event ID
            $eventId = $queueData['last_id'] + 1;
            
            // Create event entry
            $event = [
                'id' => $eventId,
                'channel' => $channel,
                'data' => $data,
                'filters' => $filters,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Add to queue
            $queueData['events'][] = $event;
            $queueData['last_id'] = $eventId;
            
            // Clean up old events
            $queueData['events'] = self::cleanupOldEvents($queueData['events']);
            
            // Write back to file
            if (file_put_contents($queuePath, json_encode($queueData, JSON_PRETTY_PRINT)) === false) {
                throw new Exception("Failed to write queue data");
            }
            
            // Release lock
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            
            error_log("[EventQueue] Added event ID $eventId to channel '$channel'");
            return $eventId;
            
        } catch (Exception $e) {
            error_log("[EventQueue] Error adding event: " . $e->getMessage());
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            return false;
        }
    }
    
    /**
     * Get events from the queue
     * 
     * @param array $channels Array of channel names to filter by
     * @param int $sinceId Only return events with ID greater than this
     * @return array Array of events
     */
    public static function getEvents($channels = [], $sinceId = 0) {
        self::init();
        
        $queuePath = self::$queueDir . self::$queueFile;
        
        // Read queue data (no lock needed for reading)
        $queueData = self::readQueueData($queuePath);
        if ($queueData === false) {
            return [];
        }
        
        $events = $queueData['events'];
        
        // Filter by ID
        if ($sinceId > 0) {
            $events = array_filter($events, function($event) use ($sinceId) {
                return $event['id'] > $sinceId;
            });
        }
        
        // Filter by channels
        if (!empty($channels)) {
            $events = array_filter($events, function($event) use ($channels) {
                return in_array($event['channel'], $channels);
            });
        }
        
        // Re-index array
        return array_values($events);
    }
    
    /**
     * Read queue data from file
     * 
     * @param string $queuePath Path to queue file
     * @return array|false Queue data or false on failure
     */
    private static function readQueueData($queuePath) {
        if (!file_exists($queuePath)) {
            return [
                'events' => [],
                'last_id' => 0
            ];
        }
        
        $content = file_get_contents($queuePath);
        if ($content === false) {
            error_log("[EventQueue] Failed to read queue file");
            return false;
        }
        
        $data = json_decode($content, true);
        if ($data === null) {
            error_log("[EventQueue] Failed to decode queue JSON, creating new queue");
            return [
                'events' => [],
                'last_id' => 0
            ];
        }
        
        return $data;
    }
    
    /**
     * Remove events older than TTL
     * 
     * @param array $events Array of events
     * @return array Filtered events
     */
    private static function cleanupOldEvents($events) {
        $cutoffTime = time() - self::$eventTTL;
        
        return array_filter($events, function($event) use ($cutoffTime) {
            $eventTime = strtotime($event['timestamp']);
            return $eventTime > $cutoffTime;
        });
    }
    
    /**
     * Get the last event ID in the queue
     * 
     * @return int Last event ID
     */
    public static function getLastEventId() {
        self::init();
        
        $queuePath = self::$queueDir . self::$queueFile;
        $queueData = self::readQueueData($queuePath);
        
        return $queueData ? $queueData['last_id'] : 0;
    }
    
    /**
     * Clear all events from the queue (for testing/maintenance)
     * 
     * @return bool Success status
     */
    public static function clearQueue() {
        self::init();
        
        $queuePath = self::$queueDir . self::$queueFile;
        $lockFile = $queuePath . '.lock';
        
        $lockHandle = fopen($lockFile, 'c');
        if (!$lockHandle) {
            return false;
        }
        
        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            return false;
        }
        
        $initialData = [
            'events' => [],
            'last_id' => 0
        ];
        
        $result = file_put_contents($queuePath, json_encode($initialData, JSON_PRETTY_PRINT)) !== false;
        
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        
        if ($result) {
            error_log("[EventQueue] Queue cleared");
        }
        
        return $result;
    }
}
