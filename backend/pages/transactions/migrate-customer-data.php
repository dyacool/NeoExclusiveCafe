<?php
require_once "../../php/includes/database.php";

// Function to execute SQL and handle errors
function executeSQL($conn, $sql) {
    if (!$conn->query($sql)) {
        echo "Error executing SQL: " . $conn->error . "\n";
        echo "SQL: " . $sql . "\n";
        return false;
    }
    return true;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get unique customers from orders table
    $getCustomers = "SELECT DISTINCT 
                        customer_name,
                        customer_contact,
                        customer_address
                    FROM orders 
                    WHERE customer_name IS NOT NULL 
                    AND customer_name != ''";

    $result = $conn->query($getCustomers);
    if (!$result) {
        throw new Exception("Error fetching customers: " . $conn->error);
    }

    $customersProcessed = 0;
    $ordersUpdated = 0;

    while ($customer = $result->fetch_assoc()) {
        // Insert customer into customers table
        $insertCustomer = "INSERT INTO customers (contact, address) 
                          VALUES (?, ?)";
        
        $stmt = $conn->prepare($insertCustomer);
        if (!$stmt) {
            throw new Exception("Error preparing customer insert: " . $conn->error);
        }

        $stmt->bind_param("ss", 
            $customer['customer_contact'],
            $customer['customer_address']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting customer: " . $stmt->error);
        }

        $customerId = $conn->insert_id;
        $customersProcessed++;

        // Update orders with the new customer_id
        $updateOrders = "UPDATE orders 
                        SET customer_id = ? 
                        WHERE customer_name = ? 
                        AND customer_contact = ? 
                        AND customer_address = ?";

        $stmt = $conn->prepare($updateOrders);
        if (!$stmt) {
            throw new Exception("Error preparing order update: " . $conn->error);
        }

        $stmt->bind_param("isss", 
            $customerId,
            $customer['customer_name'],
            $customer['customer_contact'],
            $customer['customer_address']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error updating orders: " . $stmt->error);
        }

        $ordersUpdated += $stmt->affected_rows;
    }

    // Commit transaction
    $conn->commit();

    echo "Migration completed successfully:\n";
    echo "Customers processed: $customersProcessed\n";
    echo "Orders updated: $ordersUpdated\n";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error during migration: " . $e->getMessage() . "\n";
}

// Verify the migration
$verifyQuery = "SELECT 
                    COUNT(*) as total_orders,
                    COUNT(customer_id) as orders_with_customer_id
                FROM orders";

$result = $conn->query($verifyQuery);
if ($result) {
    $stats = $result->fetch_assoc();
    echo "\nVerification:\n";
    echo "Total orders: " . $stats['total_orders'] . "\n";
    echo "Orders with customer_id: " . $stats['orders_with_customer_id'] . "\n";
    echo "Migration coverage: " . round(($stats['orders_with_customer_id'] / $stats['total_orders']) * 100, 2) . "%\n";
}
?> 