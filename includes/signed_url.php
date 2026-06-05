<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'db_functions.php';

use Google\Cloud\Storage\StorageClient;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get file URL from POST
$fileUrl = $_POST['file_url'] ?? null;
if (empty($fileUrl)) {
    echo json_encode(['success' => false, 'error' => 'No file URL provided']);
    exit;
}

// Extract object name from URL
$objectName = str_replace("https://storage.googleapis.com/" . $BUCKET_NAME . "/", "", $fileUrl);
if (empty($objectName) || $objectName === $fileUrl) {
    echo json_encode(['success' => false, 'error' => 'Invalid file URL']);
    exit;
}

// Verify ownership by matching the exact fileUrl in Firestore
$files = getUserFiles($userId);
$ownedFile = null;
foreach ($files as $file) {
    if ($file['fileUrl'] === $fileUrl) {
        $ownedFile = $file;
        break;
    }
}

if (!$ownedFile) {
    echo json_encode(['success' => false, 'error' => 'File not found or access denied']);
    exit;
}

// Generate signed URL
try {
    $storage = new StorageClient([
        'projectId' => $PROJECT_ID
    ]);
    
    $bucket = $storage->bucket($BUCKET_NAME);
    $object = $bucket->object($objectName);
    
    if (!$object->exists()) {
        echo json_encode(['success' => false, 'error' => 'File not found in storage']);
        exit;
    }
    
    $url = $object->signedUrl(new \DateTime("+60 seconds"), [
        'method' => 'GET',
        'version' => 'v4'
    ]);
    
    echo json_encode(['success' => true, 'url' => $url]);
    
} catch (Exception $e) {
    error_log("Signed URL generation failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate signed URL: ' . $e->getMessage()]);
}
?>
