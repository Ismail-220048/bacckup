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

$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

// Validation
if (empty($email) || empty($password)) {
    header('Location: ../login.php?error=' . urlencode('All fields are required.'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../login.php?error=' . urlencode('Invalid email format.'));
    exit;
}

$db = Database::getInstance();
$users = $db->getCollection('users');

$user = $users->findOne(['email' => $email]);

if (!$user) {
    header('Location: ../login.php?error=' . urlencode('Invalid email or password.'));
    exit;
}

if (!password_verify($password, $user['password'])) {
    header('Location: ../login.php?error=' . urlencode('Invalid email or password.'));
    exit;
}

// Set session
$_SESSION['user_id'] = (string) $user['_id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['role'] = $user['role'] ?? 'user';

header('Location: ../user/dashboard.php');
exit;
