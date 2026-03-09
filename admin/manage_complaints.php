<?php
/**
 * CivicTrack — Admin: Manage Complaints (with Officer Assignment)
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$complaintsCol = $db->getCollection('complaints');
$usersCol      = $db->getCollection('users');
$officersCol   = $db->getCollection('officers');

$filter = [];
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

// Get all officers for assignment dropdown
$allOfficers = $officersCol->find([], ['sort' => ['name' => 1]]);
$officersList = iterator_to_array($allOfficers);

$adminName = $_SESSION['user_name'] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints — CivicTrack Admin</title>
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
                <a href="heatmap.php">
                    <span class="nav-icon">🗺️</span> Heatmap
                </a>
                <a href="manage_complaints.php" class="active">
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
                <h1>Manage Complaints</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>📋 All Complaints (<?php echo count($complaintsList); ?>)</h3>
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
                    <button class="btn btn-warning" id="btn-merge-selected" style="margin-left: auto;">🔗 Merge Selected</button>
                </div>

                <?php if (empty($complaintsList)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>No complaints found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table id="complaints-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllMerge"></th>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>User</th>
                                    <th>Officer</th>
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
                                    $assignedOfficer = $c['assigned_officer_name'] ?? '';
                                    $assignedOfficerId = $c['assigned_officer_id'] ?? '';
                                ?>
                                <tr id="row-<?php echo $cId; ?>">
                                    <td style="text-align: center;"><input type="checkbox" class="merge-cb" value="<?php echo $cId; ?>" data-title="<?php echo htmlspecialchars($c['title']); ?>"></td>
                                    <td style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);"><?php echo substr($cId, -6); ?></td>
                                    <td style="color: var(--text-primary); font-weight: 500;">
                                        <?php echo htmlspecialchars($c['title']); ?>
                                        <?php if (!empty($c['image'])): ?>
                                            <br><a href="../<?php echo htmlspecialchars($c['image']); ?>" target="_blank" style="font-size: 0.78rem; color: var(--accent);">📷 View Image</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['category']); ?></td>
                                    <td><?php echo htmlspecialchars($userLookup[$c['user_id'] ?? ''] ?? 'Unknown'); ?></td>
                                    <td>
                                        <?php if ($assignedOfficer): ?>
                                            <span style="color: var(--warning); font-weight: 500;">👮 <?php echo htmlspecialchars($assignedOfficer); ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['date'] ?? $c['created_at']); ?></td>
                                    <td><span class="badge <?php echo $bc; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-info btn-sm" onclick="openUpdateModal('<?php echo $cId; ?>', '<?php echo htmlspecialchars($status); ?>', <?php echo htmlspecialchars(json_encode($c['admin_reply'] ?? '')); ?>, '<?php echo htmlspecialchars($assignedOfficerId); ?>')">✏️ Update</button>
                                            <button class="btn btn-outline btn-sm" onclick="viewComplaint('<?php echo $cId; ?>')">👁️ View</button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteComplaint('<?php echo $cId; ?>')">🗑️</button>
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
                <h3>Update Complaint</h3>
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
                    <label for="update-officer">Assign Officer</label>
                    <select id="update-officer" class="filter-select" style="width: 100%;">
                        <option value="">— Not Assigned —</option>
                        <?php foreach ($officersList as $o): ?>
                            <option value="<?php echo (string) $o['_id']; ?>">
                                👮 <?php echo htmlspecialchars($o['name']); ?> (<?php echo htmlspecialchars($o['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="update-reply">Admin Reply</label>
                    <textarea id="update-reply" placeholder="Enter your reply to the citizen..." style="width:100%;padding:0.7rem;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md);color:var(--text-primary);font-family:var(--font-sans);min-height:100px;resize:vertical;outline:none;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update Complaint</button>
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

    <!-- Merge Complaints Modal -->
    <div class="modal-overlay" id="mergeModal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Merge Complaints</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="mergeForm" style="margin-top: 1rem;">
                <div class="form-group">
                    <label>Select Primary Complaint <br><small style="color:var(--text-muted);">(The remaining selected complaints will be merged into this one and deleted)</small></label>
                    <select id="merge-primary-select" class="filter-select" style="width: 100%; margin-top: 0.5rem;" required>
                    </select>
                </div>
                <button type="submit" class="btn btn-warning btn-block">Confirm Merge</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        const complaintsData = <?php echo json_encode(array_map(function($c) use ($userLookup) {
            return [
                '_id'                   => (string) $c['_id'],
                'title'                 => $c['title'],
                'category'              => $c['category'],
                'description'           => $c['description'],
                'location'              => $c['location'] ?? '',
                'image'                 => $c['image'] ?? '',
                'date'                  => $c['date'] ?? $c['created_at'],
                'status'                => $c['status'],
                'admin_reply'           => $c['admin_reply'] ?? '',
                'officer_notes'         => $c['officer_notes'] ?? '',
                'assigned_officer_name' => $c['assigned_officer_name'] ?? '',
                'user_name'             => $userLookup[$c['user_id'] ?? ''] ?? 'Unknown',
                'created_at'            => $c['created_at']
            ];
        }, $complaintsList)); ?>;

        initTableSearch('search-input', 'complaints-table');
        initStatusFilter('status-filter', 'complaints-table');

        function openUpdateModal(id, status, reply, officerId) {
            document.getElementById('update-complaint-id').value = id;
            document.getElementById('update-status').value = status;
            document.getElementById('update-reply').value = reply || '';
            document.getElementById('update-officer').value = officerId || '';
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
            if (c.assigned_officer_name) {
                html += `<div class="detail-item"><label>Assigned Officer</label><p>👮 ${c.assigned_officer_name}</p></div>`;
            }
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
            const reply = document.getElementById('update-reply').value;
            const officerId = document.getElementById('update-officer').value;

            const result = await postJSON('../api/update_status.php', {
                complaint_id: id,
                status: status,
                admin_reply: reply,
                assigned_officer_id: officerId,
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

        async function deleteComplaint(id) {
            if (!confirmDelete('Are you sure you want to delete this complaint?')) return;

            const result = await postJSON('../api/update_status.php', {
                complaint_id: id,
                action: 'delete'
            });

            if (result.success) {
                showToast(result.message, 'success');
                const row = document.getElementById('row-' + id);
                if (row) row.remove();
            } else {
                showToast(result.message, 'error');
            }
        }

        // --- Merge Logic ---
        const selectAllCb = document.getElementById('selectAllMerge');
        if (selectAllCb) {
            selectAllCb.addEventListener('change', function() {
                document.querySelectorAll('.merge-cb').forEach(cb => cb.checked = this.checked);
            });
        }

        const btnMergeSelected = document.getElementById('btn-merge-selected');
        if (btnMergeSelected) {
            btnMergeSelected.addEventListener('click', function() {
                const checked = document.querySelectorAll('.merge-cb:checked');
                if (checked.length < 2) {
                    showToast('Please select at least 2 complaints to merge.', 'warning');
                    return;
                }

                const selectEl = document.getElementById('merge-primary-select');
                selectEl.innerHTML = '';
                checked.forEach(cb => {
                    const option = document.createElement('option');
                    option.value = cb.value;
                    option.textContent = cb.dataset.title + ' (ID: ' + cb.value.substr(-6) + ')';
                    selectEl.appendChild(option);
                });

                openModal('mergeModal');
            });
        }

        const mergeForm = document.getElementById('mergeForm');
        if (mergeForm) {
            mergeForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const primaryId = document.getElementById('merge-primary-select').value;
                const checked = document.querySelectorAll('.merge-cb:checked');
                let secondaryIds = [];
                checked.forEach(cb => {
                    if (cb.value !== primaryId) secondaryIds.push(cb.value);
                });

                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Merging...';

                const result = await postJSON('../api/merge_complaints.php', {
                    primary_id: primaryId,
                    secondary_ids: secondaryIds
                });

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal('mergeModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Confirm Merge';
                }
            });
        }
    </script>
</body>
</html>
