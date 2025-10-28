<?php
require_once __DIR__ . '/config/cloudinary-config.php';

echo "<h2>Cloudinary Files List</h2>\n\n";

try {
    $cloudinary = CloudinaryConfig::getInstance()->getCloudinary();
    
    // List resources in the neocafe folder
    echo "<h3>Listing files in Cloudinary...</h3>\n";
    
    // Get all resources with prefix 'neocafe'
    $result = $cloudinary->adminApi()->resources([
        'type' => 'upload',
        'prefix' => 'neocafe/',
        'max_results' => 500
    ]);
    
    echo "<p>Total files found: " . count($result['resources']) . "</p>\n\n";
    
    // Group by folder
    $folders = [];
    foreach ($result['resources'] as $resource) {
        $public_id = $resource['public_id'];
        $parts = explode('/', $public_id);
        $folder = isset($parts[1]) ? $parts[1] : 'root';
        
        if (!isset($folders[$folder])) {
            $folders[$folder] = [];
        }
        
        $folders[$folder][] = [
            'public_id' => $public_id,
            'url' => $resource['secure_url'],
            'format' => $resource['format'],
            'created_at' => $resource['created_at']
        ];
    }
    
    // Display by folder
    foreach ($folders as $folder => $files) {
        echo "<h3>📁 Folder: neocafe/{$folder} (" . count($files) . " files)</h3>\n";
        echo "<table border='1' cellpadding='5' style='width:100%; font-size:12px;'>\n";
        echo "<tr><th>Public ID</th><th>URL</th><th>Format</th></tr>\n";
        
        foreach ($files as $file) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($file['public_id']) . "</td>";
            echo "<td><a href='" . htmlspecialchars($file['url']) . "' target='_blank'>View</a></td>";
            echo "<td>" . htmlspecialchars($file['format']) . "</td>";
            echo "</tr>\n";
        }
        
        echo "</table><br>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>Next Step:</h3>\n";
    echo "<p>Now we need to update your database to link these Cloudinary URLs to your products.</p>\n";
    echo "<p>What folder structure did you use when uploading? (e.g., neocafe/products/, neocafe/carousel/)</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Make sure your Cloudinary credentials are correct in .env file</p>\n";
}
?>
