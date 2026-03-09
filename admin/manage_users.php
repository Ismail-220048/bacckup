<?php
/**
 * CivicTrack — Admin: Manage Users
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

// Handle user deletion via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $userId = $_POST['user_id'] ?? '';
    if ($userId) {
        try {
            $usersCol->deleteOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
            // Also delete user's complaints
            $complaintsCol->deleteMany(['user_id' => $userId]);
            header('Location: manage_users.php?deleted=1');
            exit;
        } catch (Exception $e) {
            header('Location: manage_users.php?error=' . urlencode('Failed to delete user.'));
            exit;
        }
    }
}

$allUsers = $usersCol->find([], ['sort' => ['created_at' => -1]]);
$usersList = iterator_to_array($allUsers);

// Get complaint counts per user
$userComplaintCounts = [];
foreach ($usersList as $u) {
    $uid = (string) $u['_id'];
    $userComplaintCounts[$uid] = $complaintsCol->countDocuments(['user_id' => $uid]);
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — CivicTrack Admin</title>
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
                <a href="admin_dashboard.php">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="manage_complaints.php">
                    <span class="nav-icon">📋</span> Manage Complaints
                </a>
                <a href="manage_users.php" class="active">
                    <span class="nav-icon">👥</span> Manage Users
                </a>
                <a href="manage_officers.php">
                    <span class="nav-icon">👮</span> Manage Officers
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
                <h1>Manage Users</h1>
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
                                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Delete this user and all their complaints?')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                            </form>
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
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        initTableSearch('search-input', 'users-table');

        async function viewUserComplaints(userId) {
            openModal('userComplaintsModal');
            document.getElementById('userComplaintsContent').innerHTML = '<div class="empty-state"><div class="empty-icon">⏳</div><p>Loading...</p></div>';

            try {
                const res = await fetch(`../api/get_complaints.php?user_id=${userId}&all=1`);
                const data = await res.json();

                if (data.success && data.data.length > 0) {
                    let html = '<div class="table-wrapper"><table><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Date</th></tr></thead><tbody>';
                    data.data.forEach(c => {
                        const bc = c.status === 'Pending' ? 'badge-pending' : c.status === 'In Progress' ? 'badge-progress' : 'badge-resolved';
                        html += `<tr>
                            <td style="color:var(--text-primary);font-weight:500;">${c.title}</td>
                            <td>${c.category}</td>
                            <td><span class="badge ${bc}">${c.status}</span></td>
                            <td>${c.date || c.created_at}</td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    document.getElementById('userComplaintsContent').innerHTML = html;
                } else {
                    document.getElementById('userComplaintsContent').innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>No complaints from this user.</p></div>';
                }
            } catch (err) {
                document.getElementById('userComplaintsContent').innerHTML = '<div class="empty-state"><p>Error loading complaints.</p></div>';
            }
        }
    </script>
</body>
</html>
