<?php
/**
 * ReportMyCity — Admin: Manage Complaints (with Officer Assignment)
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
            // Ensure $uid is string before creating ObjectId
            $u = $usersCol->findOne(['_id' => new MongoDB\BSON\ObjectId((string)$uid)]);
            if ($u) $userLookup[(string)$uid] = $u['name'];
        } catch (Exception $e) {}
    }
}

// Get all officers for assignment dropdown
$allOfficers = $officersCol->find([], ['sort' => ['name' => 1]]);
$officersList = iterator_to_array($allOfficers);

// Get IDs of officers who have active (Pending or In Progress) complaints
$busyIdsRaw = $complaintsCol->distinct('assigned_officer_id', [
    'status' => ['$in' => ['Pending', 'In Progress']],
    'assigned_officer_id' => ['$ne' => '']
]);
$busyOfficerIds = array_map(fn($id) => (string)$id, (array)$busyIdsRaw);

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
    <title>Manage Complaints — ReportMyCity Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Serif:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-inner">
                    <img src="../assets/images/govt_emblem.png" alt="Emblem" class="sidebar-emblem">
                    <div class="sidebar-brand-text">
                        <h2>ReportMyCity</h2>
                        <span>Administration Portal</span>
                    </div>
                </div>
            </div>
            <div class="sidebar-gold-stripe"></div>
            <nav class="sidebar-nav">
                <div class="sidebar-section-label">Main Menu</div>
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
                <a href="manage_officer_reports.php">
                    <span class="nav-icon">🛡️</span> Officer Reports
                    <?php if($officerReportsCount > 0): ?>
                        <span style="background:var(--danger); color:white; padding: 2px 6px; border-radius: 10px; font-size: 0.65rem; margin-left: 5px;"><?php echo $officerReportsCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage_user_reports.php">
                    <span class="nav-icon">🚩</span> Fake Complaints
                    <?php if($userReportsCount > 0): ?>
                        <span style="background:var(--warning); color:var(--gov-navy); padding: 2px 6px; border-radius: 10px; font-size: 0.65rem; margin-left: 5px;"><?php echo $userReportsCount; ?></span>
                    <?php endif; ?>
                </a>
                <div class="sidebar-section-label">Analytics</div>
                <a href="heatmap.php">
                    <span class="nav-icon">🗺️</span> Heatmap
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar"><?php echo $initials; ?></div>
                    <div>
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($adminName); ?></div>
                        <div class="sidebar-user-role">Administrator</div>
                    </div>
                </div>
                <a href="../logout.php">
                    <i class="fa fa-sign-out"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main -->
        <main class="main-content">

            <div class="page-header">
                                <div class="header-left">
                    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
                    <div class="header-logo-group">
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(250, 249, 248, 0.3));">
                        <span>ReportMyCity</span>
                    </div>
                    <h1>Manage Complaints</h1>
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
                        <h3>📋 All Complaints (<?php echo count($complaintsList); ?>)</h3>
                    </div>

                    <!-- Toolbar -->
                    <div class="toolbar">
                        <div class="search-box">
                            <input type="text" id="search-input" placeholder="Search complaints...">
                        </div>
                        <select class="filter-select" id="status-filter">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo (($_GET['status'] ?? '') === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo (($_GET['status'] ?? '') === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Officer Completed" <?php echo (($_GET['status'] ?? '') === 'Officer Completed') ? 'selected' : ''; ?>>Officer Completed</option>
                            <option value="Resolved" <?php echo (($_GET['status'] ?? '') === 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
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
                                    <th>Citizen Rating</th>
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
                                    elseif ($status === 'Officer Completed') $bc = 'badge-progress'; // Or a custom class like badge-warning
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
                                     <td>
                                         <?php echo htmlspecialchars($c['category']); ?>
                                         <br><small style="color:var(--text-muted); font-size: 0.75rem;">Risk: <?php echo htmlspecialchars($c['risk_type'] ?? 'Medium'); ?></small>
                                     </td>
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
                                        <?php if (isset($c['rating'])): ?>
                                            <div class="rating-display" style="font-size: 0.85rem;">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <span class="star-static <?php echo ($i <= $c['rating']) ? 'filled' : ''; ?>" style="font-size: 0.85rem;">★</span>
                                                <?php endfor; ?>
                                                <small style="display:block; color:var(--text-muted);">"<?php echo htmlspecialchars($c['user_feedback'] ?? ''); ?>"</small>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">No rating yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <?php if ($status !== 'Resolved'): ?>
                                                <?php if (($c['risk_type'] ?? '') === 'Critical' && empty($c['is_verified_critical']) && $status === 'Pending'): ?>
                                                    <button class="btn btn-danger btn-sm" onclick="openVerifyCriticalModal('<?php echo $cId; ?>', '<?php echo htmlspecialchars($c['title'], ENT_QUOTES); ?>')">🚨 Verify Critical</button>
                                                <?php else: ?>
                                                    <button class="btn btn-info btn-sm" onclick="openUpdateModal('<?php echo $cId; ?>', '<?php echo htmlspecialchars($status); ?>', <?php echo htmlspecialchars(json_encode($c['admin_reply'] ?? '')); ?>, '<?php echo htmlspecialchars($assignedOfficerId); ?>')">✏️ Update</button>
                                                <?php endif; ?>
                                            <?php endif; ?>
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
        <div class="modal" style="width: 100%; max-width: 600px; padding: 2.5rem; border-radius: var(--radius-lg);">
            <div class="modal-header" style="border-bottom: 2px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.6rem; font-weight: 800; color: var(--gov-navy);">✏️ Update Complaint</h3>
                <button class="modal-close" style="font-size: 1.8rem;">&times;</button>
            </div>
            <form id="updateForm">
                <input type="hidden" id="update-complaint-id">
                <div class="form-group">
                    <label for="update-status">Status</label>
                    <select id="update-status" class="filter-select" style="width: 100%;">
                        <option value="Pending">⏳ Pending</option>
                        <option value="In Progress">🔄 In Progress (or needs rework)</option>
                        <option value="Officer Completed">✅ Officer Completed (Needs Review)</option>
                        <option value="Resolved">✅ Resolved (Close Complaint)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="update-officer">Assign Officer</label>
                    <select id="update-officer" class="filter-select" style="width: 100%;">
                        <option value="">— Not Assigned —</option>
                        <?php foreach ($officersList as $o): 
                            $oId = (string)$o['_id'];
                            $isBusy = in_array($oId, $busyOfficerIds);
                        ?>
                            <option value="<?php echo $oId; ?>" data-busy="<?php echo $isBusy ? '1' : '0'; ?>">
                                👮 <?php echo htmlspecialchars($o['name']); ?> [<?php echo htmlspecialchars($o['department'] ?? 'General'); ?>]
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
        <div class="modal" style="width: 100%; max-width: 750px; padding: 2.5rem; border-radius: var(--radius-lg);">
            <div class="modal-header" style="border-bottom: 2px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.6rem; font-weight: 800; color: var(--gov-navy);">📋 Task & Issue Details</h3>
                <button class="modal-close" style="font-size: 1.8rem;">&times;</button>
            </div>
            <div id="viewContent"></div>
        </div>
    </div>

    <!-- Verify Critical Modal -->
    <div class="modal-overlay" id="verifyCriticalModal">
        <div class="modal" style="width: 100%; max-width: 600px; padding: 2.5rem; border-radius: var(--radius-lg);">
            <div class="modal-header" style="border-bottom: 2px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.6rem; font-weight: 800; color: var(--danger);">🚨 Verify Critical Complaint</h3>
                <button class="modal-close" style="font-size: 1.8rem;">&times;</button>
            </div>
            <form id="verifyCriticalForm" style="margin-top: 1rem;">
                <input type="hidden" id="vc-complaint-id">
                <p style="margin-bottom: 1rem;"><strong>Title:</strong> <span id="vc-title-display"></span></p>
                <div class="form-group">
                    <label>Is this a genuine critical issue that needs immediate attention?</label>
                    <select id="vc-action" class="filter-select" style="width: 100%;">
                        <option value="verify">Yes, verify Critical & assign Officer Immediately</option>
                        <option value="downgrade">No, downgrade to Medium risk</option>
                    </select>
                </div>
                <div class="form-group" id="vc-officer-group">
                    <label>Dispatch Officer Immediately</label>
                    <select id="vc-officer" class="filter-select" style="width: 100%;">
                        <option value="">— Select Officer —</option>
                        <?php foreach ($officersList as $o):
                            $oId = (string)$o['_id'];
                            $isBusy = in_array($oId, $busyOfficerIds);
                        ?>
                            <option value="<?php echo $oId; ?>">
                                👮 <?php echo htmlspecialchars($o['name']); ?> [<?php echo htmlspecialchars($o['department'] ?? 'General'); ?>]<?php echo $isBusy ? ' (Busy)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger btn-block">Confirm</button>
            </form>
        </div>
    </div>

    <!-- Merge Complaints Modal -->
    <div class="modal-overlay" id="mergeModal">
        <div class="modal" style="width: 100%; max-width: 600px; padding: 2.5rem; border-radius: var(--radius-lg);">
            <div class="modal-header" style="border-bottom: 2px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.6rem; font-weight: 800; color: var(--warning);">🔗 Merge Complaints</h3>
                <button class="modal-close" style="font-size: 1.8rem;">&times;</button>
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
                'officer_proof_image'   => $c['officer_proof_image'] ?? '',
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
            // Filter officer dropdown to only show free officers
            const officerSelect = document.getElementById('update-officer');
            Array.from(officerSelect.options).forEach(opt => {
                if (opt.value === '') return; // "Not Assigned" is always visible
                
                const isBusy = opt.dataset.busy === '1';
                // Show if it's the current officer (assigned to THIS complaint) OR if not busy at all
                if (opt.value === officerId || !isBusy) {
                    opt.style.display = '';
                    opt.disabled = false;
                } else {
                    opt.style.display = 'none';
                    opt.disabled = true;
                }
            });

            document.getElementById('update-complaint-id').value = id;
            document.getElementById('update-status').value = status;
            document.getElementById('update-reply').value = reply || '';
            document.getElementById('update-officer').value = officerId || '';
            openModal('updateModal');
        }

        function viewComplaint(id) {
            const c = complaintsData.find(item => item._id === id);
            if (!c) return;

            let badgeClass = c.status === 'Pending' ? 'pending' : (c.status === 'Resolved' ? 'resolved' : 'progress');

            let html = '<div class="complaint-detail-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">';
            html += `<div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Issue Title</label><p style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-top: 0.2rem;">${c.title}</p></div>`;
            html += `<div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Category</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary); margin-top: 0.2rem;">${c.category}</p></div>`;
            html += `<div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Location</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary); margin-top: 0.2rem;">${c.location}</p></div>`;
            html += `<div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Date Reported</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary); margin-top: 0.2rem;">${c.date}</p></div>`;
            html += `<div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Current Status</label><p style="margin-top: 0.4rem;"><span class="badge badge-${badgeClass}" style="font-size: 0.95rem; padding: 0.4rem 0.8rem;">${c.status}</span></p></div>`;
            html += `<div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Reported By</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary); margin-top: 0.2rem;">👤 ${c.user_name}</p></div>`;
            if (c.assigned_officer_name) {
                html += `<div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Assigned Officer</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--warning); margin-top: 0.2rem;">👮 ${c.assigned_officer_name}</p></div>`;
            }
            html += '</div>';
            
            html += `<div style="margin-top:1.5rem; background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); border-left: 4px solid var(--primary-light);">
                        <label style="display:block;font-size:0.85rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;">Citizen Description</label>
                        <p style="color:var(--text-primary);font-size:1.05rem; line-height: 1.6;">${c.description}</p>
                     </div>`;
            if (c.admin_reply) {
                html += `<div style="margin-top:1rem; background: rgba(59, 130, 246, 0.05); padding: 1.2rem; border-radius: var(--radius-md); border: 1px solid rgba(59, 130, 246, 0.2);">
                            <label style="display:block;font-size:0.85rem;color:var(--gov-navy);font-weight:700;text-transform:uppercase;margin-bottom:0.4rem;">Admin Reply</label>
                            <p style="color:var(--text-primary);font-size:1rem; line-height: 1.5;">${c.admin_reply}</p>
                         </div>`;
            }
            if (c.officer_notes) {
                html += `<div style="margin-top:1rem; background: var(--bg-card); padding: 1.2rem; border-radius: var(--radius-md); border: 1px dashed var(--border);">
                            <label style="display:block;font-size:0.85rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:0.4rem;">Officer Notes</label>
                            <p style="color:var(--text-primary);font-size:1rem; line-height: 1.5;">${c.officer_notes}</p>
                         </div>`;
            }

            if (c.image || c.officer_proof_image) {
                html += `<div style="margin-top:1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">`;
                if (c.image) {
                    html += `<div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                                <label style="display:block;font-size:0.85rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:0.8rem;">Citizen Submission Image</label>
                                <img src="../${c.image}" alt="Complaint Image" style="display:block; width:100%; height:auto; max-height: 350px; object-fit: contain; border-radius:var(--radius-sm); border:1px solid var(--border);">
                             </div>`;
                }
                if (c.officer_proof_image) {
                    html += `<div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                                <label style="display:block;font-size:0.85rem;color:var(--success);font-weight:700;text-transform:uppercase;margin-bottom:0.8rem;">Officer Uploaded Proof Image</label>
                                <img src="../${c.officer_proof_image}" alt="Proof Image" style="display:block; width:100%; height:auto; max-height: 350px; object-fit: contain; border-radius:var(--radius-sm); border:1px solid var(--border);">
                             </div>`;
                }
                html += `</div>`;
            }

            // Removed map code from here.

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

        // --- Verify Critical Logic ---
        function openVerifyCriticalModal(id, title) {
            document.getElementById('vc-complaint-id').value = id;
            document.getElementById('vc-title-display').textContent = title;
            document.getElementById('vc-action').value = 'verify';
            document.getElementById('vc-officer-group').style.display = 'block';
            openModal('verifyCriticalModal');
        }

        const verifyCriticalForm = document.getElementById('verifyCriticalForm');
        if (verifyCriticalForm) {
            const vcAction = document.getElementById('vc-action');
            vcAction.addEventListener('change', function() {
                if (this.value === 'verify') {
                    document.getElementById('vc-officer-group').style.display = 'block';
                } else {
                    document.getElementById('vc-officer-group').style.display = 'none';
                }
            });

            verifyCriticalForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                const id = document.getElementById('vc-complaint-id').value;
                const action = document.getElementById('vc-action').value;
                const officerId = document.getElementById('vc-officer').value;

                if (action === 'verify' && !officerId) {
                    showToast('Please select an officer for urgent dispatch.', 'error');
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Processing...';

                const result = await postJSON('../api/verify_critical.php', {
                    complaint_id: id,
                    action: action,
                    officer_id: officerId
                });

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal('verifyCriticalModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Confirm';
                }
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
