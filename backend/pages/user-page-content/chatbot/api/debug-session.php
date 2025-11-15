<?php
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_data' => $_SESSION,
    'session_id' => session_id(),
    'session_status' => session_status()
]);
