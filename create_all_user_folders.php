<?php
require_once 'includes/db_functions.php';

echo "Starting to create folders for all existing users...\n\n";

global $db;
$usersCollection = $db->collection('users');
$documents = $usersCollection->documents();

$count = 0;
$success = 0;

foreach ($documents as $doc) {
    if ($doc->exists()) {
        $userData = $doc->data();
        $userId = $userData['userId'] ?? $doc->id();
        $username = $userData['username'] ?? 'Unknown';
        
        echo "Creating folder for: $username (ID: $userId) ... ";
        
        
        $bucketName = ""; // Bucket Name
        $command = "gsutil mkdir gs://{$bucketName}/user_{$userId}/ 2>&1";
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✅ DONE\n";
            $success++;
        } else {
            echo "❌ FAILED: " . implode(" ", $output) . "\n";
        }
        $count++;
    }
}

echo "\n========================================\n";
echo "Total users: $count\n";
echo "Folders created: $success\n";
echo "========================================\n";
?>
