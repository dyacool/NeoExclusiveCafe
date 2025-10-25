<?php
$file = 'frontend/pages/products/product-dashboard.php';
$lines = file($file);
echo "Line 1940-1955:\n";
for ($i = 1939; $i < 1955 && $i < count($lines); $i++) {
    echo ($i + 1) . ": " . $lines[$i];
}
?>
