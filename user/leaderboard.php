<?php
/**
 * ReportMyCity — Citizen Leaderboard
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/Gamification.php';

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

$gamification = Gamification::getInstance();

// Filters
$city = $_GET['city'] ?? '';
$state = $_GET['state'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$filter = [
    'city' => $city,
    'state' => $state,
    'limit' => $limit
];

$leaderboardCursor = $gamification->getLeaderboard($filter);
$leaderboard = iterator_to_array($leaderboardCursor);

// User info for sidebar
$userDoc = $db->getCollection('users')->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
$userPhoto = $userDoc['photo'] ?? '';
$initials = strtoupper(substr($userName, 0, 1));

// Gamification stats for header
$stats = $gamification->getUserStats($userId);
$points = $stats['points'] ?? 0;
$streak = $stats['streak'] ?? 0;
$level = $stats['level'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Leaderboard — ReportMyCity</title>
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
                    <img src="../assets/images/logo.png" alt="ReportMyCity" style="width: 100%; max-width:   250px; height: auto; object-fit: contain; margin: 0 auto;">
                </div>
            </div>
            <div class="sidebar-gold-stripe"></div>
            <nav class="sidebar-nav">
                <div class="sidebar-section-label">Navigation</div>
                <a href="dashboard.php">
                    <span class="nav-icon">📊</span> Dashboard
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
                <a href="leaderboard.php" class="active">
                    <span class="nav-icon">🏆</span> Leaderboard
                </a>
                <div class="sidebar-section-label" style="margin-top:1.5rem; color:#ef4444;">🛡️ Oversight</div>
                <a href="my_complaints.php" style="color:#ef4444; background: rgba(239, 68, 68, 0.05); border: 1px dashed rgba(239, 68, 68, 0.2);">
                    <span class="nav-icon">👮</span> Report Officer Conduct
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
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
                    <div class="header-logo-group"><img src="../assets/images/logo.png" alt="ReportMyCity" style="height:   70px; width: auto; object-fit: contain;"></div>
                    <div>
                        <h1>🏆 City Guardians Leaderboard</h1>
                        <div class="breadcrumb">
                            <a href="dashboard.php">Home</a>
                            <span>›</span>
                            <span>Leaderboard</span>
                        </div>
                    </div>
                </div>

                <div class="user-info">
                    <span style="font-size:0.82rem; color:var(--text-muted);"><?php echo date('d M Y'); ?></span>
                    <span class="user-welcome-text">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                    
                    <!-- Header Gamification Badges -->
                    <div class="header-gamification">
                        <div class="header-g-badge points" title="Total Points">✨ <?php echo $points; ?></div>
                        <div class="header-g-badge streak" title="Daily Streak">🔥 <?php echo $streak; ?>d</div>
                        <div class="header-g-badge level" title="Citizen Level">🛡️ Lvl <?php echo $level; ?></div>
                    </div>

                    <!-- Profile Dropdown -->
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
                                <span><?php echo htmlspecialchars($userEmail); ?></span>
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

            <div style="padding: 2rem;">
                <!-- Filter Section -->
                <div class="card" style="margin-bottom: 2rem;">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                            <label>Filter by City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="Enter city...">
                        </div>
                        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                            <label>Time Period</label>
                            <select name="period">
                                <option value="all" <?php echo ($_GET['period'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All-Time</option>
                                <option value="monthly" <?php echo ($_GET['period'] ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="weekly" <?php echo ($_GET['period'] ?? '') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                            <label>Show Top</label>
                            <select name="limit">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>Top 10</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>Top 25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>Top 50</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                        <a href="leaderboard.php" class="btn btn-outline">🔄 Reset</a>
                    </form>
                </div>

                <!-- Leaderboard Table -->
                <div class="card">
                    <div class="table-wrapper">
                        <table class="leaderboard-table">
                            <thead>
                                <tr style="background: transparent;">
                                    <th class="rank-number">#</th>
                                    <th>User</th>
                                    <th>Level</th>
                                    <th>Badges</th>
                                    <th style="text-align: right;">Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaderboard)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">No users found for this filter.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $rank = 1; foreach ($leaderboard as $user): ?>
                                        <tr class="leaderboard-row <?php echo (string)$user['_id'] == $userId ? 'current-user-highlight' : ''; ?>">
                                            <td class="leaderboard-td rank-number <?php echo $rank <= 3 ? 'rank-top-'.$rank : ''; ?>">
                                                <?php 
                                                if ($rank == 1) echo '🥇';
                                                elseif ($rank == 2) echo '🥈';
                                                elseif ($rank == 3) echo '🥉';
                                                else echo $rank;
                                                ?>
                                            </td>
                                            <td class="leaderboard-td">
                                                <div class="user-cell">
                                                    <?php if (!empty($user['photo'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile">
                                                    <?php else: ?>
                                                        <div class="sidebar-user-avatar"><?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?></div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="user-name">
                                                            <?php echo htmlspecialchars($user['name'] ?? 'Anonymous'); ?>
                                                            <?php if ((string)$user['_id'] == $userId): ?>
                                                                <span class="badge" style="background: #e0f2fe; color: #0369a1; margin-left: 5px;">You</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="user-city">📍 <?php echo htmlspecialchars($user['city'] ?? 'Unknown City'); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="leaderboard-td">
                                                <span class="badge-level">Lvl <?php echo floor(($user['points'] ?? 0) / 100) + 1; ?></span>
                                            </td>
                                            <td class="leaderboard-td">
                                                <div style="display: flex; gap: 5px; flex-wrap: wrap; max-width: 250px;">
                                                    <?php 
                                                    $userBadges = $user['badges'] ?? [];
                                                    foreach (array_slice($userBadges, 0, 3) as $badge): 
                                                    ?>
                                                        <span class="badge-pill" style="font-size: 0.65rem;">🏆 <?php echo $badge; ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($userBadges) > 3): ?>
                                                        <span style="font-size: 0.7rem; color: var(--text-muted);">+<?php echo count($userBadges) - 3; ?> more</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="leaderboard-td" style="text-align: right;">
                                                <div style="font-weight: 800; font-size: 1.1rem; color: var(--gov-navy);">
                                                    ✨ <?php echo number_format($user['points'] ?? 0); ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php $rank++; endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .current-user-highlight {
            border: 2px solid var(--gov-gold);
            background: #fffdf5 !important;
        }
        .leaderboard-row {
            border-bottom: 1px solid var(--border);
        }
    </style>
    <script src="../assets/js/main.js"></script>
    <script>
        // Profile Dropdown
        const pd_wrapper = document.getElementById('profileDropdownWrapper');
        if (pd_wrapper) {
            pd_wrapper.addEventListener('click', function(e) { 
                e.stopPropagation(); 
                this.classList.toggle('open'); 
            });
            document.addEventListener('click', () => { 
                pd_wrapper.classList.remove('open'); 
            });
        }
    </script>
</body>
</html>
