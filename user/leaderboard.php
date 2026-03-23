<?php
/**
 * ReportMyCity — Civic Leaderboard
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$complaints = $db->getCollection('complaints');
$users = $db->getCollection('users');
$userId = $_SESSION['user_id'];

// Stats for current user
$totalComplaints   = $complaints->countDocuments(['user_id' => $userId]);
$resolvedComplaints = $complaints->countDocuments(['user_id' => $userId, 'status' => 'Resolved']);

$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$userDoc = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
$userPhoto = $userDoc['photo'] ?? '';
$initials = strtoupper(substr($userName, 0, 1));

// Full Leaderboard Logic (Top 20)
$pipeline = [
    ['$group' => [
        '_id' => '$user_id',
        'total' => ['$sum' => 1],
        'resolved' => [
            '$sum' => ['$cond' => [['$eq' => ['$status', 'Resolved']], 1, 0]]
        ]
    ]],
    ['$addFields' => [
        'points' => ['$add' => [['$multiply' => ['$total', 10]], ['$multiply' => ['$resolved', 15]]]]
    ]],
    ['$sort' => ['points' => -1]],
    ['$limit' => 20]
];
$leaderboardStats = $complaints->aggregate($pipeline);
$leaderboard = [];
$userPoints = ($totalComplaints * 10) + ($resolvedComplaints * 15);
$userRank = "N/A";

$index = 1;
foreach ($leaderboardStats as $stat) {
    if (!$stat['_id']) continue;
    $u = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($stat['_id'])]);
    if ($u) {
        $leaderboard[] = [
            'rank' => $index,
            'name' => $u['name'],
            'photo' => $u['photo'] ?? '',
            'points' => $stat['points'],
            'resolved' => $stat['resolved'],
            'is_current_user' => ((string)$stat['_id'] === $userId)
        ];
        if ((string)$stat['_id'] === $userId) $userRank = $index;
    }
    $index++;
}

// Global Stats
$totalCityPoints = $complaints->countDocuments() * 10 + $complaints->countDocuments(['status' => 'Resolved']) * 15;
$totalCivicHeroes = $users->countDocuments(['role' => 'user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civic Leaderboard — ReportMyCity</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Serif:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="gov-animation-layer">
        <div class="gov-blob blob-navy"></div>
        <div class="gov-blob blob-gold"></div>
        <div class="gov-grid"></div>
    </div>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-inner">
                    <img src="../assets/images/govt_emblem.png" alt="Emblem" class="sidebar-emblem">
                    <div class="sidebar-brand-text">
                        <h2>ReportMyCity</h2>
                        <span>Citizen Portal</span>
                    </div>
                </div>
            </div>
            <div class="sidebar-gold-stripe"></div>
            <nav class="sidebar-nav">
                <div class="sidebar-section-label">Navigation</div>
                <a href="dashboard.php">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="leaderboard.php" class="active">
                    <span class="nav-icon">🏆</span> Civic Leaderboard
                </a>
                <a href="submit_complaint.php">
                    <span class="nav-icon">📝</span> Submit Complaint
                </a>
                <a href="my_complaints.php">
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
                    <button class="sidebar-toggle">☰</button>
                    <div class="header-logo-group">
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(250, 249, 248, 0.3));">
                        <span>ReportMyCity</span>
                    </div>
                    <div>
                        <h1>🏆 Civic Leaderboard</h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Home</a>
                            <span>›</span>
                            <span>Civic Heroes Ranking</span>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span style="font-size:0.82rem; color:var(--text-muted);"><?php echo date('d M Y'); ?></span>
                    <span style="background: var(--gov-gold-glow); color: var(--gov-gold); padding: 4px 12px; border-radius: 20px; font-weight: 700; border: 1px solid var(--gov-gold);">🎖️ <?php echo $userPoints; ?> pts</span>
                </div>
            </div>

            <!-- Global Leaderboard Stats -->
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
                <div class="stat-card orange">
                    <div class="stat-icon">👑</div>
                    <div class="stat-value">#<?php echo $userRank; ?></div>
                    <div class="stat-label">Your Current Rank</div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-value"><?php echo number_format($totalCityPoints); ?></div>
                    <div class="stat-label">Total City Civic Points</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $totalCivicHeroes; ?></div>
                    <div class="stat-label">Active Civic Heroes</div>
                </div>
            </div>

            <div class="card" style="border: 1px solid var(--gov-gold); box-shadow: var(--shadow-gold);">
                <div class="card-header" style="background: var(--gov-gold-glow); border-bottom: 1px solid var(--gov-gold-pale);">
                    <h3>TOP 20 Civic Heroes</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Updated in real-time based on verified civic actions</p>
                </div>
                <div class="table-wrapper">
                    <table class="leaderboard-full-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Rank</th>
                                <th>Citizen</th>
                                <th>Verified Solutions</th>
                                <th style="text-align: right;">Civic Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboard as $hero): ?>
                            <tr class="<?php echo $hero['is_current_user'] ? 'current-user-row' : ''; ?>" style="<?php echo $hero['is_current_user'] ? 'background: rgba(200, 146, 42, 0.08); border: 1px solid var(--gov-gold);' : ''; ?>">
                                <td style="font-weight: 800; font-size: 1.1rem; color: var(--gov-gold);">
                                    <?php 
                                    if ($hero['rank'] == 1) echo '🥇';
                                    elseif ($hero['rank'] == 2) echo '🥈';
                                    elseif ($hero['rank'] == 3) echo '🥉';
                                    else echo $hero['rank'] . '.'; 
                                    ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 45px; height: 45px; border-radius: 50%; overflow: hidden; border: 2px solid var(--border);">
                                            <?php if ($hero['photo']): ?>
                                                <img src="../<?php echo htmlspecialchars($hero['photo']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="width: 100%; height: 100%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569;"><?php echo strtoupper(substr($hero['name'], 0, 1)); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--gov-navy);"><?php echo htmlspecialchars($hero['name']); ?> <?php if ($hero['is_current_user']) echo '<span style="font-size: 0.65rem; background: var(--gov-navy); color: white; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">YOU</span>'; ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);">Active Member</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight: 600; color: var(--success);">✅ <?php echo $hero['resolved']; ?> Solutions</td>
                                <td style="text-align: right;">
                                    <div style="font-weight: 800; font-size: 1.25rem; color: var(--gov-gold);"><?php echo number_format($hero['points']); ?></div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">pts</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- jQuery and Animation Scripts (consistent with other pages) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $(document).mousemove(function(e) {
                const x = (e.clientX / window.innerWidth - 0.5) * 40;
                const y = (e.clientY / window.innerHeight - 0.5) * 40;
                $('.blob-navy').css('transform', `translate(${x}px, ${y}px)`);
                $('.blob-gold').css('transform', `translate(${-x}px, ${-y}px)`);
            });
        });
    </script>
</body>
</html>
