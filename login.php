<?php
/**
 * CivicTrack — User Login Page
 */
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user') {
    header('Location: user/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CivicTrack — Login to report and track civic complaints in your community.">
    <title>Login — CivicTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="logo">
                <h1>🏛️ CivicTrack</h1>
                <p>Report civic issues. Track progress. Build better communities.</p>
            </div>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">✅ Registration successful! Please login.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">👋 You have been logged out.</div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="api/login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Create one</a>
                <br><br>
                <a href="admin/admin_login.php" style="color: var(--text-muted); font-size: 0.82rem;">Admin Login →</a>
                &nbsp;|&nbsp;
                <a href="officer/officer_login.php" style="color: var(--text-muted); font-size: 0.82rem;">Officer Login →</a>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (!validateLoginForm(this)) e.preventDefault();
        });
    </script>
</body>
</html>
