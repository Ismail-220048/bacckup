<?php
/**
 * CivicTrack — Landing Page
 * Redirects to user/admin dashboard if logged in, otherwise displays landing page.
 */
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } elseif ($_SESSION['role'] === 'officer') {
        header('Location: officer/officer_dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CivicTrack — Report civic issues, track progress, and build better communities.">
    <title>CivicTrack — Better Communities Together</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="formal-landing">

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <a href="#" class="nav-logo">
            <h2>🏛️ CivicTrack</h2>
        </a>
        <div class="nav-links">
            <a href="#about" class="nav-link">Platform Overview</a>
            <a href="#features" class="nav-link">Key Features</a>
            <a href="login.php" class="btn-outline" style="border-radius: 4px; padding: 0.5rem 1.25rem; font-weight: 600; text-decoration: none;">Secure Login</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1 class="hero-title">Civic Accountability,<br>Modernized Infrastructure.</h1>
            <p class="hero-subtitle">CivicTrack is the official unified administrative platform for securely reporting community incidents, maintaining verifiable audit trails, and ensuring immediate transparent resolutions from assigned authorities.</p>
            <div class="hero-actions">
                <a href="register.php" class="hero-btn hero-btn-primary">
                    Citizen Registration
                </a>
                <a href="#about" class="hero-btn hero-btn-outline">View Details</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="section-container">
            <div class="section-header">
                <h2>Why CivicTrack?</h2>
                <p>We bridge the gap between citizens, civil officers, and government administration, ensuring transparency and swift action on community grievances.</p>
            </div>
            
            <div class="features-grid" id="features">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <div class="feature-icon">📢</div>
                    <h3 class="feature-title">Effortless Reporting</h3>
                    <p class="feature-desc">Snap a picture, drop a pin, and report local issues ranging from potholes to erratic water supply in under 60 seconds.</p>
                </div>
                <!-- Feature 2 -->
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3 class="feature-title">Real-Time Tracking</h3>
                    <p class="feature-desc">Receive immediate notifications when your complaint is assigned to an officer, investigated, and ultimately resolved.</p>
                </div>
                <!-- Feature 3 -->
                <div class="feature-card">
                    <div class="feature-icon">⚖️</div>
                    <h3 class="feature-title">Absolute Transparency</h3>
                    <p class="feature-desc">Hold your local authorities accountable. See response times, transparent audit trails, and officer resolution notes directly.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-logo">🏛️ CivicTrack</div>
        <p class="footer-text">Building resilient, connected, and responsive communities.</p>
        <div class="footer-links">
            <a href="login.php">Citizen Login</a>
            <a href="officer/officer_login.php">Officer Portal</a>
            <a href="admin/admin_login.php">Admin Console</a>
        </div>
        <p class="copyright">© 2026 CivicTrack Platform. All rights reserved.</p>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        // Navbar Scrolled Effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth Scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
