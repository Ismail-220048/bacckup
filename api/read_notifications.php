<?php
/**
 * CivicTrack API — Read/Mark Notifications
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

try {
    $db = Database::getInstance();
    $notifications = $db->getCollection('notifications');

    if (isset($input['notification_id'])) {
        $notifications->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($input['notification_id'])],
            ['$set' => ['is_read' => true]]
        );
    } elseif (isset($input['mark_all'])) {
        $query = ($_SESSION['role'] === 'admin') ? ['role' => 'admin'] : ['user_id' => $_SESSION['user_id']];
        $notifications->updateMany(
            $query, 
            ['$set' => ['is_read' => true]]
        );
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
