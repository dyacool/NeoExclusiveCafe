<?php
require_once __DIR__ . '/../php/includes/database.php';

// Query to get top 10 most sold products, highest at the top, using product_name for join
$query = "SELECT 
            p.id as product_id,
            p.name as product_name,
            p.sku,
            COALESCE(SUM(oi.quantity), 0) as total_quantity,
            p.price as current_price
          FROM products p
          LEFT JOIN order_items oi ON oi.product_name = p.name
          WHERE p.deleted_at IS NULL
          GROUP BY p.id, p.name, p.sku, p.price
          ORDER BY total_quantity DESC, p.name ASC
          LIMIT 10";

$result = $conn->query($query);
$dataPoints = array();
$productCount = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataPoints[] = array(
            "label" => $row['product_name'],
            "y" => intval($row['total_quantity']),
            "toolTipContent" =>
                "<b>" . htmlspecialchars($row['product_name']) . "</b><br>" .
                "SKU: " . htmlspecialchars($row['sku']) . "<br>" .
                "Current Price: ₱" . number_format($row['current_price'], 2) . "<br>" .
                "Total Sold: " . $row['total_quantity']
        );
        $productCount++;
    }
}
// Set chart height dynamically: 40px per product, min 400px, max 1200px
$chartHeight = max(400, min(40 * $productCount, 1200));
?>
<!DOCTYPE HTML>
<html>
<head>  
<script>
window.onload = function () {
    var chart = new CanvasJS.Chart("chartContainer", {
        animationEnabled: true,
        exportEnabled: true,
        theme: "light1",
        title:{
            text: "Top 10 Most Sold Products"
        },
        axisX:{
            title: "Total Quantity Sold",
            includeZero: true
        },
        axisY:{
            title: "Product Name",
            labelFontSize: 12,
            labelMaxWidth: 180,
            interval: 1
        },
        toolTip: {
            shared: false,
            content: "{toolTipContent}"
        },
        data: [{
            type: "bar",
            indexLabel: "{y}",
            indexLabelFontColor: "#5A5757",
            indexLabelPlacement: "outside",   
            dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
        }]
    });
    chart.render();
}
</script>
<style>
#chartContainer {
    width: 100%;
    min-width: 350px;
}
</style>
</head>
<body>
<div id="chartContainer" style="height: <?php echo $chartHeight; ?>px;"></div>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</body>
</html>                              