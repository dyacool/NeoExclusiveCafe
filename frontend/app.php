<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeoCafe - Dashboard</title>
    
    <!-- Include your base CSS files -->
    <link rel="stylesheet" href="/frontend/user-includes/navbar/customer-navigation.css">
    
    <!-- Include any global styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            overflow-x: hidden;
        }
        
        /* Ensure proper iframe sizing */
        html, body {
            height: 100%;
        }
    </style>
</head>
<body>
    <?php 
    // Include the navigation with iframe setup
    require_once __DIR__ . "/user-includes/navbar/customer-navigation.php";
    ?>
    
    <script>
        // Global scripts that should be available across all pages
        
        // Handle iframe communication if needed
        window.addEventListener('message', function(event) {
            // Handle messages from iframe pages if needed
            if (event.data.type === 'navigate') {
                // Handle navigation requests from iframe
                const targetPage = event.data.page;
                const iframe = document.getElementById('contentFrame');
                if (iframe) {
                    iframe.src = targetPage;
                }
            }
        });
        
        // Add any global functionality here
    </script>
</body>
</html>
