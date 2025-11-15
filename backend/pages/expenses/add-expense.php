<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';
require_once __DIR__ . "/../admin-includes/database.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Validate required fields
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

// Log received data for debugging
error_log("Expense submission - Name: $name, Category: $category, Amount: $amount, Note: $note");

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

if (empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Category is required', 'received_category' => $category, 'post_data' => $_POST]);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0']);
    exit;
}

// Validate category
$allowed_categories = ['Fixed Costs', 'Variable Costs', 'Overhead Costs'];
if (!in_array($category, $allowed_categories)) {
    echo json_encode(['success' => false, 'error' => 'Invalid category']);
    exit;
}

// Truncate note to 100 characters
$note = substr($note, 0, 100);

try {
    // Check if table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'expenses'");
    if (mysqli_num_rows($table_check) === 0) {
        echo json_encode([
            'success' => false, 
            'error' => 'Database table not found. Please run the setup first.',
            'setup_required' => true
        ]);
        exit;
    }
    
    // Insert expense
    $sql = "INSERT INTO expenses (name, category, amount, note, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ssds", $name, $category, $amount, $note);
    
    if (mysqli_stmt_execute($stmt)) {
        $insert_id = mysqli_insert_id($conn);
        
        // Verify the insert by reading it back
        $verify_sql = "SELECT * FROM expenses WHERE id = ?";
        $verify_stmt = mysqli_prepare($conn, $verify_sql);
        mysqli_stmt_bind_param($verify_stmt, "i", $insert_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        $inserted_row = mysqli_fetch_assoc($verify_result);
        mysqli_stmt_close($verify_stmt);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Expense added successfully',
            'id' => $insert_id,
            'inserted_data' => $inserted_row,
            'sent_data' => [
                'name' => $name,
                'category' => $category,
                'amount' => $amount,
                'note' => $note
            ]
        ]);
    } else {
        throw new Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    error_log("Error adding expense: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
