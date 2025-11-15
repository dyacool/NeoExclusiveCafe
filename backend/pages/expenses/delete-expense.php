<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';
require_once __DIR__ . "/../admin-includes/database.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid expense ID']);
    exit;
}

$expense_id = intval($data['id']);

try {
    // Delete expense
    $sql = "DELETE FROM expenses WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $expense_id);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Expense not found']);
        }
    } else {
        throw new Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    error_log("Error deleting expense: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to delete expense']);
}

mysqli_close($conn);
?>
