<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Storage\StorageClient;


$PROJECT_ID  = '';  // Your actual project ID
$BUCKET_NAME = '';   // Your bucket name
// =================================================

// Initialize Firestore
$db = new FirestoreClient([
    'projectId' => $PROJECT_ID,
    'database'  => '' // Database Name
]);

// Initialize Cloud Storage
$storage = new StorageClient([
    'projectId' => $PROJECT_ID
]);
$bucket = $storage->bucket($BUCKET_NAME);

// Collection names
const USERS_COLLECTION = 'users';
const FILES_COLLECTION = 'files';

// ---------- USER FUNCTIONS ----------

function createUser($userId, $username, $passwordHash) {
    global $db;
    try {
        $db->collection(USERS_COLLECTION)->document($userId)->set([
            'userId' => $userId,
            'username' => $username,
            'passwordHash' => $passwordHash,
            'createdAt' => new DateTime()
        ]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getUser($userId) {
    global $db;
    try {
        $snapshot = $db->collection(USERS_COLLECTION)->document($userId)->snapshot();
        return $snapshot->exists() ? $snapshot->data() : null;
    } catch (Exception $e) {
        return null;
    }
}

function getUserByUsername($username) {
    global $db;
    try {
        $query = $db->collection(USERS_COLLECTION)->where('username', '=', $username);
        $documents = $query->documents();
        foreach ($documents as $doc) {
            if ($doc->exists()) {
                return $doc->data();
            }
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// ---------- FILE FUNCTIONS ----------

function saveFileMetadata($userId, $fileId, $fileName, $fileUrl, $fileSize, $mimeType) {
    global $db;
    try {
        $db->collection(FILES_COLLECTION)->document($fileId)->set([
            'userId' => $userId,
            'fileId' => $fileId,
            'fileName' => $fileName,
            'fileUrl' => $fileUrl,
            'fileSize' => (int)$fileSize,
            'mimeType' => $mimeType,
            'uploadedAt' => new DateTime()
        ]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getFileMetadata($userId, $fileId) {
    global $db;
    try {
        $snapshot = $db->collection(FILES_COLLECTION)->document($fileId)->snapshot();
        if ($snapshot->exists() && $snapshot->data()['userId'] === $userId) {
            return $snapshot->data();
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}

function getUserFiles($userId) {
    global $db;
    try {
        $query = $db->collection(FILES_COLLECTION)
            ->where('userId', '=', $userId)
            ->orderBy('uploadedAt', 'DESC');
        $documents = $query->documents();
        $files = [];
        foreach ($documents as $doc) {
            if ($doc->exists()) {
                $files[] = $doc->data();
            }
        }
        return $files;
    } catch (Exception $e) {
        return [];
    }
}

function deleteFileMetadata($userId, $fileId) {
    global $db;
    try {
        $snapshot = $db->collection(FILES_COLLECTION)->document($fileId)->snapshot();
        if ($snapshot->exists() && $snapshot->data()['userId'] === $userId) {
            $db->collection(FILES_COLLECTION)->document($fileId)->delete();
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'File not found or access denied'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getUserFileCount($userId) {
    return count(getUserFiles($userId));
}

function getUserStorageUsed($userId) {
    $total = 0;
    foreach (getUserFiles($userId) as $file) {
        $total += $file['fileSize'];
    }
    return $total;
}

function deleteAllUserFiles($userId) {
    $files = getUserFiles($userId);
    $success = true;
    foreach ($files as $file) {
        $result = deleteFileMetadata($userId, $file['fileId']);
        if (!$result['success']) {
            $success = false;
        }
    }
    return ['success' => $success];
}

function updateFileMetadata($userId, $fileId, $updates) {
    global $db;
    try {
        $snapshot = $db->collection(FILES_COLLECTION)->document($fileId)->snapshot();
        if (!$snapshot->exists() || $snapshot->data()['userId'] !== $userId) {
            return ['success' => false, 'error' => 'File not found'];
        }
        $db->collection(FILES_COLLECTION)->document($fileId)->update($updates);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getUserFilesByCategory($userId, $category = null) {
    $files = getUserFiles($userId);
    if ($category === null) {
        return $files;
    }
    
    $extensions = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'],
        'video' => ['mp4', 'webm', 'mov', 'avi', 'mkv'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
        'pdf' => ['pdf'],
        'document' => ['doc', 'docx', 'txt', 'md', 'rtf', 'odt'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'code' => ['php', 'html', 'js', 'css', 'json', 'py', 'java', 'c', 'cpp']
    ];
    
    $allowed = $extensions[$category] ?? [];
    return array_filter($files, function($file) use ($allowed) {
        $ext = strtolower(pathinfo($file['fileName'], PATHINFO_EXTENSION));
        return in_array($ext, $allowed);
    });
}

// =============================================
// CLOUD STORAGE MANAGEMENT FUNCTIONS (FIXED)
// =============================================

function createUserStorageFolder($userId, $bucketName = null) {
    global $bucket, $BUCKET_NAME;
    
    $bucketName = $bucketName ?? $BUCKET_NAME;
    $folderMarker = "user_{$userId}/.folder_marker";
    
    try {
        // Use gsutil command (most reliable)
        $command = "gsutil cp /dev/null gs://{$bucketName}/{$folderMarker} 2>&1";
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return true;
        }
        
        // Fallback: Try PHP method
        $tempStream = fopen('php://temp', 'r+');
        fwrite($tempStream, '');
        rewind($tempStream);
        
        $bucket->upload($tempStream, [
            'name' => $folderMarker,
            'metadata' => ['contentType' => 'text/plain']
        ]);
        
        if (is_resource($tempStream)) {
            fclose($tempStream);
        }
        return true;
        
    } catch (Exception $e) {
        error_log("Folder creation error: " . $e->getMessage());
        return false;
    }
}

function deleteFromCloudStorage($fileUrl, $bucketName = null) {
    global $bucket, $BUCKET_NAME;
    
    $bucketName = $bucketName ?? $BUCKET_NAME;
    
    // Extract object name from URL
    $objectName = str_replace("https://storage.googleapis.com/" . $bucketName . "/", "", $fileUrl);
    
    if (empty($objectName) || $objectName === $fileUrl) {
        return false;
    }
    
    try {
        // Use gsutil command (most reliable)
        $command = "gsutil rm gs://{$bucketName}/{$objectName} 2>&1";
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return true;
        }
        
        // Fallback: Try PHP method
        $object = $bucket->object($objectName);
        if ($object->exists()) {
            $object->delete();
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Cloud Storage delete error: " . $e->getMessage());
        return false;
    }
}

function deleteFileComplete($userId, $fileId, $fileUrl) {
    // First delete from Cloud Storage
    $storageDeleted = deleteFromCloudStorage($fileUrl);
    
    // Then delete metadata from Firestore
    $metadataDeleted = deleteFileMetadata($userId, $fileId);
    
    return [
        'success' => ($metadataDeleted['success']),
        'storageDeleted' => $storageDeleted,
        'metadataDeleted' => $metadataDeleted['success'],
        'message' => ($storageDeleted ? 'File deleted from Storage and Database' : 'File deleted from database only')
    ];
}
?>
