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
$category    = trim($_POST['category'] ?? '');
$description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
$location    = htmlspecialchars(trim($_POST['location'] ?? ''), ENT_QUOTES, 'UTF-8');
$pincode     = htmlspecialchars(trim($_POST['pincode'] ?? ''), ENT_QUOTES, 'UTF-8');
$priority    = htmlspecialchars(trim($_POST['priority'] ?? 'Medium'), ENT_QUOTES, 'UTF-8');
$date        = htmlspecialchars(trim($_POST['date'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8');
$anonymous   = isset($_POST['anonymous']) && $_POST['anonymous'] === 'true';
$accusedRole = htmlspecialchars(trim($_POST['accused_role'] ?? ''), ENT_QUOTES, 'UTF-8');
$subcategory = trim($_POST['subcategory'] ?? '');

require_once __DIR__ . '/../config/Router.php';

// Validation
if (empty($title) || empty($category) || empty($description) || empty($location) || empty($pincode)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit;
}

$validCategories = [
    'Corruption & Bribery', 'Roads & Infrastructure', 'Water Supply Issues',
    'Electricity Problems', 'Sanitation & Garbage', 'Public Transport Issues',
    'Healthcare Complaints', 'Education System Issues', 'Police Misconduct / Law & Order',
    'Government Scheme Issues', 'Land & Property Disputes', 'Cybercrime / Online Fraud',
    'Environmental Issues', 'Women & Child Safety', 'Municipal Services',
    'Tax / Revenue Issues', 'Other'
];
if (!in_array($category, $validCategories)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid category.']);
    exit;
}

// Handle image upload
$imagePath = '';
if (isset($_FILES['image']) && $_FILES['image']['name'] !== '') {
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        
        $errMsg = 'Unknown upload error.';
        if ($_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['image']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errMsg = 'Image size exceeds the allowed system limit (usually 2MB or 5MB). Please compress the image.';
        }
        echo json_encode(['success' => false, 'message' => $errMsg]);
        exit;
    }

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
$actionLogs = $db->getCollection('action_logs');
$users = $db->getCollection('users');

$currentUser = $users->findOne(['_id' => new \MongoDB\BSON\ObjectId($_SESSION['user_id'])]);
$userDistrict = $currentUser['district'] ?? null;
$userState = $currentUser['state'] ?? null;

// Use Router to assign dynamically (Conflict-Aware Routing)
$assignedOfficerId = Router::assignComplaint($category, $userDistrict, $userState, $accusedRole);

// Check for duplicates
$duplicateCount = $complaints->countDocuments([
    'location' => $location,
    'category' => $category
]);

$targetDept = Router::getDepartment($category);

$insertData = [
    'user_id'             => $_SESSION['user_id'],
    'anonymous'           => $anonymous,
    'assigned_officer_id' => $assignedOfficerId,
    'target_department'   => $targetDept,
    'title'               => $title,
    'category'            => $category,
    'subcategory'         => $subcategory,
    'description'         => $description,
    'location'            => $location,
    'pincode'             => $pincode,
    'image'               => $imagePath,
    'district'            => $userDistrict,
    'state'               => $userState,
    'date'                => $date,
    'priority'            => $priority,
    'accused_role'        => $accusedRole,
    'status'              => 'Pending',
    'admin_reply'         => '',
    'additional_user_ids' => [],
    'created_at'          => date('Y-m-d H:i:s')
];

if ($assignedOfficerId) {
    $insertData['assigned_timestamp'] = date('d M Y, h:i A');
}

$result = $complaints->insertOne($insertData);

if ($result->getInsertedCount() > 0) {
    // Write initial log
    $actionLogs->insertOne([
        'complaint_id' => (string) $result->getInsertedId(),
        'performed_by' => $_SESSION['user_id'],
        'role'         => 'citizen',
        'action'       => 'Complaint Filed',
        'comment'      => 'Sys: Auto-assigned to department.',
        'timestamp'    => date('Y-m-d H:i:s')
    ]);

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
