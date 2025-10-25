<?php
// Read the file
$file = 'frontend/pages/products/product-dashboard.php';
$content = file_get_contents($file);

// Remove any standalone try blocks or broken function declarations
$content = preg_replace('/\s*try\s*\{\s*const productData[^}]*\}\s*\}/', '', $content);

// Remove duplicate/broken addToCart functions (keep only the complete one)
// This regex finds the broken pattern with extra closing brace
$content = preg_replace('/function addToCartFromModal\(\)[^{]*\{[^}]*closeProductModal\(\);[^}]*\}\s*\}/', '', $content);

// Save the fixed file
file_put_contents($file, $content);

echo "Fixed! Check the file now.\n";
?>
