<?php
/**
 * CivicTrack — Admin: Manage Users
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';
use MongoDB\BSON\ObjectId;

$db = Database::getInstance();
$usersCol = $db->getCollection('users');
$complaintsCol = $db->getCollection('complaints');

// Get all users
$allUsers = $usersCol->find([], ['sort' => ['created_at' => -1]]);
$usersList = iterator_to_array($allUsers);

// Get complaint counts per user
$userComplaintCounts = [];
$counts = $complaintsCol->aggregate([
    ['$group' => ['_id' => '$user_id', 'count' => ['$sum' => 1]]]
]);
foreach ($counts as $c) {
    if ($c['_id']) $userComplaintCounts[(string)$c['_id']] = $c['count'];
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | CivicTrack Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <a href="admin_dashboard.php">📊 Dashboard</a>
                <a href="manage_complaints.php">📋 All Complaints</a>
                <a href="manage_users.php" class="active">👥 Manage Citizens</a>
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

        <!-- Main -->
        <main class="main-content">

            <div class="page-header">
                                <div class="header-left">
                    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
                    <div class="header-logo-group">
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(200,146,42,0.3));">
                        <span>CivicTrack</span>
                    </div>
                    <h1>Manage Users</h1>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">✅ User deleted successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>👥 Registered Users (<?php echo count($usersList); ?>)</h3>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-box">
                        <input type="text" id="search-input" placeholder="Search users...">
                    </div>
                </div>

                <?php if (empty($usersList)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👤</div>
                        <p>No users registered yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table id="users-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Complaints</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersList as $u):
                                    $uid = (string) $u['_id'];
                                    $complaintCount = $userComplaintCounts[$uid] ?? 0;
                                ?>
                                <tr id="user-row-<?php echo $uid; ?>">
                                    <td style="color: var(--text-primary); font-weight: 500;">
                                        <div style="display:flex;align-items:center;gap:0.65rem;">
                                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.82rem;flex-shrink:0;">
                                                <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($u['name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                                    <td>
                                        <?php if ($complaintCount > 0): ?>
                                            <a href="manage_complaints.php?user_id=<?php echo $uid; ?>" style="color:var(--primary-light);font-weight:600;"><?php echo $complaintCount; ?> complaints</a>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['created_at'] ?? '—'); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-outline btn-sm" onclick="viewUserComplaints('<?php echo $uid; ?>')">📋 Complaints</button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser('<?php echo $uid; ?>', '<?php echo htmlspecialchars($u['name']); ?>')">🗑️ Delete</button>
                                        </div>
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

    <!-- User Complaints Modal -->
    <div class="modal-overlay" id="userComplaintsModal">
        <div class="modal" style="max-width: 700px;">
            <div class="modal-header">
                <h3>User Complaints</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div id="userComplaintsContent">
                <div class="empty-state">
                    <div class="empty-icon">⏳</div>
                    <p>Fetching complaints...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        initTableSearch('search-input', 'users-table');

        async function deleteUser(id, name) {
            if (!confirm(`Are you sure you want to delete user "${name}"? This will NOT delete their complaints, but they will be orphaned.`)) return;
            
            const result = await postJSON('../api/delete_user.php', { user_id: id });
            if (result.success) {
                showToast(result.message, 'success');
                const row = document.getElementById('user-row-' + id);
                if (row) row.remove();
            } else {
                showToast(result.message, 'error');
            }
        }

        async function viewUserComplaints(userId) {
            const container = document.getElementById('userComplaintsContent');
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">⏳</div><p>Fetching complaints...</p></div>';
            openModal('userComplaintsModal');

            try {
                const res = await fetch(`../api/get_complaints.php?user_id=${userId}`);
                const data = await res.json();

                if (data.success && data.complaints.length > 0) {
                    let html = '<div class="table-wrapper"><table><thead><tr><th>Title</th><th>Status</th><th>Date</th></tr></thead><tbody>';
                    data.complaints.forEach(c => {
                        html += `<tr>
                            <td>${c.title}</td>
                            <td><span class="badge badge-pending">${c.status}</span></td>
                            <td>${c.date}</td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="empty-state"><div class="empty-icon">📂</div><p>No complaints found for this user.</p></div>';
                }
            } catch (e) {
                container.innerHTML = '<div class="alert alert-error">Failed to load complaints.</div>';
            }
        }
    </script>
</body>
</html>
