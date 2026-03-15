<?php
/**
 * CivicTrack — Citizen Login
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
    <meta name="description" content="CivicTrack — Citizen Login. Access the official government civic complaint portal.">
    <title>Citizen Login — CivicTrack Official Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Serif:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page-wrapper">

    <!-- Government Header -->
    <div class="auth-gov-header">
        <img src="assets/images/govt_emblem.png" alt="Government Emblem" class="emblem">
    
        <div class="portal-text">
            <h1>CivicTrack — Citizen Services Portal</h1>
            <p>Ministry of Urban Development &amp; Civic Affairs · Government of India</p>
        </div>
    </div>

    <!-- Auth Body -->
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="logo">
                <img src="assets/images/govt_emblem.png" alt="CivicTrack Emblem" class="gov-emblem-sm">
                <h1>Citizen Login</h1>
                <p>Enter your registered credentials to access the portal</p>
            </div>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">✅ Registration successful! Please login with your credentials.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">👋 You have been securely logged out.</div>
            <?php endif; ?>

            <div class="auth-section-title">Login Credentials</div>

            <form id="loginForm" method="POST" action="api/login.php">
                <div class="form-group">
                    <label for="email">Registered Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" placeholder="your@email.gov.in" required>
                </div>
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:0.5rem; padding: 0.75rem;">
                    🔐 Sign In to Portal
                </button>
            </form>

            <div class="auth-footer">
                New citizen? <a href="register.php">Register on the portal</a>
                <br><br>
                <a href="admin/admin_login.php" style="color: var(--text-muted); font-size: 0.78rem;">Admin Login →</a>
                &nbsp;|&nbsp;
                <a href="officer/officer_login.php" style="color: var(--text-muted); font-size: 0.78rem;">Officer Login →</a>
            </div>
        </div>
    </div>

    <!-- Government Footer -->
    <div class="auth-gov-footer">
        © 2026 CivicTrack — Official Citizen Services Portal. Government of India. All rights reserved. |
        <a href="#">Privacy Policy</a> | <a href="#">Terms of Use</a> | <a href="#">Help</a>
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
