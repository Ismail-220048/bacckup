<?php
/**
 * CivicTrack — Officer: My Assignments
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'officer') {
    header('Location: officer_login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$complaintsCol = $db->getCollection('complaints');
$usersCol      = $db->getCollection('users');
$officerId = $_SESSION['user_id'];

$filter = ['assigned_officer_id' => $officerId];
if (!empty($_GET['status'])) {
    $filter['status'] = htmlspecialchars(trim($_GET['status']), ENT_QUOTES, 'UTF-8');
}

$allComplaints = $complaintsCol->find($filter, ['sort' => ['created_at' => -1]]);
$complaintsList = iterator_to_array($allComplaints);

// Build user lookup
$userIds = array_unique(array_map(fn($c) => $c['user_id'] ?? '', $complaintsList));
$userLookup = [];
foreach ($userIds as $uid) {
    if ($uid) {
        try {
            $u = $usersCol->findOne(['_id' => new MongoDB\BSON\ObjectId($uid)]);
            if ($u) $userLookup[$uid] = $u['name'];
        } catch (Exception $e) {}
    }
}

$officerName = $_SESSION['user_name'] ?? 'Officer';
$initials = strtoupper(substr($officerName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments — CivicTrack Officer</title>
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
                <a href="officer_dashboard.php">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="my_assignments.php" class="active">
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
                <h1>My Assignments</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($officerName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>📋 Assigned Complaints (<?php echo count($complaintsList); ?>)</h3>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-box">
                        <input type="text" id="search-input" placeholder="Search assignments...">
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
                        <p>No complaints assigned to you yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table id="complaints-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Reported By</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaintsList as $c):
                                    $cId = (string) $c['_id'];
                                    $status = $c['status'] ?? 'Pending';
                                    $bc = 'badge-pending';
                                    if ($status === 'In Progress') $bc = 'badge-progress';
                                    elseif ($status === 'Resolved') $bc = 'badge-resolved';
                                ?>
                                <tr id="row-<?php echo $cId; ?>">
                                    <td style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);"><?php echo substr($cId, -6); ?></td>
                                    <td style="color: var(--text-primary); font-weight: 500;">
                                        <?php echo htmlspecialchars($c['title']); ?>
                                        <?php if (!empty($c['image'])): ?>
                                            <br><a href="../<?php echo htmlspecialchars($c['image']); ?>" target="_blank" style="font-size: 0.78rem; color: var(--accent);">📷 View Image</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['category']); ?></td>
                                    <td><?php echo htmlspecialchars($userLookup[$c['user_id'] ?? ''] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($c['location'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($c['date'] ?? $c['created_at']); ?></td>
                                    <td><span class="badge <?php echo $bc; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-info btn-sm" onclick="openUpdateModal('<?php echo $cId; ?>', '<?php echo htmlspecialchars($status); ?>', <?php echo htmlspecialchars(json_encode($c['officer_notes'] ?? '')); ?>)">✏️ Update</button>
                                            <button class="btn btn-outline btn-sm" onclick="viewComplaint('<?php echo $cId; ?>')">👁️ View</button>
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

    <!-- Update Status Modal -->
    <div class="modal-overlay" id="updateModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Update Complaint Progress</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="updateForm">
                <input type="hidden" id="update-complaint-id">
                <div class="form-group">
                    <label for="update-status">Status</label>
                    <select id="update-status" class="filter-select" style="width: 100%;">
                        <option value="Pending">⏳ Pending</option>
                        <option value="In Progress">🔄 In Progress</option>
                        <option value="Resolved">✅ Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="update-notes">Officer Notes</label>
                    <textarea id="update-notes" placeholder="Add progress notes, actions taken..." style="width:100%;padding:0.7rem;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md);color:var(--text-primary);font-family:var(--font-sans);min-height:100px;resize:vertical;outline:none;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update Progress</button>
            </form>
        </div>
    </div>

    <!-- View Complaint Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Complaint Details</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div id="viewContent"></div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        const complaintsData = <?php echo json_encode(array_map(function($c) use ($userLookup) {
            return [
                '_id'           => (string) $c['_id'],
                'title'         => $c['title'],
                'category'      => $c['category'],
                'description'   => $c['description'],
                'location'      => $c['location'] ?? '',
                'image'         => $c['image'] ?? '',
                'date'          => $c['date'] ?? $c['created_at'],
                'status'        => $c['status'],
                'admin_reply'   => $c['admin_reply'] ?? '',
                'officer_notes' => $c['officer_notes'] ?? '',
                'user_name'     => $userLookup[$c['user_id'] ?? ''] ?? 'Unknown',
                'created_at'    => $c['created_at']
            ];
        }, $complaintsList)); ?>;

        initTableSearch('search-input', 'complaints-table');
        initStatusFilter('status-filter', 'complaints-table');

        function openUpdateModal(id, status, notes) {
            document.getElementById('update-complaint-id').value = id;
            document.getElementById('update-status').value = status;
            document.getElementById('update-notes').value = notes || '';
            openModal('updateModal');
        }

        function viewComplaint(id) {
            const c = complaintsData.find(item => item._id === id);
            if (!c) return;

            let html = '<div class="complaint-detail-grid">';
            html += `<div class="detail-item"><label>Title</label><p>${c.title}</p></div>`;
            html += `<div class="detail-item"><label>Category</label><p>${c.category}</p></div>`;
            html += `<div class="detail-item"><label>Location</label><p>${c.location}</p></div>`;
            html += `<div class="detail-item"><label>Date</label><p>${c.date}</p></div>`;
            html += `<div class="detail-item"><label>Status</label><p><span class="badge badge-${c.status === 'Pending' ? 'pending' : c.status === 'In Progress' ? 'progress' : 'resolved'}">${c.status}</span></p></div>`;
            html += `<div class="detail-item"><label>Reported By</label><p>${c.user_name}</p></div>`;
            html += '</div>';
            html += `<div style="margin-top:1rem"><label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:0.25rem;">Description</label><p style="color:var(--text-primary);font-size:0.92rem;">${c.description}</p></div>`;
            if (c.admin_reply) {
                html += `<div style="margin-top:1rem"><label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:0.25rem;">Admin Reply</label><p style="color:var(--text-primary);font-size:0.92rem;">${c.admin_reply}</p></div>`;
            }
            if (c.officer_notes) {
                html += `<div style="margin-top:1rem"><label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:0.25rem;">Officer Notes</label><p style="color:var(--text-primary);font-size:0.92rem;">${c.officer_notes}</p></div>`;
            }
            if (c.image) {
                html += `<div style="margin-top:1rem"><label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:0.25rem;">Image</label><img src="../${c.image}" alt="Complaint Image" style="max-width:100%;border-radius:var(--radius-md);border:1px solid var(--border);margin-top:0.5rem;"></div>`;
            }

            document.getElementById('viewContent').innerHTML = html;
            openModal('viewModal');
        }

        document.getElementById('updateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('update-complaint-id').value;
            const status = document.getElementById('update-status').value;
            const notes = document.getElementById('update-notes').value;

            const result = await postJSON('../api/update_status.php', {
                complaint_id: id,
                status: status,
                officer_notes: notes,
                action: 'update'
            });

            if (result.success) {
                showToast(result.message, 'success');
                closeModal('updateModal');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        });
    </script>
</body>
</html>
