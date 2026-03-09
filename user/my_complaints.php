<?php
/**
 * CivicTrack — My Complaints Page
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

$filter = ['user_id' => $userId];
if (!empty($_GET['status'])) {
    $filter['status'] = htmlspecialchars(trim($_GET['status']), ENT_QUOTES, 'UTF-8');
}

$allComplaints = $complaints->find($filter, ['sort' => ['created_at' => -1]]);
$complaintsList = iterator_to_array($allComplaints);

$userName = $_SESSION['user_name'] ?? 'User';
$initials = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints — CivicTrack</title>
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
                <a href="dashboard.php">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="submit_complaint.php">
                    <span class="nav-icon">📝</span> Submit Complaint
                </a>
                <a href="my_complaints.php" class="active">
                    <span class="nav-icon">📋</span> My Complaints
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php">
                    Logout <span class="nav-icon" style="margin-left: auto;">🚪</span>
                </a>
            </div>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

            <div class="page-header">
                <h1>My Complaints</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($userName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>📋 All Complaints (<?php echo count($complaintsList); ?>)</h3>
                    <a href="submit_complaint.php" class="btn btn-primary btn-sm">+ New Complaint</a>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-box">
                        <input type="text" id="search-input" placeholder="Search complaints...">
                    </div>
                    <select class="filter-select" id="status-filter">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo (($_GET['status'] ?? '') === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="in progress" <?php echo (($_GET['status'] ?? '') === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo (($_GET['status'] ?? '') === 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>

                <?php if (empty($complaintsList)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>No complaints found.<br><a href="submit_complaint.php">Submit your first complaint →</a></p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table id="complaints-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Assigned Officer</th>
                                    <th>Admin Reply</th>
                                    <th>Officer Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaintsList as $c): ?>
                                <tr>
                                    <td style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);">
                                        <?php echo substr((string)$c['_id'], -6); ?>
                                    </td>
                                    <td style="color: var(--text-primary); font-weight: 500;">
                                        <?php echo htmlspecialchars($c['title']); ?>
                                        <?php if (!empty($c['image'])): ?>
                                            <br><a href="../<?php echo htmlspecialchars($c['image']); ?>" target="_blank" style="font-size: 0.78rem; color: var(--accent);">📷 View Image</a>
                                        <?php endif; ?>
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
                                    <td>
                                        <?php
                                        $officerName = $c['assigned_officer_name'] ?? '';
                                        echo $officerName ? '<span style="color: var(--warning); font-weight: 500;">👮 ' . htmlspecialchars($officerName) . '</span>' : '<span style="color: var(--text-muted);">—</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $reply = $c['admin_reply'] ?? '';
                                        echo $reply ? htmlspecialchars($reply) : '<span style="color: var(--text-muted);">—</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $notes = $c['officer_notes'] ?? '';
                                        echo $notes ? htmlspecialchars($notes) : '<span style="color: var(--text-muted);">—</span>';
                                        ?>
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
        initTableSearch('search-input', 'complaints-table');
        initStatusFilter('status-filter', 'complaints-table');
    </script>
</body>
</html>
