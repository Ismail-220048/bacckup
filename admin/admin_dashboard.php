<?php
/**
 * CivicTrack — Admin Dashboard
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$complaintsCol = $db->getCollection('complaints');
$usersCol = $db->getCollection('users');
$officersCol = $db->getCollection('officers');

// Stats Calculation
$totalComplaints = $complaintsCol->countDocuments();
$pendingCount = $complaintsCol->countDocuments(['status' => 'Pending']);
$progressCount = $complaintsCol->countDocuments(['status' => 'In Progress']);
$resolvedCount = $complaintsCol->countDocuments(['status' => 'Resolved']);

$totalUsers = $usersCol->countDocuments();
$totalOfficers = $officersCol->countDocuments();

// Recent Complaints
$recentComplaints = $complaintsCol->find([], ['limit' => 5, 'sort' => ['created_at' => -1]]);
$recentArr = iterator_to_array($recentComplaints);

// User Lookup for recent complaints
$userIds = array_unique(array_map(fn($c) => $c['user_id'] ?? '', $recentArr));
$userLookup = [];
if (!empty($userIds)) {
    $foundUsers = $usersCol->find(['_id' => ['$in' => array_map(fn($id) => new \MongoDB\BSON\ObjectId($id), array_filter($userIds))]]);
    foreach ($foundUsers as $u) {
        $userLookup[(string)$u['_id']] = $u['name'];
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
    <title>Admin Dashboard | CivicTrack</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-theme">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-inner">
                    <img src="../assets/images/govt_emblem.png" class="sidebar-emblem" alt="Gov Emblem">
                    <div class="sidebar-brand-text">
                        <span>Republic of India</span>
                        <h2>CivicTrack</h2>
                    </div>
                </div>
                <div class="sidebar-gold-stripe"></div>
            </div>

            <div class="sidebar-nav">
                <div class="sidebar-section-label">Main Console</div>
                <a href="admin_dashboard.php" class="active">📊 Dashboard</a>
                <a href="manage_complaints.php">📋 All Complaints</a>
                <a href="manage_users.php">👥 Manage Citizens</a>
                <a href="manage_officers.php">👮 Manage Officers</a>
                
                <div class="sidebar-section-label">Analytics</div>
                <a href="heatmap.php">🗺️ Heatmap</a>
            </div>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="user-avatar"><?php echo $initials; ?></div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($adminName); ?></span>
                        <span class="sidebar-user-role">🛡️ Administrator</span>
                    </div>
                </div>
                <a href="../logout.php" class="logout-link">🚪 Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <!-- Page Header -->
            <div class="page-header">
                                <div class="header-left">
                    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
                    <div class="header-logo-group">
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(200,146,42,0.3));">
                        <span>CivicTrack</span>
                    </div>
                    <div>
                        <h1>📊 Admin Dashboard</h1>
                        <div class="breadcrumb">
                            <a href="admin_dashboard.php">Home</a>
                            <span>›</span>
                            <span>Dashboard Overview</span>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span style="font-size:0.82rem; color:var(--text-muted);"><?php echo date('d M Y'); ?></span>
                    <span><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <!-- Profile/Welcome Banner -->
            <div class="profile-card">
                <div class="profile-avatar"><?php echo $initials; ?></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($adminName); ?> — Administrator</h3>
                    <p>System Management &amp; Oversight · CivicTrack Administration Console</p>
                    <div class="profile-meta">
                        <span>🏛️ Admin Panel</span>
                        <span>📅 <?php echo date('d M Y, H:i'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <!-- Charts Grid replaces Stats Grid -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); margin-bottom: 2rem;">
                <!-- User Distribution Chart -->
                <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; align-items: center;">
                    <h3 style="margin-bottom: 1rem; font-size: 1rem; color: var(--text-primary);">👥 Workforce Distribution</h3>
                    <div style="width: 100%; height: 220px;">
                        <canvas id="userDistChart"></canvas>
                    </div>
                    <div style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                         Total Participants: <strong><?php echo ($totalUsers + $totalOfficers); ?></strong>
                    </div>
                </div>

                <!-- Complaint Breakdown Chart -->
                <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; align-items: center;">
                    <h3 style="margin-bottom: 1rem; font-size: 1rem; color: var(--text-primary);">📋 Complaint Lifecycle</h3>
                    <div style="width: 100%; height: 220px;">
                        <canvas id="complaintStatusChart"></canvas>
                    </div>
                    <div style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                         Total Reports: <strong><?php echo $totalComplaints; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Illustration -->
            <!-- Quick Actions -->
            <div class="dashboard-widgets" style="margin-bottom: 1.75rem;">
                <div class="gov-section-heading">
                    <h2>Quick Actions</h2>
                </div>
                <div class="quick-actions" style="margin-bottom: 0;">
                    <a href="manage_complaints.php" class="quick-action-card">
                        <span class="action-icon">📋</span>
                        <span class="action-label">All Complaints</span>
                    </a>
                    <a href="manage_complaints.php?status=Pending" class="quick-action-card">
                        <span class="action-icon">⏳</span>
                        <span class="action-label">Pending Cases</span>
                    </a>
                    <a href="manage_complaints.php?status=In Progress" class="quick-action-card">
                        <span class="action-icon">🔄</span>
                        <span class="action-label">In Progress</span>
                    </a>
                    <a href="manage_users.php" class="quick-action-card">
                        <span class="action-icon">👥</span>
                        <span class="action-label">Manage Citizens</span>
                    </a>
                    <a href="manage_officers.php" class="quick-action-card">
                        <span class="action-icon">👮</span>
                        <span class="action-label">Manage Officers</span>
                    </a>
                    <a href="heatmap.php" class="quick-action-card">
                        <span class="action-icon">🗺️</span>
                        <span class="action-label">Issue Heatmap</span>
                    </a>
                </div>
            </div>

            <!-- Recent Complaints Table -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Recent Complaints</h3>
                    <a href="manage_complaints.php" class="btn btn-outline btn-sm">View All →</a>
                </div>
                <?php if (empty($recentArr)): ?>
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <p>No complaints submitted yet.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Complaint Title</th>
                                    <th>Category</th>
                                    <th>Citizen</th>
                                    <th>Date Filed</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentArr as $c): ?>
                                <tr>
                                    <td style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars($c['title']); ?></td>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15,
                            font: { size: 11, family: 'Inter' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 10,
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 }
                    }
                },
                cutout: '65%'
            };

            // User Distribution Chart
            new Chart(document.getElementById('userDistChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Citizens', 'Officers'],
                    datasets: [{
                        data: [<?php echo $totalUsers; ?>, <?php echo $totalOfficers; ?>],
                        backgroundColor: ['#8b5cf6', '#06b6d4'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: chartOptions
            });

            // Complaint Lifecycle Chart
            new Chart(document.getElementById('complaintStatusChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Pending', 'In Progress', 'Resolved'],
                    datasets: [{
                        data: [<?php echo $pendingCount; ?>, <?php echo $progressCount; ?>, <?php echo $resolvedCount; ?>],
                        backgroundColor: ['#f59e0b', '#0ea5e9', '#10b981'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    ...chartOptions,
                    cutout: '0%' // Full Pie for contrast
                }
            });
        });
    </script>
</body>
</html>
