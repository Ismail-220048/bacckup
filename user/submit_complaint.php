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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        #map {
            height: 250px;
            width: 100%;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            margin-bottom: 0.5rem;
            z-index: 10;
        }
    </style>
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
                    Logout <span class="nav-icon" style="margin-left: auto;">🚪</span>
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
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Evidence & Location (Pinpoint on Map)</label>
                            
                            <div style="position: relative;">
                                <div id="map"></div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                                <input type="text" id="location" name="location" placeholder="Select location on map or permit GPS access." required readonly style="background: var(--bg-body); cursor: not-allowed; flex: 1;">
                                <button type="button" id="btn-get-location" class="btn btn-info btn-sm">📍 Locate Me</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: column; align-items: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                        <label style="margin-bottom: 1rem;">Evidence Document / Photo (optional)</label>
                        <div style="display: flex; gap: 15px; margin-bottom: 1rem; justify-content: center; width: 100%;">
                            <label class="btn btn-primary" style="cursor: pointer; margin: 0; border-radius: 20px; padding: 0.6rem 1.5rem;" for="complaint-image">
                                📁 Upload image
                            </label>
                            <input type="file" id="complaint-image" name="image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                            <button type="button" class="btn btn-warning" id="open-camera-btn" style="border-radius: 20px; color: #161822; padding: 0.6rem 1.5rem;">
                                📸 Live Camera
                            </button>
                        </div>
                        
                        <!-- Camera Interface -->
                        <div id="camera-interface" style="display: none; flex-direction: column; align-items: center; gap: 10px; background: var(--bg-input); padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 10px;">
                            <video id="camera-stream" autoplay playsinline style="width: 100%; max-width: 400px; border-radius: var(--radius-sm); background: #000;"></video>
                            <div style="display: flex; gap: 10px; width: 100%; max-width: 400px;">
                                <button type="button" class="btn btn-success" id="capture-btn" style="flex: 1;">📱 Snap Photo</button>
                                <button type="button" class="btn btn-danger" id="close-camera-btn" style="flex: 1;">❌ Close</button>
                            </div>
                        </div>

                        <canvas id="camera-canvas" style="display: none;"></canvas>
                        
                        <div id="preview-container" style="display: none; position: relative; max-width: 400px; margin: 0 auto;">
                            <img id="image-preview" class="image-preview" src="" style="width: 100%; border-radius: var(--radius-md); border: 1px solid var(--border);" alt="Preview">
                            <button type="button" class="btn btn-danger btn-sm" id="clear-image-btn" style="position: absolute; top: 10px; right: 10px; border-radius: 50%; width: 30px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">✕</button>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem; font-size: 1.05rem; border-radius: 30px;">
                            Submit Complaint
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // --- MAP LOGIC ---
        // Default to a generic location, e.g., center of a placeholder city (New York)
        let defaultLat = 40.7128;
        let defaultLng = -74.0060;
        
        let map = L.map('map').setView([defaultLat, defaultLng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
        const locationInput = document.getElementById('location');

        // Function to update input based on marker
        function updateLocationInput(lat, lng) {
            locationInput.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        // Initialize with default
        updateLocationInput(defaultLat, defaultLng);

        // When marker is dragged
        marker.on('dragend', function (e) {
            const pos = marker.getLatLng();
            updateLocationInput(pos.lat, pos.lng);
        });

        // When map is clicked
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateLocationInput(e.latlng.lat, e.latlng.lng);
        });

        // Get Live Location
        document.getElementById('btn-get-location').addEventListener('click', () => {
            if ("geolocation" in navigator) {
                const btn = document.getElementById('btn-get-location');
                btn.textContent = 'Locating...';
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        map.setView([lat, lng], 16);
                        marker.setLatLng([lat, lng]);
                        updateLocationInput(lat, lng);
                        
                        btn.textContent = '📍 My Location';
                        showToast('Location updated successfully', 'success');
                    },
                    (error) => {
                        console.error("Error obtaining location:", error);
                        btn.textContent = '📍 My Location';
                        showToast('Unable to retrieve your location.', 'error');
                    },
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                );
            } else {
                showToast('Geolocation is not supported by your browser.', 'error');
            }
        });

        // --- CAMERA LOGIC ---
        let capturedDataURL = null;
        let stream = null;

        const cameraInterface = document.getElementById('camera-interface');
        const cameraStream = document.getElementById('camera-stream');
        const cameraCanvas = document.getElementById('camera-canvas');
        const imagePreview = document.getElementById('image-preview');
        const previewContainer = document.getElementById('preview-container');
        const openCameraBtn = document.getElementById('open-camera-btn');
        const captureBtn = document.getElementById('capture-btn');
        const closeCameraBtn = document.getElementById('close-camera-btn');
        const clearImageBtn = document.getElementById('clear-image-btn');
        const fileInput = document.getElementById('complaint-image');

        // Normal File Upload Preview
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                    capturedDataURL = null; // Clear camera capture if file uploaded
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Open Camera
        openCameraBtn.addEventListener('click', async () => {
            try {
                const constraints = { video: { facingMode: 'environment' } };
                stream = await navigator.mediaDevices.getUserMedia(constraints);
                cameraStream.srcObject = stream;
                cameraInterface.style.display = 'flex';
                openCameraBtn.style.display = 'none';
                previewContainer.style.display = 'none'; // hide preview while camera is open
            } catch (err) {
                console.error(err);
                showToast('Camera access denied or unavailable.', 'error');
            }
        });

        // Close Camera
        closeCameraBtn.addEventListener('click', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            cameraInterface.style.display = 'none';
            openCameraBtn.style.display = 'inline-block';
        });

        // Capture Frame
        captureBtn.addEventListener('click', () => {
            cameraCanvas.width = cameraStream.videoWidth;
            cameraCanvas.height = cameraStream.videoHeight;
            cameraCanvas.getContext('2d').drawImage(cameraStream, 0, 0);
            
            capturedDataURL = cameraCanvas.toDataURL('image/jpeg');
            imagePreview.src = capturedDataURL;
            previewContainer.style.display = 'block';
            
            // Close camera after capture
            closeCameraBtn.click();
            
            // Clear file input so it doesn't conflict
            fileInput.value = '';
        });

        // Clear Image
        clearImageBtn.addEventListener('click', () => {
            capturedDataURL = null;
            fileInput.value = '';
            imagePreview.src = '';
            previewContainer.style.display = 'none';
        });

        document.getElementById('complaintForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateComplaintForm(this)) return;

            const formData = new FormData(this);
            
            // If camera was used, convert base64 to Blob and override the image field
            if (capturedDataURL) {
                formData.delete('image');
                try {
                    const res = await fetch(capturedDataURL);
                    const blob = await res.blob();
                    formData.append('image', blob, 'camera_capture.jpg');
                } catch (e) {
                    console.error("Error converting camera data to blob", e);
                }
            }

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
