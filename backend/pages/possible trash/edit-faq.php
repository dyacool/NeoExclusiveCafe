<?php
require_once __DIR__ . '/../includes/database.php';
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$question = trim($_POST['question'] ?? '');
$answer = trim($_POST['answer'] ?? '');

if ($id <= 0 || $question === '' || $answer === '') {
    echo json_encode(['success' => false, 'error' => 'ID, question, and answer are required.']);
    exit;
}

$stmt = $conn->prepare('UPDATE chatbot_faq SET question = ?, answer = ? WHERE id = ?');
$stmt->bind_param('ssi', $question, $answer, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
} 