<?php
session_start();
require_once 'includes/db_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$file_id = $_POST['file_id'] ?? '';
$file_url = $_POST['file_url'] ?? '';

if (empty($file_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
    exit();
}

// Verify file belongs to user
$file = getFileMetadata($user_id, $file_id);

if (!$file) {
    echo json_encode(['success' => false, 'message' => 'File not found or not yours']);
    exit();
}

// Delete from both Storage and Database
$result = deleteFileComplete($user_id, $file_id, $file['fileUrl']);

echo json_encode([
    'success' => $result['success'],
    'message' => $result['message']
]);
?>
