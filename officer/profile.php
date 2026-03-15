<?php
/**
 * CivicTrack — Officer Profile
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'officer') {
    header('Location: officer_login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$collection = $db->getCollection('officers');
$userId = $_SESSION['user_id'];

try {
    $userDoc = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
} catch (Exception $e) {
    echo "Invalid officer ID.";
    exit;
}

if (!$userDoc) {
    echo "Officer not found.";
    exit;
}

$userName = $userDoc['name'] ?? 'Officer';
$userEmail = $userDoc['email'] ?? '';
$userPhone = $userDoc['phone'] ?? '';
$initials = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — CivicTrack Officer Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Serif:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-inner">
                    <img src="../assets/images/govt_emblem.png" alt="Emblem" class="sidebar-emblem">
                    <div class="sidebar-brand-text">
                        <h2>CivicTrack</h2>
                        <span>Field Officer Portal</span>
                    </div>
                </div>
            </div>
            <div class="sidebar-gold-stripe"></div>
            <nav class="sidebar-nav">
                <div class="sidebar-section-label">Navigation</div>
                <a href="officer_dashboard.php">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="my_assignments.php">
                    <span class="nav-icon">📋</span> My Assignments
                </a>
                <a href="profile.php" class="active">
                    <span class="nav-icon">👤</span> My Profile
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar" id="sidebar-avatar"><?php echo $initials; ?></div>
                    <div>
                        <div class="sidebar-user-name" id="sidebar-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="sidebar-user-role">Field Officer</div>
                    </div>
                </div>
                <a href="../logout.php">
                    <i class="fa fa-sign-out"></i> Logout
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
                    <div>
                        <h1>👤 My Profile</h1>
                        <div class="breadcrumb">
                            <a href="officer_dashboard.php">Home</a>
                            <span>›</span>
                            <span>My Profile</span>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span style="font-size:0.82rem; color:var(--text-muted);"><?php echo date('d M Y'); ?></span>
                    <span id="header-name"><?php echo htmlspecialchars($userName); ?></span>
                    <div class="user-avatar" id="header-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <div class="page-body">
                <div class="profile-container">
                    <h2 style="margin-bottom: 1.5rem; font-family: var(--font-serif); color: var(--gov-navy);">Edit Personal Information</h2>
                    <form id="profileForm">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userName); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userPhone); ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">Change Password <small style="color:var(--text-muted);">(Leave blank to keep unchanged)</small></label>
                            <input type="password" id="password" name="password" placeholder="Min. 6 characters">
                        </div>
                        <p style="font-size: 0.82rem; margin-bottom: 1rem; color: var(--text-muted);">Please save your changes before leaving this page.</p>
                        <button type="submit" class="btn btn-primary btn-block">💾 Save Changes</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;

            if (!name || !email) {
                showToast('Name and email are required.', 'error');
                return;
            }

            const payload = { name, email, phone };
            if (password) payload.password = password;

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            const result = await postJSON('../api/update_profile.php', payload);

            if (result.success) {
                showToast(result.message, 'success');
                // Update UI visually
                const initials = name.charAt(0).toUpperCase();
                document.getElementById('sidebar-name').innerText = name;
                document.getElementById('header-name').innerText = name;
                document.getElementById('sidebar-avatar').innerText = initials;
                document.getElementById('header-avatar').innerText = initials;
            } else {
                showToast(result.message, 'error');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = '💾 Save Changes';
        });
    </script>
</body>
</html>
