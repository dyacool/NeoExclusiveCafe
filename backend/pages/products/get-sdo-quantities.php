<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit();
}

$product_id = intval($_GET['product_id']);

// Get all quantities for this product
$sql = "SELECT date, quantity FROM quantity_per_day_sdo WHERE product_id = ? ORDER BY date ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$quantities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $quantities[$row['date']] = $row['quantity'];
}

echo json_encode([
    'success' => true,
    'quantities' => $quantities
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
