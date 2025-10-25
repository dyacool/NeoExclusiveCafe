<?php
// Test the get-post-data.php directly
session_start();
$_SESSION["is_admin"] = true; // Force admin session for testing

if (!isset($_GET['id'])) {
    die("Please provide an ID parameter. Example: test-get-post.php?id=1");
}

$id = $_GET['id'];
echo "<h2>Testing get-post-data.php with ID: $id</h2>";

// Test the API call
$url = "http://localhost/backend/pages/blog/get-post-data.php?id=" . $id;
echo "<p>Calling: <a href='$url' target='_blank'>$url</a></p>";

// Make the request
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);

echo "<h3>Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "<h3>Parsed JSON:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        
        echo "<h3>Key Fields:</h3>";
        echo "<ul>";
        echo "<li><strong>Title:</strong> " . htmlspecialchars($data['title'] ?? 'Not found') . "</li>";
        echo "<li><strong>Description:</strong> " . htmlspecialchars(substr($data['description'] ?? 'Not found', 0, 100)) . "...</li>";
        echo "<li><strong>Image Path:</strong> " . htmlspecialchars($data['image_path'] ?? 'Not found') . "</li>";
        echo "</ul>";
    } else {
        echo "<p><strong>Error:</strong> Could not parse JSON response</p>";
    }
}
?>