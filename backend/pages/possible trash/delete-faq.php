<?php
require_once __DIR__ . '/../includes/database.php';
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID is required.']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM chatbot_faq WHERE id = ?');
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
} 