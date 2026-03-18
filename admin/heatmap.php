<?php
/**
 * CivicTrack — Admin: Heatmap
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Heatmap — CivicTrack Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Serif:wght@400;700&display=swap" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        #heatmap-container {
            height: 600px; 
            width: 100%; 
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            z-index: 10;
        }
        .legend-container {
            padding: 1rem; 
            border-bottom: 1px solid var(--border); 
            display: flex; 
            flex-wrap: wrap;
            gap: 20px;
            background: var(--bg-card);
            align-items: center;
        }
    </style>
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
                <a href="heatmap.php" class="active">
                    <span class="nav-icon">🗺️</span> Heatmap
                </a>
                <a href="manage_complaints.php">
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
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(200,146,42,0.3));">
                        <span>CivicTrack</span>
                    </div>
                    <h1>Complaint Density Heatmap</h1>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>


                <div class="card">
                <div class="card-header">
                    <h3>🔥 Live Complaint Activity Heatmap</h3>
                </div>
                
                <!-- Legend -->
                <div class="legend-container">
                    <strong>Intensity Legend:</strong>
                    <span style="color: #ff0000; font-weight: bold; display: flex; align-items: center; gap: 5px;"><div style="width: 15px; height: 15px; background: #ff0000; border-radius: 50%;"></div> High Density (Red Zone)</span>
                    <span style="color: #ff8c00; font-weight: bold; display: flex; align-items: center; gap: 5px;"><div style="width: 15px; height: 15px; background: #ff8c00; border-radius: 50%;"></div> Medium Density (Orange)</span>
                    <span style="color: #ffd700; font-weight: bold; display: flex; align-items: center; gap: 5px;"><div style="width: 15px; height: 15px; background: #ffd700; border-radius: 50%;"></div> Low Density (Yellow)</span>
                    <span style="color: #008000; font-weight: bold; display: flex; align-items: center; gap: 5px;"><div style="width: 15px; height: 15px; background: #00ff00; border-radius: 50%;"></div> Sparse (Green)</span>
                </div>
                
                <!-- Map Container -->
                <div id="heatmap-container"></div>
            </div>

        </main>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Leaflet.heat JS -->
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Init Map
        const map = L.map('heatmap-container').setView([20.5937, 78.9629], 5); // Default center (e.g., India)
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Fetch location data
        fetch('../api/get_heatmap_data.php')
            .then(res => res.json())
            .then(res => {
                if(res.success && res.data && res.data.length > 0) {
                    // Extract data in [lat, lng, intensity] format
                    // leaflet-heat takes array of [lat, lng, intensity] or just [lat, lng]
                    const heatData = res.data;
                    
                    // Create heat layer
                    const heat = L.heatLayer(heatData, {
                        radius: 25,
                        blur: 15,
                        maxZoom: 16,
                        gradient: {
                            0.2: '#00ff00', // Green
                            0.4: '#ffd700', // Yellow
                            0.6: '#ff8c00', // Orange
                            0.9: '#ff0000'  // Red
                        }
                    }).addTo(map);

                    // Auto fit map bounds to the data
                    // Res.data is array of [lat, lng, intensity]
                    const bounds = L.latLngBounds(heatData.map(p => [p[0], p[1]]));
                    map.fitBounds(bounds, { padding: [50, 50] });
                } else {
                    showToast('No location data available for heatmap.', 'info');
                }
            })
            .catch(err => {
                console.error('Heatmap fetch error:', err);
                showToast('Failed to load heatmap data.', 'error');
            });
    </script>
</body>
</html>
