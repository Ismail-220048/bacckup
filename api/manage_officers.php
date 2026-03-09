<?php
/**
 * CivicTrack API — Manage Officers (Admin only)
 * 
 * POST JSON body:
 *   action — "add" or "delete"
 *   For add: name, email, phone, password
 *   For delete: officer_id
 *
 * GET: Returns list of all officers
 */
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$db = Database::getInstance();
$officers = $db->getCollection('officers');

// GET — list all officers
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cursor = $officers->find([], ['sort' => ['created_at' => -1]]);
    $result = [];
    foreach ($cursor as $o) {
        $result[] = [
            '_id'        => (string) $o['_id'],
            'name'       => $o['name'],
            'email'      => $o['email'],
            'phone'      => $o['phone'] ?? '',
            'created_at' => $o['created_at'] ?? ''
        ];
    }
    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// POST — add or delete officer
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Try form data
    $input = $_POST;
}

$action = $input['action'] ?? '';

if ($action === 'add') {
    $name     = htmlspecialchars(trim($input['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email    = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone    = htmlspecialchars(trim($input['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $password = $input['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Name, email, and password are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    // Check duplicate
    $existing = $officers->findOne(['email' => $email]);
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Officer with this email already exists.']);
        exit;
    }

    $officers->insertOne([
        'name'       => $name,
        'email'      => $email,
        'phone'      => $phone,
        'password'   => password_hash($password, PASSWORD_BCRYPT),
        'role'       => 'officer',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    echo json_encode(['success' => true, 'message' => 'Officer added successfully.']);
    exit;
}

if ($action === 'delete') {
    $officerId = $input['officer_id'] ?? '';
    if (empty($officerId)) {
        echo json_encode(['success' => false, 'message' => 'Officer ID is required.']);
        exit;
    }

    try {
        $objectId = new MongoDB\BSON\ObjectId($officerId);
        $result = $officers->deleteOne(['_id' => $objectId]);

        // Unassign complaints from this officer
        $complaintsCol = $db->getCollection('complaints');
        $complaintsCol->updateMany(
            ['assigned_officer_id' => $officerId],
            ['$set' => ['assigned_officer_id' => '', 'assigned_officer_name' => '']]
        );

        if ($result->getDeletedCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Officer deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Officer not found.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Invalid officer ID.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
