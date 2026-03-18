<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/google.php';

echo "Testing environment...\n";

// Test Google Client class
try {
    $client = new Google\Client();
    echo "✅ Google\Client class found.\n";
} catch (Error $e) {
    echo "❌ Google\Client class NOT found: " . $e->getMessage() . "\n";
}

// Test Database Connection
try {
    $db = Database::getInstance();
    $coll = $db->getCollection('users');
    $count = $coll->countDocuments();
    echo "✅ MongoDB connected. Users count: $count\n";
} catch (Exception $e) {
    echo "❌ MongoDB connection failed: " . $e->getMessage() . "\n";
}

echo "Testing complete.\n";
