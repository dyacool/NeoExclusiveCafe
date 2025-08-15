<?php
header('Content-Type: application/json');

echo "=== Time Logic Test ===\n";

// Get current time
$current_time = date('H:i:s');
$current_date = date('Y-m-d');

echo "Current time: $current_time\n";
echo "Current date: $current_date\n";

// Test different closing times
$test_closing_times = [
    '17:00:00',  // 5:00 PM
    '18:00:00',  // 6:00 PM
    '19:00:00',  // 7:00 PM
    '20:00:00',  // 8:00 PM
    '21:00:00',  // 9:00 PM
    '22:00:00',  // 10:00 PM
    '23:00:00',  // 11:00 PM
    '00:00:00',  // 12:00 AM (next day)
    '01:00:00',  // 1:00 AM (next day)
];

echo "\nTesting time comparisons:\n";
foreach ($test_closing_times as $closing_time) {
    $is_closed = ($current_time > $closing_time);
    $status = $is_closed ? "CLOSED" : "OPEN";
    echo "Current: $current_time vs Closing: $closing_time = $status\n";
}

// Test with specific times
echo "\nTesting specific scenarios:\n";

$scenarios = [
    ['current' => '16:00:00', 'closing' => '17:00:00', 'description' => '4 PM vs 5 PM closing'],
    ['current' => '17:30:00', 'closing' => '17:00:00', 'description' => '5:30 PM vs 5 PM closing'],
    ['current' => '18:00:00', 'closing' => '17:00:00', 'description' => '6 PM vs 5 PM closing'],
    ['current' => '23:00:00', 'closing' => '17:00:00', 'description' => '11 PM vs 5 PM closing'],
    ['current' => '01:00:00', 'closing' => '17:00:00', 'description' => '1 AM vs 5 PM closing (next day)'],
];

foreach ($scenarios as $scenario) {
    $current = $scenario['current'];
    $closing = $scenario['closing'];
    $description = $scenario['description'];
    $is_closed = ($current > $closing);
    $status = $is_closed ? "CLOSED" : "OPEN";
    echo "$description: $current vs $closing = $status\n";
}

// Test midnight crossing issue
echo "\nTesting midnight crossing:\n";
$midnight_scenarios = [
    ['current' => '23:30:00', 'closing' => '22:00:00', 'description' => '11:30 PM vs 10 PM closing'],
    ['current' => '00:30:00', 'closing' => '22:00:00', 'description' => '12:30 AM vs 10 PM closing (next day)'],
    ['current' => '01:00:00', 'closing' => '22:00:00', 'description' => '1 AM vs 10 PM closing (next day)'],
];

foreach ($midnight_scenarios as $scenario) {
    $current = $scenario['current'];
    $closing = $scenario['closing'];
    $description = $scenario['description'];
    
    // Convert to minutes for proper comparison
    $current_minutes = (intval(substr($current, 0, 2)) * 60) + intval(substr($current, 3, 2));
    $closing_minutes = (intval(substr($closing, 0, 2)) * 60) + intval(substr($closing, 3, 2));
    
    // Handle midnight crossing
    if ($current_minutes < $closing_minutes) {
        // Current time is before closing time, but we're past midnight
        $is_closed = true;
    } else {
        $is_closed = ($current_minutes > $closing_minutes);
    }
    
    $status = $is_closed ? "CLOSED" : "OPEN";
    echo "$description: $current vs $closing = $status (minutes: $current_minutes vs $closing_minutes)\n";
}

echo "\n=== Test Complete ===\n";
?>
