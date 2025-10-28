<?php
/**
 * Simple Cloudinary Diagnostic Test
 * This will tell us exactly what's wrong
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cloudinary Diagnostic</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Cloudinary Setup Diagnostic</h1>
    
    <div class="box">
        <h2>1. File System Check</h2>
        <?php
        $files = [
            'Config' => __DIR__ . '/config/cloudinary-config.php',
            'Database Config' => __DIR__ . '/config/database-config.php',
            'Fetcher' => __DIR__ . '/backend/includes/cloudinary-image-fetcher.php',
            'Helper' => __DIR__ . '/backend/includes/cloudinary-helper.php'
        ];
        
        foreach ($files as $name => $path) {
            if (file_exists($path)) {
                echo "<span class='success'>✓</span> {$name}: EXISTS<br>";
            } else {
                echo "<span class='error'>✗</span> {$name}: NOT FOUND at {$path}<br>";
            }
        }
        ?>
    </div>
    
    <div class="box">
        <h2>2. Composer Autoload Check</h2>
        <?php
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            echo "<span class='success'>✓</span> Composer autoload exists<br>";
            require_once $autoloadPath;
            echo "<span class='success'>✓</span> Composer autoload loaded successfully<br>";
        } else {
            echo "<span class='error'>✗</span> Composer autoload NOT FOUND<br>";
            echo "<span class='error'>Run: composer install</span><br>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>3. Cloudinary SDK Check</h2>
        <?php
        if (class_exists('Cloudinary\Cloudinary')) {
            echo "<span class='success'>✓</span> Cloudinary SDK is installed<br>";
        } else {
            echo "<span class='error'>✗</span> Cloudinary SDK NOT FOUND<br>";
            echo "<span class='error'>Run: composer require cloudinary/cloudinary_php</span><br>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>4. Environment Variables Check</h2>
        <?php
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            echo "<span class='success'>✓</span> .env file exists<br>";
            
            // Try to load it
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (!empty($key)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                    }
                }
            }
        } else {
            echo "<span class='error'>✗</span> .env file NOT FOUND<br>";
        }
        
        $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
        $apiKey = getenv('CLOUDINARY_API_KEY');
        $apiSecret = getenv('CLOUDINARY_API_SECRET');
        
        echo "<br>";
        echo "CLOUDINARY_CLOUD_NAME: " . ($cloudName ? "<span class='success'>✓ Set ({$cloudName})</span>" : "<span class='error'>✗ Not set</span>") . "<br>";
        echo "CLOUDINARY_API_KEY: " . ($apiKey ? "<span class='success'>✓ Set</span>" : "<span class='error'>✗ Not set</span>") . "<br>";
        echo "CLOUDINARY_API_SECRET: " . ($apiSecret ? "<span class='success'>✓ Set</span>" : "<span class='error'>✗ Not set</span>") . "<br>";
        ?>
    </div>
    
    <div class="box">
        <h2>5. Cloudinary Connection Test</h2>
        <?php
        if (class_exists('Cloudinary\Cloudinary') && $cloudName && $apiKey && $apiSecret) {
            try {
                $cloudinary = new Cloudinary\Cloudinary([
                    'cloud' => [
                        'cloud_name' => $cloudName,
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret
                    ]
                ]);
                
                echo "<span class='success'>✓</span> Cloudinary object created<br>";
                
                // Try to ping
                try {
                    $result = $cloudinary->adminApi()->ping();
                    echo "<span class='success'>✓</span> Cloudinary connection successful!<br>";
                    echo "<pre>" . print_r($result, true) . "</pre>";
                } catch (Exception $e) {
                    echo "<span class='error'>✗</span> Cloudinary ping failed: " . $e->getMessage() . "<br>";
                }
                
            } catch (Exception $e) {
                echo "<span class='error'>✗</span> Failed to create Cloudinary object: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "<span class='error'>✗</span> Cannot test - missing SDK or credentials<br>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>6. Database Connection Test</h2>
        <?php
        try {
            if (file_exists(__DIR__ . '/config/database-config.php')) {
                require_once __DIR__ . '/config/database-config.php';
                $conn = getDatabaseConnection();
                
                if ($conn && $conn->ping()) {
                    echo "<span class='success'>✓</span> Database connected successfully<br>";
                    echo "Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br>";
                } else {
                    echo "<span class='error'>✗</span> Database connection failed<br>";
                }
            } else {
                echo "<span class='error'>✗</span> Database config file not found<br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>✗</span> Database error: " . $e->getMessage() . "<br>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>7. Load CloudinaryImageFetcher Class</h2>
        <?php
        // First, let's see what happens when we try to include it
        echo "<strong>Attempting to load file...</strong><br>";
        
        $fetcherFile = __DIR__ . '/backend/includes/cloudinary-image-fetcher.php';
        
        if (!file_exists($fetcherFile)) {
            echo "<span class='error'>✗</span> File not found: {$fetcherFile}<br>";
        } else {
            echo "<span class='success'>✓</span> File exists<br>";
            
            // Check file size
            $fileSize = filesize($fetcherFile);
            echo "File size: {$fileSize} bytes<br>";
            
            if ($fileSize < 100) {
                echo "<span class='error'>⚠️</span> File seems too small, might be corrupted<br>";
                echo "<pre>" . htmlspecialchars(file_get_contents($fetcherFile)) . "</pre>";
            } else {
                // Try to include with error capture
                ob_start();
                try {
                    include_once $fetcherFile;
                    $includeOutput = ob_get_clean();
                    
                    if (!empty($includeOutput)) {
                        echo "<span class='error'>⚠️</span> Output during include:<br>";
                        echo "<pre>" . htmlspecialchars($includeOutput) . "</pre>";
                    }
                    
                    echo "<span class='success'>✓</span> File included without fatal errors<br>";
                    
                    // Check if class exists
                    if (class_exists('CloudinaryImageFetcher')) {
                        echo "<span class='success'>✓</span> CloudinaryImageFetcher class found!<br>";
                        
                        // Try to instantiate
                        if (isset($conn) && $conn) {
                            try {
                                $fetcher = new CloudinaryImageFetcher($conn);
                                echo "<span class='success'>✓</span> CloudinaryImageFetcher instantiated successfully!<br>";
                                
                                $status = $fetcher->getCloudinaryStatus();
                                echo "<strong>Cloudinary Status:</strong><br>";
                                echo "<pre>" . print_r($status, true) . "</pre>";
                            } catch (Exception $e) {
                                echo "<span class='error'>✗</span> Failed to instantiate: " . $e->getMessage() . "<br>";
                                echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
                            }
                        } else {
                            echo "<span class='error'>⚠️</span> No database connection available for testing<br>";
                        }
                    } else {
                        echo "<span class='error'>✗</span> CloudinaryImageFetcher class NOT found after include<br>";
                        echo "<strong>Declared classes in file:</strong><br>";
                        $declaredClasses = get_declared_classes();
                        $relevantClasses = array_filter($declaredClasses, function($class) {
                            return stripos($class, 'cloudinary') !== false || stripos($class, 'fetcher') !== false;
                        });
                        echo "<pre>" . print_r($relevantClasses, true) . "</pre>";
                    }
                    
                } catch (Exception $e) {
                    ob_end_clean();
                    echo "<span class='error'>✗</span> Exception: " . $e->getMessage() . "<br>";
                    echo "<pre>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "\n" . $e->getTraceAsString() . "</pre>";
                } catch (Error $e) {
                    ob_end_clean();
                    echo "<span class='error'>✗</span> PHP Error: " . $e->getMessage() . "<br>";
                    echo "<pre>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "\n" . $e->getTraceAsString() . "</pre>";
                }
            }
        }
        ?>
    </div>
    
    <div class="box">
        <h2>8. PHP Info</h2>
        <span class="info">PHP Version: <?php echo PHP_VERSION; ?></span><br>
        <span class="info">Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span><br>
        <span class="info">Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></span><br>
        <span class="info">Current Dir: <?php echo __DIR__; ?></span><br>
    </div>
    
    <div class="box">
        <h2>📋 Summary</h2>
        <p>If all checks pass with ✓, the Cloudinary Image Fetcher is ready to use!</p>
        <p>If you see ✗ errors, follow the instructions shown to fix them.</p>
    </div>
    
</body>
</html>
