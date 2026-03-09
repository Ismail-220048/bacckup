<?php
/**
 * CivicTrack API — Get Heatmap Data
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $complaintsCol = $db->getCollection('complaints');

    $cursor = $complaintsCol->find([]);
    $points = [];
    
    foreach ($cursor as $doc) {
        if (!empty($doc['location'])) {
            $loc = trim($doc['location']);
            $parts = explode(',', $loc);
            
            // Checking if the location string is a valid lat,lng format
            if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                $lat = floatval(trim($parts[0]));
                $lng = floatval(trim($parts[1]));
                // Intensity = 1 for each complaint
                $points[] = [$lat, $lng, 1];
            }
        }
    }

    echo json_encode(['success' => true, 'data' => $points]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
