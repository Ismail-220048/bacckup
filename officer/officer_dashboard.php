<?php
/**
 * CivicTrack — Officer Dashboard
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'officer') {
    header('Location: officer_login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$complaintsCol = $db->getCollection('complaints');
$officerId = $_SESSION['user_id'];

// Stats for this officer
$assignedTotal   = $complaintsCol->countDocuments(['assigned_officer_id' => $officerId]);
$assignedPending = $complaintsCol->countDocuments(['assigned_officer_id' => $officerId, 'status' => 'Pending']);
$assignedProgress = $complaintsCol->countDocuments(['assigned_officer_id' => $officerId, 'status' => 'In Progress']);
$assignedResolved = $complaintsCol->countDocuments(['assigned_officer_id' => $officerId, 'status' => 'Resolved']);

// Recent assigned complaints
$recentComplaints = $complaintsCol->find(
    ['assigned_officer_id' => $officerId],
    ['sort' => ['created_at' => -1], 'limit' => 8]
);
$recentArr = iterator_to_array($recentComplaints);

$officerName = $_SESSION['user_name'] ?? 'Officer';
$initials = strtoupper(substr($officerName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard — CivicTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2>👮 CivicTrack</h2>
                <span>Officer Portal</span>
            </div>
            <nav class="sidebar-nav">
                <a href="officer_dashboard.php" class="active">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="my_assignments.php">
                    <span class="nav-icon">📋</span> My Assignments
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
                <h1>Officer Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($officerName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar"><?php echo $initials; ?></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($officerName); ?></h3>
                    <p><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                    <div class="profile-meta">
                        <span>📋 <?php echo $assignedTotal; ?> assigned</span>
                        <span>✅ <?php echo $assignedResolved; ?> resolved</span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card purple animate-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-value"><?php echo $assignedTotal; ?></div>
                    <div class="stat-label">Total Assigned</div>
                </div>
                <div class="stat-card orange animate-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $assignedPending; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card cyan animate-card">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-value"><?php echo $assignedProgress; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-card green animate-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $assignedResolved; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>

            <!-- Quick Actions & Highlights -->
            <div class="dashboard-widgets" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="quick-actions" style="margin-bottom: 0; height: 100%;">
                    <a href="my_assignments.php" class="quick-action-card">
                        <span class="action-icon">📋</span>
                        <span class="action-label">All Assignments</span>
                    </a>
                    <a href="my_assignments.php?status=Pending" class="quick-action-card">
                        <span class="action-icon">⏳</span>
                        <span class="action-label">Pending (<?php echo $assignedPending; ?>)</span>
                    </a>
                    <a href="my_assignments.php?status=In Progress" class="quick-action-card">
                        <span class="action-icon">🔄</span>
                        <span class="action-label">In Progress (<?php echo $assignedProgress; ?>)</span>
                    </a>
                    <a href="my_assignments.php?status=Resolved" class="quick-action-card">
                        <span class="action-icon">✅</span>
                        <span class="action-label">Resolved (<?php echo $assignedResolved; ?>)</span>
                    </a>
                </div>

                <div class="illustration-card" style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: var(--shadow-sm); transition: transform 0.3s ease;">
                    <img src="../assets/images/abstract-reports.png" alt="Officer Tasks" style="max-height: 120px; object-fit: contain; margin-bottom: 1rem; filter: drop-shadow(0 10px 15px rgba(16,185,129,0.25)) hue-rotate(90deg); border-radius: 12px;">
                     <h3 style="font-size: 1.05rem; margin-bottom: 0.35rem; color: var(--text-white);">Task Management</h3>
                     <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">Investigate, update, and resolve civic complaints effectively.</p>
                </div>
            </div>

            <!-- Recent Assignments -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Recent Assignments</h3>
                    <a href="my_assignments.php" class="btn btn-outline btn-sm">View All →</a>
                </div>
                <?php if (empty($recentArr)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>No complaints assigned to you yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentArr as $c): ?>
                                <tr>
                                    <td style="color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($c['title']); ?></td>
                                    <td><?php echo htmlspecialchars($c['category']); ?></td>
                                    <td><?php echo htmlspecialchars($c['location'] ?? ''); ?></td>
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
