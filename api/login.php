<?php
/**
 * CivicTrack API — User Login
 */
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// Support both JSON and standard POST
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $email    = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $input['password'] ?? '';
} else {
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
}

// Validation
if (empty($email) || empty($password)) {
    $errorMsg = 'All fields are required.';
    if ($input) {
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    } else {
        header('Location: ../login.php?error=' . urlencode($errorMsg));
    }
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorMsg = 'Invalid email format.';
    if ($input) {
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    } else {
        header('Location: ../login.php?error=' . urlencode($errorMsg));
    }
    exit;
}

$db = Database::getInstance();
$users = $db->getCollection('users');

$user = $users->findOne(['email' => $email]);

if (!$user || !password_verify($password, $user['password'])) {
    $errorMsg = 'Invalid email or password.';
    if ($input) {
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    } else {
        header('Location: ../login.php?error=' . urlencode($errorMsg));
    }
    exit;
}

// Set session
$_SESSION['user_id'] = (string) $user['_id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['role'] = $user['role'] ?? 'user';

if ($input) {
    echo json_encode(['success' => true, 'role' => $_SESSION['role']]);
} else {
    // Redirect based on role
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/admin_dashboard.php');
    } elseif ($_SESSION['role'] === 'officer') {
        header('Location: ../officer/officer_dashboard.php');
    } else {
        header('Location: ../user/dashboard.php');
    }
}
exit;
