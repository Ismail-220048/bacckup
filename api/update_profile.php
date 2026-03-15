<?php
/**
 * CivicTrack API — Update User/Officer Profile
 */
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['user', 'officer'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

$name  = htmlspecialchars(trim($input['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars(trim($input['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$password = $input['password'] ?? ''; // Optional

if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

$db = Database::getInstance();
$collectionName = $_SESSION['role'] === 'user' ? 'users' : 'officers';
$collection = $db->getCollection($collectionName);

try {
    $userId = new MongoDB\BSON\ObjectId($_SESSION['user_id']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

// Check for duplicate email across other accounts
$existingEmail = $collection->findOne([
    'email' => $email,
    '_id' => ['$ne' => $userId]
]);
if ($existingEmail) {
    echo json_encode(['success' => false, 'message' => 'Email is already in use by another account.']);
    exit;
}

$updateFields = [
    'name'  => $name,
    'email' => $email,
    'phone' => $phone
];

if (!empty($password)) {
    $updateFields['password'] = password_hash($password, PASSWORD_BCRYPT);
}

$result = $collection->updateOne(
    ['_id' => $userId],
    ['$set' => $updateFields]
);

// Update session variables if they changed
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;

// If officer, we also need to update their name on complaints assigned to them (for consistency)
if ($_SESSION['role'] === 'officer') {
    $complaints = $db->getCollection('complaints');
    $complaints->updateMany(
        ['assigned_officer_id' => $_SESSION['user_id']],
        ['$set' => ['assigned_officer_name' => $name]]
    );
}

echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
