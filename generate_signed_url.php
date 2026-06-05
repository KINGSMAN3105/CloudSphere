<?php
session_start();
require_once 'includes/db_functions.php';
require_once 'includes/signed_url.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$file_id = $_POST['file_id'] ?? '';

if (empty($file_id)) {
    echo json_encode(['error' => 'No file ID']);
    exit();
}

$file = getFileMetadata($user_id, $file_id);

if (!$file) {
    echo json_encode(['error' => 'File not found']);
    exit();
}

$bucketName = "bucket-name";
$fileUrl = $file['fileUrl'];
$objectName = str_replace("https://storage.googleapis.com/" . $bucketName . "/", "", $fileUrl);

$signedUrl = generateSignedUrl($bucketName, $objectName, 60);

if ($signedUrl) {
    echo json_encode(['url' => $signedUrl]);
} else {
    echo json_encode(['error' => 'Failed to generate signed URL']);
}
?>
