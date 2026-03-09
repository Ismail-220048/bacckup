<?php
/**
 * CivicTrack — Admin Dashboard
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$usersCol      = $db->getCollection('users');
$complaintsCol = $db->getCollection('complaints');
$officersCol   = $db->getCollection('officers');

$totalUsers       = $usersCol->countDocuments();
$totalOfficers    = $officersCol->countDocuments();
$totalComplaints  = $complaintsCol->countDocuments();
$pendingCount     = $complaintsCol->countDocuments(['status' => 'Pending']);
$progressCount    = $complaintsCol->countDocuments(['status' => 'In Progress']);
$resolvedCount    = $complaintsCol->countDocuments(['status' => 'Resolved']);

// Recent complaints
$recentComplaints = $complaintsCol->find([], ['sort' => ['created_at' => -1], 'limit' => 8]);
$recentArr = iterator_to_array($recentComplaints);

// Build user lookup for names
$userIds = array_unique(array_map(fn($c) => $c['user_id'] ?? '', $recentArr));
$userLookup = [];
foreach ($userIds as $uid) {
    if ($uid) {
        try {
            $u = $usersCol->findOne(['_id' => new MongoDB\BSON\ObjectId($uid)]);
            if ($u) $userLookup[$uid] = $u['name'];
        } catch (Exception $e) {}
    }
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — CivicTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2>🛡️ CivicTrack</h2>
                <span>Admin Panel</span>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="active">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="heatmap.php">
                    <span class="nav-icon">🗺️</span> Heatmap
                </a>
                <a href="manage_complaints.php">
                    <span class="nav-icon">📋</span> Manage Complaints
                </a>
                <a href="manage_users.php">
                    <span class="nav-icon">👥</span> Manage Users
                </a>
                <a href="manage_officers.php">
                    <span class="nav-icon">👮</span> Manage Officers
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php">
                    Logout <i class="fa fa-sign-out" style="margin-left: auto; font-size: 1.1rem;"></i>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <!-- Profile Card Banner -->
            <div class="profile-card">
                <div class="profile-avatar"><?php echo $initials; ?></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($adminName); ?> (Administrator)</h3>
                    <p>System Management and Overview</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card purple animate-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card cyan animate-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-value"><?php echo $totalComplaints; ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
                <div class="stat-card orange animate-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $pendingCount; ?></div>
                    <div class="stat-label">Pending Complaints</div>
                </div>
                <div class="stat-card green animate-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $resolvedCount; ?></div>
                    <div class="stat-label">Resolved Complaints</div>
                </div>
            </div>

            <!-- Officer Stat -->
            <div class="stats-grid" style="margin-top: -0.75rem;">
                <div class="stat-card cyan animate-card">
                    <div class="stat-icon">👮</div>
                    <div class="stat-value"><?php echo $totalOfficers; ?></div>
                    <div class="stat-label">Total Officers</div>
                </div>
            </div>

            <!-- Quick Actions & Highlights -->
            <div class="dashboard-widgets" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="quick-actions" style="margin-bottom: 0; height: 100%;">
                    <a href="manage_complaints.php" class="quick-action-card">
                        <span class="action-icon">📋</span>
                        <span class="action-label">All Complaints</span>
                    </a>
                    <a href="manage_complaints.php?status=Pending" class="quick-action-card">
                        <span class="action-icon">⏳</span>
                        <span class="action-label">Pending (<?php echo $pendingCount; ?>)</span>
                    </a>
                    <a href="manage_complaints.php?status=In Progress" class="quick-action-card">
                        <span class="action-icon">🔄</span>
                        <span class="action-label">In Progress (<?php echo $progressCount; ?>)</span>
                    </a>
                    <a href="manage_users.php" class="quick-action-card">
                        <span class="action-icon">👥</span>
                        <span class="action-label">Manage Users</span>
                    </a>
                    <a href="manage_officers.php" class="quick-action-card">
                        <span class="action-icon">👮</span>
                        <span class="action-label">Manage Officers</span>
                    </a>
                </div>

                <div class="illustration-card" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: var(--shadow-sm); transition: transform 0.3s ease;">
                    <img src="../assets/images/admin-illustration.png" alt="City Administration" style="max-height: 140px; object-fit: contain; margin-bottom: 1rem; filter: drop-shadow(0 10px 15px rgba(6,182,212,0.25)); border-radius: 12px;">
                     <h3 style="font-size: 1.05rem; margin-bottom: 0.35rem; color: var(--text-white);">City Administration</h3>
                     <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">Monitor resolving civic issues and overview complete city data reliably.</p>
                </div>
            </div>

            <!-- Recent Complaints -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Recent Complaints</h3>
                    <a href="manage_complaints.php" class="btn btn-outline btn-sm">View All →</a>
                </div>
                <?php if (empty($recentArr)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>No complaints yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>User</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentArr as $c): ?>
                                <tr>
                                    <td style="color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($c['title']); ?></td>
                                    <td><?php echo htmlspecialchars($c['category']); ?></td>
                                    <td><?php echo htmlspecialchars($userLookup[$c['user_id'] ?? ''] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($c['date'] ?? $c['created_at']); ?></td>
                                    <td>
                                        <?php
                                        $status = $c['status'] ?? 'Pending';
                                        $bc = 'badge-pending';
                                        if ($status === 'In Progress') $bc = 'badge-progress';
                                        elseif ($status === 'Resolved') $bc = 'badge-resolved';
                                        ?>
                                        <span class="badge <?php echo $bc; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
