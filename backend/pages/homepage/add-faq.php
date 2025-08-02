<?php
require_once __DIR__ . '/../includes/database.php';
header('Content-Type: application/json');

$question = trim($_POST['question'] ?? '');
$answer = trim($_POST['answer'] ?? '');

if ($question === '' || $answer === '') {
    echo json_encode(['success' => false, 'error' => 'Question and answer are required.']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO chatbot_faq (question, answer) VALUES (?, ?)');
$stmt->bind_param('ss', $question, $answer);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
} 