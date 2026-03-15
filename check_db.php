<?php
/**
 * Standalone Database Connection Test for CivicTrack
 */
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

echo "--- CivicTrack Database Connection Test ---\n";

try {
    $dbInstance = Database::getInstance();
    $db = $dbInstance->getDatabase();
    
    echo "1. Instance created successfully.\n";
    
    // Attempt to list collections as a connectivity check
    $collections = $db->listCollections();
    echo "2. Successfully listed collections:\n";
    
    foreach ($collections as $collection) {
        echo "   - " . $collection->getName() . "\n";
    }
    
    echo "\n[SUCCESS] Database connection is working perfectly!\n";

} catch (Exception $e) {
    echo "\n[FAILURE] Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    echo "\nTroubleshooting Tips:\n";
    echo "1. Ensure MongoDB service is running: 'sudo systemctl start mongod'\n";
    echo "2. Check if MongoDB is listening on port 27017: 'ss -antl | grep 27017'\n";
    echo "3. Verify that the 'mongodb' PHP extension is installed and enabled.\n";
}
