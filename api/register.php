<?php
/**
 * CivicTrack API — User Registration
 */
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

$name     = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone    = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

// Validation
if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm)) {
    header('Location: ../register.php?error=' . urlencode('All fields are required.'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../register.php?error=' . urlencode('Invalid email format.'));
    exit;
}

if (strlen($password) < 6) {
    header('Location: ../register.php?error=' . urlencode('Password must be at least 6 characters.'));
    exit;
}

if ($password !== $confirm) {
    header('Location: ../register.php?error=' . urlencode('Passwords do not match.'));
    exit;
}

$db = Database::getInstance();
$users = $db->getCollection('users');

// Check duplicate email
$existing = $users->findOne(['email' => $email]);
if ($existing) {
    header('Location: ../register.php?error=' . urlencode('Email already registered.'));
    exit;
}

// Insert user
$users->insertOne([
    'name'       => $name,
    'email'      => $email,
    'phone'      => $phone,
    'password'   => password_hash($password, PASSWORD_BCRYPT),
    'role'       => 'user',
    'created_at' => date('Y-m-d H:i:s')
]);

header('Location: ../login.php?registered=1');
exit;
