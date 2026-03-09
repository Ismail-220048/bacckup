<?php
/**
 * CivicTrack — Submit Complaint Page
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}
$userName = $_SESSION['user_name'] ?? 'User';
$initials = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint — CivicTrack</title>
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
                <a href="submit_complaint.php" class="active">
                    <span class="nav-icon">📝</span> Submit Complaint
                </a>
                <a href="my_complaints.php">
                    <span class="nav-icon">📋</span> My Complaints
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
                <h1>Submit a Complaint</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($userName); ?></span>
                    <div class="user-avatar"><?php echo $initials; ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>📝 New Civic Complaint</h3>
                </div>

                <form id="complaintForm" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Complaint Title</label>
                            <input type="text" id="title" name="title" placeholder="e.g. Broken road near main market" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="">Select category...</option>
                                <option value="Road Damage">🛣️ Road Damage</option>
                                <option value="Garbage">🗑️ Garbage</option>
                                <option value="Water Leakage">💧 Water Leakage</option>
                                <option value="Street Light Issue">💡 Street Light Issue</option>
                                <option value="Drainage Problem">🚰 Drainage Problem</option>
                                <option value="Other">📌 Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Describe the issue in detail..." required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" placeholder="e.g. MG Road, Sector 5" required>
                        </div>
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="complaint-image">Upload Image (optional)</label>
                        <input type="file" id="complaint-image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <img id="image-preview" class="image-preview" src="" style="display: none;" alt="Preview">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Submit Complaint
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('complaintForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateComplaintForm(this)) return;

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            const result = await postForm('../api/submit_complaint.php', formData);

            if (result.success) {
                showToast(result.message, 'success');
                setTimeout(() => window.location.href = 'my_complaints.php', 1500);
            } else {
                showToast(result.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Complaint';
            }
        });
    </script>
</body>
</html>
