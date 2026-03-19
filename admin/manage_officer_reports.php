<?php
/**
 * ReportMyCity — Manage Officer Reports (Admin)
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();
$reportsCol = $db->getCollection('officer_reports');
$officersCol = $db->getCollection('officers');

// Fetch all reports
$reports = $reportsCol->find([], ['sort' => ['created_at' => -1]]);
$reportsArr = iterator_to_array($reports);

// Lookup officer names
$officerIds = array_unique(array_map(fn($r) => $r['officer_id'] ?? '', $reportsArr));
$officerLookup = [];
if (!empty($officerIds)) {
    $validIds = array_filter($officerIds, fn($id) => !empty($id) && preg_match('/^[a-f\d]{24}$/i', $id));
    if (!empty($validIds)) {
        $foundOfficers = $officersCol->find(['_id' => ['$in' => array_map(fn($id) => new \MongoDB\BSON\ObjectId($id), $validIds)]]);
        foreach ($foundOfficers as $o) {
            $officerLookup[(string)$o['_id']] = $o['name'];
        }
    }
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminEmail = $_SESSION['user_email'] ?? 'admin@reportmycity.gov';
$initials = strtoupper(substr($adminName, 0, 1));

// Fetch pending counts for sidebar notification badges
$officerReportsCount = $db->getCollection('officer_reports')->countDocuments(['status' => 'Pending Admin Review']);
$userReportsCount = $db->getCollection('user_reports')->countDocuments(['status' => 'Audit Requested']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Conduct Reports | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-theme">
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-inner">
                    <img src="../assets/images/govt_emblem.png" alt="ReportMyCity" style="width: 100%; max-width:   250px; height: auto; object-fit: contain; margin: 0 auto;">
                </div>
                <div class="sidebar-gold-stripe"></div>
            </div>
            <div class="sidebar-nav">
                <div class="sidebar-section-label">Main Console</div>
                <a href="admin_dashboard.php">📊 Dashboard</a>
                <a href="manage_complaints.php">📋 All Complaints</a>
                <a href="manage_users.php">👥 Manage Citizens</a>
                <a href="manage_officers.php">👮 Manage Officers</a>
                <a href="manage_officer_reports.php" class="active">🛡️ Officer Reports <?php if($officerReportsCount > 0): ?><span style="background:var(--danger); color:white; padding: 2px 6px; border-radius: 10px; font-size: 0.65rem; margin-left: 5px;"><?php echo $officerReportsCount; ?></span><?php endif; ?></a>
                <a href="manage_user_reports.php">🚩 Fake Complaints <?php if($userReportsCount > 0): ?><span style="background:var(--warning); color:var(--gov-navy); padding: 2px 6px; border-radius: 10px; font-size: 0.65rem; margin-left: 5px;"><?php echo $userReportsCount; ?></span><?php endif; ?></a>
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

        <main class="main-content">
            <div class="page-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
                    <div class="header-logo-group">
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(250, 249, 248, 0.3));">
                        <span>ReportMyCity</span>
                    </div>
                    <div>
                        <h1>🛡️ Officer Conduct Reports</h1>
                        <div class="breadcrumb">
                            <a href="admin_dashboard.php">Home</a>
                            <span>›</span>
                            <span>Officer Reports</span>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span style="font-size:0.82rem; color:var(--text-muted);"><?php echo date('d M Y'); ?></span>
                    <span>Welcome, <?php echo htmlspecialchars($adminName); ?></span>
                    <!-- Profile Dropdown -->
                    <div class="profile-dropdown-wrapper" id="profileDropdownWrapper">
                        <div class="user-avatar">
                            <?php echo $initials; ?>
                        </div>
                        <div class="profile-dropdown-menu">
                            <div class="profile-dropdown-header">
                                <strong><?php echo htmlspecialchars($adminName); ?></strong>
                                <span><?php echo htmlspecialchars($adminEmail); ?></span>
                            </div>
                            <a href="../logout.php" class="dropdown-logout">
                                <div class="dropdown-icon">🚪</div> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Reports against Officers</h3>
                </div>
                <?php if (empty($reportsArr)): ?>
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-icon">🛡️</div>
                            <p>No reports against officers found.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Citizen</th>
                                    <th>Reported Officer</th>
                                    <th>Related Complaint</th>
                                    <th>Reason / Description</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportsArr as $r): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($r['user_name'] ?? 'Citizen'); ?></strong>
                                    </td>
                                    <td>
                                        <span style="color: var(--gov-navy); font-weight: 600;">
                                            <?php echo htmlspecialchars($officerLookup[(string)$r['officer_id']] ?? 'Unknown Officer'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="manage_complaints.php?id=<?php echo $r['complaint_id']; ?>" style="color: var(--gov-navy); text-decoration: underline;">
                                            <?php echo htmlspecialchars($r['original_title'] ?? 'Complaint'); ?>
                                        </a>
                                    </td>
                                    <td style="max-width: 300px; font-size: 0.85rem; color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($r['report_description']); ?>
                                    </td>
                                    <td style="font-size: 0.85rem; white-space: nowrap;">
                                        <?php echo date('d M Y', strtotime($r['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php $s = $r['status'] ?? 'Pending Review'; ?>
                                        <span class="badge <?php echo ($s === 'Pending Review' ? 'badge-pending' : ($s === 'Dismissed' ? 'badge-progress' : 'badge-resolved')); ?>">
                                            <?php echo htmlspecialchars($s); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($s === 'Pending Review'): ?>
                                            <div style="display: flex; gap: 5px;">
                                                <button class="btn btn-sm" style="background: #10b981; color: white;" onclick="updateReportStatus('<?php echo (string)$r['_id']; ?>', 'Action Taken')">✔️ Resolve</button>
                                                <button class="btn btn-sm" style="background: #ef4444; color: white;" onclick="updateReportStatus('<?php echo (string)$r['_id']; ?>', 'Dismissed')">❌ Dismiss</button>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">Reviewed</span>
                                        <?php endif; ?>
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
    <script>
        function updateReportStatus(reportId, status) {
            if (!confirm(`Are you sure you want to mark this report as ${status}?`)) return;

            const formData = new FormData();
            formData.append('report_id', reportId);
            formData.append('type', 'officer');
            formData.append('status', status);

            fetch('../api/update_report_status.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred.');
            });
        }
    </script>
    <script>
        // Profile Dropdown
        const pdw = document.getElementById('profileDropdownWrapper');
        if (pdw) {
            pdw.addEventListener('click', function(e) { e.stopPropagation(); this.classList.toggle('open'); });
            document.addEventListener('click', () => pdw.classList.remove('open'));
        }
    </script>
</body>
</html>
