<?php
/**
 * CivicTrack — Admin: Manage Officers
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$officersCol   = $db->getCollection('officers');
$complaintsCol = $db->getCollection('complaints');

$allOfficers = $officersCol->find([], ['sort' => ['created_at' => -1]]);
$officersList = iterator_to_array($allOfficers);

// Get assignment counts per officer
$officerAssignmentCounts = [];
foreach ($officersList as $o) {
    $oid = (string) $o['_id'];
    $officerAssignmentCounts[$oid] = $complaintsCol->countDocuments(['assigned_officer_id' => $oid]);
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Officers — CivicTrack Admin</title>
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
                        <h2>CivicTrack</h2>
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
                <a href="manage_complaints.php">
                    <span class="nav-icon">📋</span> Manage Complaints
                </a>
                <a href="manage_users.php">
                    <span class="nav-icon">👥</span> Manage Users
                </a>
                <a href="manage_officers.php" class="active">
                    <span class="nav-icon">👮</span> Manage Officers
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
            <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

            <div class="page-header">
                <h1>Manage Officers</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <!-- Add Officer Card -->
            <div class="card">
                <div class="card-header">
                    <h3>➕ Add New Officer</h3>
                </div>
                <form id="addOfficerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="officer-name">Full Name</label>
                            <input type="text" id="officer-name" name="name" placeholder="Officer Name" required>
                        </div>
                        <div class="form-group">
                            <label for="officer-email">Email</label>
                            <input type="email" id="officer-email" name="email" placeholder="officer@example.com" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="officer-phone">Phone</label>
                            <input type="tel" id="officer-phone" name="phone" placeholder="+91 9876543210">
                        </div>
                        <div class="form-group">
                            <label for="officer-department">Department (Category)</label>
                            <select id="officer-department" name="department" required>
                                <option value="">Select Department</option>
                                <option value="Garbage">Garbage</option>
                                <option value="Electricity">Electricity</option>
                                <option value="Water">Water</option>
                                <option value="Roads">Roads</option>
                                <option value="Sewage">Sewage</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="officer-password">Password</label>
                            <input type="password" id="officer-password" name="password" placeholder="Min. 6 characters" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Officer</button>
                </form>
            </div>

            <!-- Officers Table -->
            <div class="card">
                <div class="card-header">
                    <h3>👮 All Officers (<?php echo count($officersList); ?>)</h3>
                </div>

                <div class="toolbar">
                    <div class="search-box">
                        <input type="text" id="search-input" placeholder="Search officers...">
                    </div>
                </div>

                <?php if (empty($officersList)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👮</div>
                        <p>No officers registered yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table id="officers-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Assignments</th>
                                    <th>Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($officersList as $o):
                                    $oid = (string) $o['_id'];
                                    $assignCount = $officerAssignmentCounts[$oid] ?? 0;
                                ?>
                                <tr id="officer-row-<?php echo $oid; ?>">
                                    <td style="color: var(--text-primary); font-weight: 500;">
                                        <div style="display:flex;align-items:center;gap:0.65rem;">
                                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--warning),var(--danger));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.82rem;flex-shrink:0;">
                                                <?php echo strtoupper(substr($o['name'], 0, 1)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($o['name']); ?>
                                        </div>
                                    </td>
                                    <td><span class="badge" style="background:var(--primary-dark);"><?php echo htmlspecialchars($o['department'] ?? 'General'); ?></span></td>
                                    <td><?php echo htmlspecialchars($o['email']); ?></td>
                                    <td><?php echo htmlspecialchars($o['phone'] ?? '—'); ?></td>
                                    <td>
                                        <?php if ($assignCount > 0): ?>
                                            <span style="color:var(--primary-light);font-weight:600;"><?php echo $assignCount; ?> assigned</span>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($o['created_at'] ?? '—'); ?></td>
                                    <td>
                                        <button class="btn btn-danger btn-sm" onclick="deleteOfficer('<?php echo $oid; ?>')">🗑️ Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            </div><!-- /.page-body -->
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        initTableSearch('search-input', 'officers-table');

        document.getElementById('addOfficerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const name = document.getElementById('officer-name').value.trim();
            const email = document.getElementById('officer-email').value.trim();
            const phone = document.getElementById('officer-phone').value.trim();
            const department = document.getElementById('officer-department').value;
            const password = document.getElementById('officer-password').value;

            if (!name || !email || !password || !department) {
                showToast('Name, email, department, and password are required.', 'error');
                return;
            }
            if (password.length < 6) {
                showToast('Password must be at least 6 characters.', 'error');
                return;
            }

            const result = await postJSON('../api/manage_officers.php', {
                action: 'add',
                name: name,
                email: email,
                phone: phone,
                department: department,
                password: password
            });

            if (result.success) {
                showToast(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        });

        async function deleteOfficer(id) {
            if (!confirmDelete('Delete this officer? Their assignments will be unassigned.')) return;

            const result = await postJSON('../api/manage_officers.php', {
                action: 'delete',
                officer_id: id
            });

            if (result.success) {
                showToast(result.message, 'success');
                const row = document.getElementById('officer-row-' + id);
                if (row) row.remove();
            } else {
                showToast(result.message, 'error');
            }
        }
    </script>
</body>
</html>
