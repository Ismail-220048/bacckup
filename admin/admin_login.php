<?php
/**
 * CivicTrack — Admin Login Page
 */
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/database.php';

    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $db = Database::getInstance();
        $admins = $db->getCollection('admins');
        $admin = $admins->findOne(['email' => $email]);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id']    = (string) $admin['_id'];
            $_SESSION['user_name']  = $admin['name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['role']       = 'admin';
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — CivicTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="logo">
                <h1>🛡️ Admin Portal</h1>
                <p>CivicTrack Administration</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">👋 You have been logged out.</div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@civictrack.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Admin Sign In</button>
            </form>

            <div class="auth-footer">
                <a href="../login.php">← Back to User Login</a>
            </div>
        </div>
    </div>
</body>
</html>
