<?php
require_once __DIR__ . "/../admin-includes/database.php";

echo "=== CHECKING SDO DATA FOR PRODUCT 3 ===\n\n";

// Check product status
$status_sql = "SELECT id, name, status_id FROM products WHERE id = 3";
$status_result = mysqli_query($conn, $status_sql);
if ($row = mysqli_fetch_assoc($status_result)) {
    echo "Product: " . $row['name'] . "\n";
    echo "Status ID: " . $row['status_id'] . "\n\n";
}

// Check quantities
echo "--- Quantities in quantity_per_day_sdo ---\n";
$sql = "SELECT * FROM quantity_per_day_sdo WHERE product_id = 3 ORDER BY date";
$result = mysqli_query($conn, $sql);
if ($result) {
    $count = mysqli_num_rows($result);
    echo "Found $count records:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "  Date: " . $row['date'] . " | Quantity: " . $row['quantity'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// Check todays_products_dates
echo "\n--- Dates in todays_products_dates ---\n";
$sql2 = "SELECT * FROM todays_products_dates WHERE product_id = 3 ORDER BY available_date";
$result2 = mysqli_query($conn, $sql2);
if ($result2) {
    $count2 = mysqli_num_rows($result2);
    echo "Found $count2 records:\n";
    while ($row = mysqli_fetch_assoc($result2)) {
        echo "  Date: " . $row['available_date'] . " | Status ID: " . $row['availtoday_status_id'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// Check regular_products_today_dates
echo "\n--- Dates in regular_products_today_dates ---\n";
$sql3 = "SELECT * FROM regular_products_today_dates WHERE product_id = 3 ORDER BY available_date";
$result3 = mysqli_query($conn, $sql3);
if ($result3) {
    $count3 = mysqli_num_rows($result3);
    echo "Found $count3 records:\n";
    while ($row = mysqli_fetch_assoc($result3)) {
        echo "  Date: " . $row['available_date'] . " | Status ID: " . $row['availtoday_status_id'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

echo "\n=== DONE ===\n";

mysqli_close($conn);
?>
