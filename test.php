<?php
require_once __DIR__ . '/includes/db_functions.php';

echo "<h2>Firestore Connection Test</h2>";

// Try to write a test document
try {
    $testId = 'test_' . time();
    $db->collection('test')->document($testId)->set([
        'message' => 'Connection successful',
        'timestamp' => new DateTime()
    ]);
    
    echo "<p style='color:green'>✅ SUCCESS: Wrote to Firestore!</p>";
    
    // Try to read it back
    $snapshot = $db->collection('test')->document($testId)->snapshot();
    if ($snapshot->exists()) {
        echo "<p style='color:green'>✅ SUCCESS: Read from Firestore! Message: " . $snapshot->get('message') . "</p>";
    }
    
    // Clean up
    $db->collection('test')->document($testId)->delete();
    echo "<p style='color:green'>✅ Database is fully working!</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<p>Check that your VM's service account has 'datastore.user' role.</p>";
}
?>
