<?php
/**
 * Migration Runner
 * 
 * Simple web interface to run database migrations
 * Access: https://admin.neocafe.shop/backend/database/run-migration.php
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    die("Unauthorized. Please log in as admin.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration Runner</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #1e1e1e;
            color: #d4d4d4;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .migration-box {
            background-color: #252526;
            border: 1px solid #3e3e42;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .migration-box h2 {
            color: #dcdcaa;
            margin-top: 0;
        }
        .migration-box p {
            color: #9cdcfe;
            line-height: 1.6;
        }
        .btn {
            background-color: #0e639c;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #1177bb;
        }
        .btn:disabled {
            background-color: #3e3e42;
            cursor: not-allowed;
        }
        #output {
            background-color: #1e1e1e;
            border: 1px solid #3e3e42;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            max-height: 500px;
            overflow-y: auto;
            display: none;
        }
        .success {
            color: #4ec9b0;
        }
        .error {
            color: #f48771;
        }
        .warning {
            color: #dcdcaa;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4ec9b0;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>🗄️ Database Migration Runner</h1>
    
    <div class="migration-box">
        <h2>AJAX Image Management Support (Products)</h2>
        <p><strong>Migration:</strong> add-ajax-image-support.php</p>
        <p><strong>Description:</strong> This migration prepares the database for AJAX-based image management with Cloudinary.</p>
        <p><strong>Changes:</strong></p>
        <ul>
            <li>Makes <code>image_url</code> column nullable in <code>product_images</code> table</li>
            <li>Creates <code>temp_uploaded_images</code> table for orphan tracking</li>
            <li>Adds performance indexes</li>
        </ul>
        <button class="btn" onclick="runMigration('add-ajax-image-support.php', this)">Run Migration</button>
    </div>
    
    <div class="migration-box">
        <h2>Carousel AJAX Image Management Support</h2>
        <p><strong>Migration:</strong> add-carousel-ajax-support.php</p>
        <p><strong>Description:</strong> This migration prepares the carousel_images table for AJAX-based image management.</p>
        <p><strong>Changes:</strong></p>
        <ul>
            <li>Adds Cloudinary columns (<code>cloud_url</code>, <code>cloud_public_id</code>, <code>cloud_provider</code>) to <code>carousel_images</code> table</li>
            <li>Makes <code>image_url</code> column nullable</li>
            <li>Adds performance indexes</li>
            <li>Reuses <code>temp_uploaded_images</code> table from product images</li>
        </ul>
        <button class="btn" onclick="runMigration('add-carousel-ajax-support.php', this)">Run Migration</button>
    </div>
    
    <div id="output"></div>
    
    <a href="/backend/pages/products/product-list.php" class="back-link">← Back to Products</a>
    
    <script>
        async function runMigration(migrationFile, btn) {
            const output = document.getElementById('output');
            
            btn.disabled = true;
            btn.textContent = 'Running...';
            output.style.display = 'block';
            output.innerHTML = '<span class="warning">Running migration: ' + migrationFile + '</span>\n\n';
            
            try {
                const response = await fetch('migrations/' + migrationFile);
                const text = await response.text();
                
                // Format output with colors
                let formattedText = text
                    .replace(/✓/g, '<span class="success">✓</span>')
                    .replace(/✗/g, '<span class="error">✗</span>')
                    .replace(/===/g, '<span class="warning">===</span>');
                
                output.innerHTML = formattedText;
                
                if (text.includes('Migration completed successfully')) {
                    btn.textContent = 'Migration Complete ✓';
                    btn.style.backgroundColor = '#4ec9b0';
                } else {
                    btn.textContent = 'Migration Failed ✗';
                    btn.style.backgroundColor = '#f48771';
                    btn.disabled = false;
                }
            } catch (error) {
                output.innerHTML = '<span class="error">Error running migration: ' + error.message + '</span>';
                btn.textContent = 'Run Migration';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
