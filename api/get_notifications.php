<?php
/**
 * CivicTrack API — Get Notifications
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
    $notifications = $db->getCollection('notifications');

    $role = $_SESSION['role'] ?? '';
    $userId = $_SESSION['user_id'] ?? '';

    $query = [];
    if ($role === 'admin') {
        $query = ['role' => 'admin'];
    } else {
        $query = ['user_id' => $userId];
    }

    $cursor = $notifications->find($query, ['sort' => ['created_at' => -1], 'limit' => 20]);
    $notifs = [];
    $unreadCount = 0;
    
    foreach ($cursor as $doc) {
        $isRead = $doc['is_read'] ?? false;
        if (!$isRead) $unreadCount++;
        
        $notifs[] = [
            'id' => (string) $doc['_id'],
            'message' => $doc['message'],
            'created_at' => $doc['created_at'],
            'is_read' => $isRead
        ];
    }

    echo json_encode([
        'success' => true, 
        'notifications' => $notifs,
        'unread_count' => $unreadCount
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
