<?php
/**
 * CivicTrack — User Dashboard
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$complaints = $db->getCollection('complaints');
$userId = $_SESSION['user_id'];

// Stats
$totalComplaints   = $complaints->countDocuments(['user_id' => $userId]);
$pendingComplaints = $complaints->countDocuments(['user_id' => $userId, 'status' => 'Pending']);
$progressComplaints = $complaints->countDocuments(['user_id' => $userId, 'status' => 'In Progress']);
$resolvedComplaints = $complaints->countDocuments(['user_id' => $userId, 'status' => 'Resolved']);

// Recent complaints
$recentComplaints = $complaints->find(
    ['user_id' => $userId],
    ['sort' => ['created_at' => -1], 'limit' => 5]
);

$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$initials = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CivicTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2>🏛️ CivicTrack</h2>
                <span>Citizen Portal</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="submit_complaint.php">
                    <span class="nav-icon">📝</span> Submit Complaint
                </a>
                <a href="my_complaints.php">
                    <span class="nav-icon">📋</span> My Complaints
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php">
                    <span class="nav-icon">🚪</span> Logout
                </a>
            </div>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

            <div class="page-header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($userName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar"><?php echo $initials; ?></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($userName); ?></h3>
                    <p><?php echo htmlspecialchars($userEmail); ?></p>
                    <div class="profile-meta">
                        <span>📋 <?php echo $totalComplaints; ?> complaints filed</span>
                        <span>✅ <?php echo $resolvedComplaints; ?> resolved</span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card purple animate-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-value"><?php echo $totalComplaints; ?></div>
                    <div class="stat-label">Total Complaints</div>
                </div>
                <div class="stat-card orange animate-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $pendingComplaints; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card cyan animate-card">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-value"><?php echo $progressComplaints; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-card green animate-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $resolvedComplaints; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="submit_complaint.php" class="quick-action-card">
                    <span class="action-icon">📝</span>
                    <span class="action-label">New Complaint</span>
                </a>
                <a href="my_complaints.php" class="quick-action-card">
                    <span class="action-icon">📋</span>
                    <span class="action-label">View All Complaints</span>
                </a>
                <a href="my_complaints.php?status=Pending" class="quick-action-card">
                    <span class="action-icon">⏳</span>
                    <span class="action-label">Pending Issues</span>
                </a>
                <a href="my_complaints.php?status=Resolved" class="quick-action-card">
                    <span class="action-icon">✅</span>
                    <span class="action-label">Resolved Issues</span>
                </a>
            </div>

            <!-- Recent Complaints -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Complaints</h3>
                    <a href="my_complaints.php" class="btn btn-outline btn-sm">View All →</a>
                </div>
                <?php
                $recentArr = iterator_to_array($recentComplaints);
                if (empty($recentArr)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>You haven't filed any complaints yet.<br><a href="submit_complaint.php">Submit your first complaint →</a></p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentArr as $c): ?>
                                <tr>
                                    <td style="color: var(--text-primary); font-weight: 500;">
                                        <?php echo htmlspecialchars($c['title']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['category']); ?></td>
                                    <td><?php echo htmlspecialchars($c['date'] ?? $c['created_at']); ?></td>
                                    <td>
                                        <?php
                                        $status = $c['status'] ?? 'Pending';
                                        $badgeClass = 'badge-pending';
                                        if ($status === 'In Progress') $badgeClass = 'badge-progress';
                                        elseif ($status === 'Resolved') $badgeClass = 'badge-resolved';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
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
