<?php
/**
 * CivicTrack India — State Admin Dashboard (Head Admin)
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'state_admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$complaintsCol   = $db->getCollection('complaints');
$usersCol        = $db->getCollection('users');
$headOfficersCol = $db->getCollection('head_officers');
$fieldOfficersCol = $db->getCollection('field_officers');
$departmentsCol  = $db->getCollection('departments');

$myState = $_SESSION['state'] ?? 'Unknown';
$baseFilter = ['state' => $myState];

// Filter by District if selected
if (!empty($_GET['district_filter'])) {
    $baseFilter['district'] = $_GET['district_filter'];
}

// Add departmental filter if the admin is assigned to a specific department
if (!empty($_SESSION['department'])) {
    $baseFilter['target_department'] = $_SESSION['department'];
}

$districtsList = $complaintsCol->distinct('district', ['state' => $myState]);

// Stats Calculation (State - Specific)
$totalComplaints = $complaintsCol->countDocuments($baseFilter);
$pendingCount = $complaintsCol->countDocuments(array_merge($baseFilter, ['status' => ['$in' => ['Pending', 'Submitted', 'Assigned', 'Under Review']]]));
$progressCount = $complaintsCol->countDocuments(array_merge($baseFilter, ['status' => ['$in' => ['In Progress', 'Escalated']]]));
$resolvedCount = $complaintsCol->countDocuments(array_merge($baseFilter, ['status' => ['$in' => ['Resolved', 'Closed', 'Officer Completed']]]));

$totalUsers = $usersCol->countDocuments($baseFilter);
$totalOfficers = $headOfficersCol->countDocuments($baseFilter) + $fieldOfficersCol->countDocuments($baseFilter);
$totalDepartments = $departmentsCol->countDocuments(['state' => $myState]);

// Recent Complaints
$recentComplaints = $complaintsCol->find($baseFilter, ['limit' => 5, 'sort' => ['created_at' => -1]]);
$recentArr = iterator_to_array($recentComplaints);

$adminName = $_SESSION['user_name'] ?? 'State Admin';
$adminEmail = $_SESSION['user_email'] ?? 'admin@state.gov';
$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>State Admin Dashboard | <?php echo $myState; ?> | CivicTrack India</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-theme">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-inner">
                    <img src="../assets/images/govt_emblem.png" alt="Emblem" class="sidebar-emblem">
                    <div class="sidebar-brand-text">
                        <h2>CivicTrack India</h2>
                        <span>State Admin Console</span>
                    </div>
                </div>
            </div>
            <div class="sidebar-gold-stripe"></div>
            <nav class="sidebar-nav">
                <div class="sidebar-section-label">State Console</div>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fa fa-bar-chart-o"></i></span> State Stats
                </a>
                <a href="manage_complaints.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_complaints.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fa fa-list-alt"></i></span> Complaints
                </a>
                <a href="manage_departments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_departments.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fa fa-building-o"></i></span> Manage Departments
                </a>
                <a href="manage_officers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_officers.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="fa fa-shield"></i></span> Manage Dept Heads
                </a>
                <a href="manage_users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👥</span> Manage Citizens
                </a>
                
                <div class="sidebar-section-label" style="margin-top:1.5rem;">Intelligence</div>
                <a href="heatmap.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'heatmap.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🗺️</span> State Heatmap
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar"><?php echo $initials; ?></div>
                    <div>
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($adminName); ?></div>
                        <div class="sidebar-user-role">State Administrator</div>
                    </div>
                </div>
                <a href="../logout.php">
                    <i class="fa fa-sign-out"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main -->
        <main class="main-content" style="padding: 2rem;">

            <div class="page-header">
                <div class="header-left">
                    <button class="sidebar-toggle">☰</button>
                    <div class="header-logo-group">
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(250, 249, 248, 0.3));">
                        <span>CivicTrack India</span>
                    </div>
                    <h1>State Stats (<?php echo htmlspecialchars($myState); ?>)</h1>
                </div>
                <div class="user-info">
                    <div style="display:flex; gap:0.5rem; align-items:center; margin-right:1rem;">
                        <span style="font-size:0.8rem; color:var(--text-muted);">District:</span>
                        <select id="district_filter" class="filter-select" onchange="applyDashFilters()" style="padding:0.4rem; border-radius:5px; border:1px solid var(--border); font-size:0.8rem;">
                            <option value="">All Districts</option>
                            <?php foreach ($districtsList as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($_GET['district_filter'] ?? '') === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(!empty($_GET['district_filter'])): ?>
                            <a href="dashboard.php" style="font-size:1.1rem; color:var(--danger); text-decoration:none;" title="Reset Filter">&times;</a>
                        <?php endif; ?>
                    </div>
                    <span>Welcome, <?php echo htmlspecialchars($adminName); ?></span>
                </div>
            </div>

            <!-- Welcome Banner -->
            <div class="profile-card" style="background: linear-gradient(135deg, #065f46 0%, #047857 100%); color: white;">
                <div class="profile-avatar" style="background: rgba(255,255,255,0.2); border: 2px solid white;"><?php echo $initials; ?></div>
                <div class="profile-info">
                    <h3>State Administration — <?php echo $myState; ?></h3>
                    <p>Managing departments and citizen grievances for the state of <?php echo $myState; ?>.</p>
                    <div class="profile-meta">
                        <span style="background: rgba(255,255,255,0.2);">🏛️ State Portal</span>
                        <span style="background: rgba(255,255,255,0.2);"><i class="fa fa-calendar"></i> <?php echo date('d M Y'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; align-items: center;">
                    <h3 style="margin-bottom: 1rem; font-size: 1rem;">State Complaints (<?php echo $myState; ?>)</h3>
                    <div style="width: 100%; height: 220px;">
                        <canvas id="complaintStatusChart"></canvas>
                    </div>
                </div>

                <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; align-items: center;">
                    <h3 style="margin-bottom: 1rem; font-size: 1rem;">State Workforce</h3>
                    <div style="width: 100%; height: 220px;">
                        <canvas id="userDistChart"></canvas>
                    </div>
                </div>

                <div class="card" style="padding: 1.5rem;">
                    <h3>State Statistics</h3>
                    <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.8rem;">
                        <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-muted); font-size: 0.85rem;">Total Complaints</span>
                            <span style="font-weight: 700;"><?php echo $totalComplaints; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-muted); font-size: 0.85rem;">Active Departments</span>
                            <span style="font-weight: 700;"><?php echo $totalDepartments; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-muted); font-size: 0.85rem;">State Field Officers</span>
                            <span style="font-weight: 700;"><?php echo $totalOfficers; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted); font-size: 0.85rem;">Resolution Rate</span>
                            <span style="font-weight: 700;">88%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Complaints -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Recent Submissions in <?php echo $myState; ?></h3>
                    <a href="manage_complaints.php" class="btn btn-outline btn-sm">View All →</a>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Complaint</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentArr)): ?>
                                <tr><td colspan="4" style="text-align:center;">No recent complaints at state level.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentArr as $c): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($c['title']); ?></td>
                                    <td><?php echo htmlspecialchars($c['department'] ?? 'General'); ?></td>
                                    <td><?php echo htmlspecialchars($c['created_at']); ?></td>
                                    <td><span class="badge badge-pending"><?php echo htmlspecialchars($c['status'] ?? 'Pending'); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function applyDashFilters() {
            const d = document.getElementById('district_filter').value;
            let url = 'dashboard.php';
            if (d) {
                url += '?district_filter=' + encodeURIComponent(d);
            }
            location.href = url;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Stats Chart
            new Chart(document.getElementById('complaintStatusChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'In Progress', 'Resolved'],
                    datasets: [{
                        data: [<?php echo $pendingCount; ?>, <?php echo $progressCount; ?>, <?php echo $resolvedCount; ?>],
                        backgroundColor: ['#f59e0b', '#0ea5e9', '#10b981'],
                        borderWidth: 0
                    }]
                },
                options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
            });

            new Chart(document.getElementById('userDistChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Citizens', 'Officers'],
                    datasets: [{
                        label: 'State Count',
                        data: [<?php echo $totalUsers; ?>, <?php echo $totalOfficers; ?>],
                        backgroundColor: ['#10b981', '#06b6d4']
                    }]
                },
                options: { plugins: { legend: { display:false } }, scales: { y: { beginAtZero: true } } }
            });
        });
    </script>
</body>
</html>
