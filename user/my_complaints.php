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
$userDoc = $db->getCollection('users')->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
$userPhoto = $userDoc['photo'] ?? '';
$initials = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints — CivicTrack</title>
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
                        <span>Citizen Portal</span>
                    </div>
                </div>
            </div>
            <div class="sidebar-gold-stripe"></div>
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
                <a href="profile.php">
                    <span class="nav-icon">👤</span> My Profile
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar">
                        <?php if ($userPhoto): ?>
                            <img src="../<?php echo htmlspecialchars($userPhoto); ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <?php echo $initials; ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="sidebar-user-role">Citizen</div>
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
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(200,146,42,0.3));">
                        <span>CivicTrack</span>
                    </div>
                    <h1>My Complaints</h1>
                </div>

                <div class="user-info">
                    <span><?php echo htmlspecialchars($userName); ?></span>
                    <div class="profile-dropdown-wrapper" id="profileDropdownWrapper">
                        <div class="user-avatar">
                            <?php if ($userPhoto): ?>
                                <img src="../<?php echo htmlspecialchars($userPhoto); ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                        </div>
                        <div class="profile-dropdown-menu">
                            <div class="profile-dropdown-header">
                                <strong><?php echo htmlspecialchars($userName); ?></strong>
                                <span>Citizen Account</span>
                            </div>
                            <a href="profile.php">
                                <div class="dropdown-icon">⚙️</div> Profile Settings
                            </a>
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
                                    <th>Officer Notes</th>
                                    <th>Rating & Feedback</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaintsList as $c): 
                                    $cId = (string)$c['_id'];
                                ?>
                                <tr class="clickable-row" onclick="viewComplaint('<?php echo $cId; ?>')">
                                    <td style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);">
                                        <?php echo substr((string)$c['_id'], -6); ?>
                                    </td>
                                    <td style="color: var(--text-primary); font-weight: 500;">
                                        <?php echo htmlspecialchars($c['title']); ?>
                                        <?php if (!empty($c['image'])): ?>
                                            <br><a href="../<?php echo htmlspecialchars($c['image']); ?>" target="_blank" style="font-size: 0.78rem; color: var(--accent);">📷 View Image</a>
                                        <?php endif; ?>
                                    </td>
                                     <td>
                                         <?php echo htmlspecialchars($c['category']); ?>
                                         <br><small style="color:var(--text-muted);">Priority: <?php echo htmlspecialchars($c['risk_type'] ?? 'Medium'); ?></small>
                                     </td>
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
                                        $notes = $c['officer_notes'] ?? '';
                                        echo $notes ? htmlspecialchars($notes) : '<span style="color: var(--text-muted);">—</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($status === 'Resolved'): ?>
                                            <?php if (isset($c['rating'])): ?>
                                                <div class="rating-display">
                                                    <?php for($i=1; $i<=5; $i++): ?>
                                                        <span class="star-static <?php echo ($i <= $c['rating']) ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                    <span style="font-size: 0.7rem; display: block; color: var(--text-muted); font-weight: 400;">
                                                        "<?php echo htmlspecialchars($c['user_feedback'] ?? ''); ?>"
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <button class="btn btn-gold btn-sm" onclick="event.stopPropagation(); openFeedbackModal('<?php echo (string)$c['_id']; ?>', '<?php echo htmlspecialchars($c['title'], ENT_QUOTES); ?>')">
                                                    ⭐ Rate Work
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">Available after resolve</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); viewComplaint('<?php echo $cId; ?>')">👁️ View</button>
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

    <!-- Rating Modal -->
    <div id="feedbackModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>⭐ Rate Officer's Work</h3>
                <button class="modal-close" onclick="closeFeedbackModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modal-complaint-title" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;"></p>
                <form id="feedbackForm">
                    <input type="hidden" name="complaint_id" id="modal-complaint-id">
                    
                    <div class="form-group" style="text-align: center; margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 1rem;">How would you rate the resolution?</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5"><label for="star5">★</label>
                            <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                            <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                            <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                            <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="feedback">Additional Feedback (Optional)</label>
                        <textarea name="feedback" id="feedback" placeholder="Describe your experience with the resolution..." style="min-height: 80px; width: 100%;"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 2rem;">
                        <button type="button" class="btn btn-outline btn-block" onclick="closeFeedbackModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-block">Submit Feedback</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Complaint Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal" style="width: 100%; max-width: 750px; padding: 2.5rem; border-radius: var(--radius-lg);">
            <div class="modal-header" style="border-bottom: 2px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.6rem; font-weight: 800; color: var(--gov-navy);">📋 Task & Issue Details</h3>
                <button class="modal-close" style="font-size: 1.8rem;" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewContent"></div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Profile Dropdown
        const pdw = document.getElementById('profileDropdownWrapper');
        if (pdw) {
            pdw.addEventListener('click', function(e) { e.stopPropagation(); this.classList.toggle('open'); });
            document.addEventListener('click', () => pdw.classList.remove('open'));
        }
    </script>
    <script>
        const complaintsData = <?php echo json_encode(array_map(function($c) {
            return [
                '_id'                   => (string) $c['_id'],
                'title'                 => $c['title'],
                'category'              => $c['category'],
                'description'           => $c['description'],
                'location'              => $c['location'] ?? '',
                'image'                 => $c['image'] ?? '',
                'officer_proof_image'   => $c['officer_proof_image'] ?? '',
                'date'                  => $c['date'] ?? $c['created_at'],
                'risk_type'             => $c['risk_type'] ?? 'Medium',
                'status'                => $c['status'],
                'admin_reply'           => $c['admin_reply'] ?? '',
                'officer_notes'         => $c['officer_notes'] ?? '',
                'assigned_officer_name' => $c['assigned_officer_name'] ?? '',
                'created_at'            => $c['created_at']
            ];
        }, $complaintsList)); ?>;

        initTableSearch('search-input', 'complaints-table');
        initStatusFilter('status-filter', 'complaints-table');

        function viewComplaint(id) {
            const c = complaintsData.find(item => item._id === id);
            if (!c) return;

            let progress = 0;
            let step1 = 'active', step2 = '', step3 = '', step4 = '';
            
            if (c.status === 'Resolved') {
                progress = 100;
                step1 = 'completed'; step2 = 'completed'; step3 = 'completed'; step4 = 'completed';
            } else if (c.status === 'In Progress' || c.status === 'Officer Completed') {
                progress = 66;
                step1 = 'completed'; step2 = 'completed'; step3 = 'active';
            } else if (c.assigned_officer_name) {
                progress = 33;
                step1 = 'completed'; step2 = 'active';
            } else {
                progress = 0;
                step1 = 'active';
            }

            let html = `
                <div class="tracking-wrapper">
                    <div class="stepper">
                        <div class="stepper-progress" id="tracker-bar"></div>
                        <div class="step-item ${step1}">
                            <div class="step-circle">📝</div>
                            <div class="step-label">Reported</div>
                        </div>
                        <div class="step-item ${step2}">
                            <div class="step-circle">👮</div>
                            <div class="step-label">Dispatched</div>
                        </div>
                        <div class="step-item ${step3}">
                            <div class="step-circle">🔧</div>
                            <div class="step-label">On-Site Action</div>
                        </div>
                        <div class="step-item ${step4}">
                            <div class="step-circle">✅</div>
                            <div class="step-label">Resolved</div>
                        </div>
                    </div>

                    <div class="tracker-details">
                        <div class="tracker-info-box">
                            <h5>Current Status</h5>
                            <p>${c.status}</p>
                        </div>
                        <div class="tracker-info-box">
                            <h5>Assigned Officer</h5>
                            <p>${c.assigned_officer_name || 'Awaiting Assignment'}</p>
                        </div>
                    </div>
                </div>

                <div class="complaint-detail-grid" style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Tracking ID</label><p style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-top: 0.2rem;">#${c._id.substr(-6)}</p></div>
                    <div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Category</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary); margin-top: 0.2rem;">${c.category}</p></div>
                    <div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Risk Level</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary); margin-top: 0.2rem;">${c.risk_type}</p></div>
                    <div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Location</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary); margin-top: 0.2rem;">${c.location}</p></div>
                    <div class="detail-item"><label style="color:var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700;">Date Reported</label><p style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary); margin-top: 0.2rem;">${c.date}</p></div>
                </div>
                
                <div style="margin-top:1.5rem; background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); border-left: 4px solid var(--primary-light);">
                    <label style="display:block;font-size:0.85rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;">Citizen Description</label>
                    <p style="color:var(--text-primary);font-size:1.05rem; line-height: 1.6;">${c.description}</p>
                 </div>
            `;

            if (c.admin_reply) {
                html += `<div style="margin-top:1rem; background: rgba(59, 130, 246, 0.05); padding: 1.2rem; border-radius: var(--radius-md); border: 1px solid rgba(59, 130, 246, 0.2);">
                            <label style="display:block;font-size:0.85rem;color:var(--gov-navy);font-weight:700;text-transform:uppercase;margin-bottom:0.4rem;">Official Response</label>
                            <p style="color:var(--text-primary);font-size:1rem; line-height: 1.5;">${c.admin_reply}</p>
                         </div>`;
            }

            if (c.image || c.officer_proof_image) {
                html += `<div style="margin-top:1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">`;
                if (c.image) {
                    html += `<div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                                <label style="display:block;font-size:0.85rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:0.8rem;">Your Submission Image</label>
                                <img src="../${c.image}" alt="Complaint Image" style="display:block; width:100%; height:auto; max-height: 350px; object-fit: contain; border-radius:var(--radius-sm); border:1px solid var(--border);">
                             </div>`;
                }
                if (c.officer_proof_image) {
                    html += `<div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                                <label style="display:block;font-size:0.85rem;color:var(--success);font-weight:700;text-transform:uppercase;margin-bottom:0.8rem;">Resolution Proof Image</label>
                                <img src="../${c.officer_proof_image}" alt="Proof Image" style="display:block; width:100%; height:auto; max-height: 350px; object-fit: contain; border-radius:var(--radius-sm); border:1px solid var(--border);">
                             </div>`;
                }
                html += `</div>`;
            }

            document.getElementById('viewContent').innerHTML = html;
            openModal('viewModal');

            // Animate progress bar after modal opens
            setTimeout(() => {
                const bar = document.getElementById('tracker-bar');
                if (bar) bar.style.width = progress + '%';
            }, 300);
        }

        function openFeedbackModal(id, title) {
            document.getElementById('modal-complaint-id').value = id;
            document.getElementById('modal-complaint-title').innerText = title;
            openModal('feedbackModal');
        }

        function closeFeedbackModal() {
            closeModal('feedbackModal');
            document.getElementById('feedbackForm').reset();
        }

        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            if (!formData.get('rating')) {
                alert('Please select a star rating.');
                return;
            }

            fetch('../api/submit_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            });
        });

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target == modal) {
                closeFeedbackModal();
            }
        }
    </script>
</body>
</html>
