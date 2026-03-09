<?php
/**
 * CivicTrack API — Submit Complaint
 */
session_start();
require_once __DIR__ . '/../config/database.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$title       = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');
$category    = htmlspecialchars(trim($_POST['category'] ?? ''), ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
$location    = htmlspecialchars(trim($_POST['location'] ?? ''), ENT_QUOTES, 'UTF-8');
$date        = htmlspecialchars(trim($_POST['date'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8');

// Validation
if (empty($title) || empty($category) || empty($description) || empty($location)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit;
}

$validCategories = ['Road Damage', 'Garbage', 'Water Leakage', 'Street Light Issue', 'Drainage Problem', 'Other'];
if (!in_array($category, $validCategories)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid category.']);
    exit;
}

// Handle image upload
$imagePath = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Allowed: JPG, PNG, GIF, WebP.']);
        exit;
    }

    if ($file['size'] > $maxSize) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Image size must be under 5MB.']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('complaint_', true) . '.' . strtolower($ext);
    $uploadDir = __DIR__ . '/../uploads/complaints/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $imagePath = 'uploads/complaints/' . $filename;
    }
}

$db = Database::getInstance();
$complaints = $db->getCollection('complaints');

// Check for duplicates
$duplicateCount = $complaints->countDocuments([
    'location' => $location,
    'category' => $category
]);

$result = $complaints->insertOne([
    'user_id'             => $_SESSION['user_id'],
    'title'               => $title,
    'category'            => $category,
    'description'         => $description,
    'location'            => $location,
    'image'               => $imagePath,
    'date'                => $date,
    'status'              => 'Pending',
    'admin_reply'         => '',
    'additional_user_ids' => [],
    'created_at'          => date('Y-m-d H:i:s')
]);

if ($result->getInsertedCount() > 0) {
    // Notify Admins
    $notifications = $db->getCollection('notifications');
    
    if ($duplicateCount > 0) {
        $notifications->insertOne([
            'role'       => 'admin',
            'message'    => "Duplicate location and category detected for new complaint: " . $title . ". Consider merging.",
            'is_read'    => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        $notifications->insertOne([
            'role'       => 'admin',
            'message'    => "New user complaint submitted: " . $title,
            'is_read'    => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Complaint submitted successfully!']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to submit complaint.']);
}
exit;
