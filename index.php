<?php
/**
 * CivicTrack — Official Citizen Services Portal
 * Landing Page
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
    <meta name="description" content="CivicTrack — Official Citizen Services Portal. Submit and track civic complaints with the government authority.">
    <title>CivicTrack — Official Citizen Services Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Serif:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="formal-landing">

    <!-- ====== GOV UTILITY/ANNOUNCE BAR ====== -->
    <div class="gov-announce-bar">
        <div class="announce-links">
            <span>🇮🇳 Government of India</span>
            <span class="announce-divider">|</span>
            <a href="#">Skip to Main Content</a>
            <span class="announce-divider">|</span>
            <a href="#">Screen Reader Access</a>
            <span class="announce-divider">|</span>
            <a href="#">Sitemap</a>
        </div>
        <div class="announce-links">
            <a href="#">हिंदी</a>
            <span class="announce-divider">|</span>
            <a href="#">English</a>
            <span class="announce-divider">|</span>
            <a href="#">Help</a>
        </div>
    </div>

    <!-- ====== NAVIGATION BAR (MERGED HEADER) ====== -->
    <nav class="navbar" id="navbar" style="padding: 10px 60px; align-items: center;">
        <a href="#" class="nav-logo" style="display: flex; align-items: center; gap: 15px; text-decoration: none;">
            <img src="assets/images/govt_emblem.png" alt="CivicTrack Government Emblem" class="gov-header-emblem" style="height: 58px; width: auto; filter: drop-shadow(0 0 6px rgba(200,146,42,0.3)); transition: filter 0.3s;">
            <div class="gov-brand-divider" style="width: 2px; height: 40px; background: rgba(200,146,42,0.5);"></div>
            <div class="gov-site-title" style="display: flex; flex-direction: column;">
                <h1 style="font-family: 'Noto Serif', Georgia, serif; font-size: 1.25rem; color: #0a2558; font-weight: 700; margin: 0; line-height: 1.1;">CivicTrack</h1>
                <div class="subtitle" style="font-size: 0.65rem; color: #c8922a; letter-spacing: 0.1em; text-transform: uppercase;">Official Portal</div>
            </div>
        </a>
        <div class="nav-links" style="align-items: center;">
            <a href="#" class="nav-link">Home</a>
            <a href="#features" class="nav-link">Services</a>
            <a href="#how-it-works" class="nav-link">How It Works</a>
            <a href="#footer" class="nav-link">About</a>
            <div style="width: 1px; height: 24px; background: #d1dae8; margin: 0 10px;"></div>
            <a href="login.php" class="nav-link" style="color: #c8922a; font-weight: 700;">Login</a>
            <a href="register.php" class="btn-outline" id="nav-signin-btn">Register</a>
        </div>
    </nav>

    <!-- ====== HERO SECTION ====== -->
    <section class="hero" id="home">
        <canvas id="particle-canvas" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:0;"></canvas>
        <div class="horizon-glow"></div><div class="horizon-glow-2"></div>
        <div class="hero-content">
            <!-- Left: text -->
            <div class="hero-text">
                <div class="hero-badge">
                    <span></span>
                    Official Government Portal · Version 2.0
                </div>
                <h1 class="hero-title">
                    Welcome to<br>
                    <span class="gradient-text">CivicTrack.</span>
                </h1>
                <p class="hero-subtitle">
                    An official government-authorised digital platform connecting citizens with
                    municipal authorities. Report civic issues — potholes, water leakage, sanitation,
                    public safety — with GPS location and photographic evidence. Your complaint
                    is our priority.
                </p>
                <div class="hero-actions">
                    <a href="register.php" class="hero-btn hero-btn-primary" id="hero-register-btn">
                        🏛️ Register as Citizen
                    </a>
                    <a href="#features" class="hero-btn hero-btn-outline" id="hero-features-btn">
                        View Services →
                    </a>
                </div>
            </div>

            <!-- Right: image with floating cards -->
            <div class="hero-visual">
                <div class="hero-image-wrapper">
                    <img src="assets/images/laptop.png" alt="CivicTrack Portal Dashboard Preview" id="hero-dashboard-img">

                    <!-- Floating stat card top-right -->
                    <div class="stat-card card-top">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <div class="stat-num">4,200+</div>
                            <div class="stat-label">Issues Resolved</div>
                        </div>
                    </div>

                    <!-- Floating stat card bottom-left -->
                    <div class="stat-card card-bottom">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-info">
                            <div class="stat-num">~3 Days</div>
                            <div class="stat-label">Avg. Response Time</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== STATS BAR ====== -->
    <div class="stats-bar">
        <div class="stats-inner">
            <div class="stat-item">
                <span class="s-num" id="stat-1">12,000+</span>
                <span class="s-label">Complaints Submitted</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="s-num" id="stat-2">4,200+</span>
                <span class="s-label">Issues Resolved</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="s-num" id="stat-3">98%</span>
                <span class="s-label">Citizen Satisfaction</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="s-num" id="stat-4">150+</span>
                <span class="s-label">Active Field Officers</span>
            </div>
        </div>
    </div>

    <!-- ====== FEATURES / SERVICES SECTION ====== -->
    <section class="about-section" id="features">
        <div class="section-container">
            <div class="section-header reveal">
                <div class="section-tag">⚙️ Citizen Services</div>
                <h2>Our Service Offerings</h2>
                <p>A Government-authorised digital platform for transparent and efficient civic issue management across all municipal zones.</p>
            </div>

            <div class="features-grid" id="about">

                <div class="feature-card reveal">
                    <span class="feature-icon">📍</span>
                    <h3 class="feature-title">Smart Issue Reporting</h3>
                    <p class="feature-desc">Report civic issues like potholes, garbage accumulation, or water leakage with photos and precise GPS location tagging in under 60 seconds.</p>
                </div>

                <div class="feature-card reveal">
                    <span class="feature-icon">📊</span>
                    <h3 class="feature-title">Real-Time Status Tracking</h3>
                    <p class="feature-desc">Track your complaint's progress live with clear status updates — Pending, In Progress, and Resolved — with officer notes attached.</p>
                </div>

                <div class="feature-card reveal">
                    <span class="feature-icon">🤖</span>
                    <h3 class="feature-title">AI-Assisted Classification</h3>
                    <p class="feature-desc">Our AI model analyses uploaded images and automatically categorises civic problems, reducing manual classification overhead.</p>
                </div>

                <div class="feature-card reveal">
                    <span class="feature-icon">🗺️</span>
                    <h3 class="feature-title">Zone-Based Routing</h3>
                    <p class="feature-desc">GPS-based complaint routing ensures reports reach the responsible municipal authority instantly without misrouting or delays.</p>
                </div>

                <div class="feature-card reveal">
                    <span class="feature-icon">🏛️</span>
                    <h3 class="feature-title">Government Dashboard</h3>
                    <p class="feature-desc">Authorities manage, assign, and escalate complaints via a dedicated analytics-rich admin console with performance metrics.</p>
                </div>

                <div class="feature-card reveal">
                    <span class="feature-icon">⚡</span>
                    <h3 class="feature-title">Priority-Based Resolution</h3>
                    <p class="feature-desc">Automated assignment workflows and priority queuing help resolve civic issues 60% faster than traditional complaint channels.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- ====== HOW IT WORKS ====== -->
    <section class="how-section" id="how-it-works">
        <div class="section-container">
            <div class="section-header reveal">
                <div class="section-tag">📋 Process</div>
                <h2>How CivicTrack Works</h2>
                <p>A simple, transparent 4-step process to get your civic issue resolved by the responsible authority.</p>
            </div>
            <div class="steps-row reveal">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Register & Login</h3>
                    <p>Create a verified citizen account using your mobile number and email.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Submit Complaint</h3>
                    <p>Report your civic issue with photos, GPS location, and a brief description.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Officer Assignment</h3>
                    <p>A field officer is assigned automatically based on your complaint zone.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Track & Resolve</h3>
                    <p>Monitor real-time status updates until the issue is marked as resolved.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== CTA SECTION ====== -->
    <section class="cta-section">
        <div class="reveal">
            <h2>Exercise Your Right as a Citizen</h2>
            <p>Join thousands of citizens already using CivicTrack to report and resolve civic issues in their communities effectively and transparently.</p>
            <a href="register.php" class="cta-btn" id="cta-register-btn">
                🏛️ Register as a Citizen
            </a>
            <a href="login.php" class="cta-btn-gold" id="cta-login-btn">
                🔐 Already Registered? Sign In
            </a>
        </div>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer class="footer" id="footer">
        <div class="footer-inner">
            <div class="footer-top">
                <div class="footer-brand">
                    <img src="assets/images/govt_emblem.png" alt="CivicTrack" class="footer-logo-img">
                    <div class="footer-brand-text">
                        <h4>CivicTrack</h4>
                        <span class="footer-ministry">Official Citizen Services Portal</span>
                        <p class="footer-text">Building resilient, connected, and transparent communities — one complaint at a time. An initiative of the Ministry of Urban Development.</p>
                    </div>
                </div>

                <div class="footer-links">
                    <h4>Portals</h4>
                    <a href="login.php">🏠 Citizen Login</a>
                    <a href="officer/officer_login.php">👮 Officer Portal</a>
                    <a href="admin/admin_login.php">🛡️ Admin Console</a>
                </div>

                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <a href="#">📋 Guidelines</a>
                    <a href="#features">⚙️ Services</a>
                    <a href="register.php">✏️ Register</a>
                    <a href="#">❓ Help &amp; FAQ</a>
                </div>

                <div class="footer-links">
                    <h4>Important</h4>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Use</a>
                    <a href="#">Accessibility</a>
                    <a href="#">Contact Us</a>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="copyright">© 2026 CivicTrack — Citizen Services Portal. All rights reserved. | Government of India Initiative.</p>
                <div class="footer-badge">Best viewed in Chrome, Firefox, Edge · <span>♥</span> Built for citizens</div>
            </div>
        </div>
    </footer>

    <!-- ========== SCRIPTS ========== -->
    <script>
    /* ---- Navbar Scroll Effect ---- */
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 60) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    /* ---- Smooth Scroll ---- */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    /* ---- Scroll Reveal ---- */
    const revealEls = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, idx) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, idx * 70);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    revealEls.forEach(el => observer.observe(el));

    /* ---- Interactive Particles Animation ---- */
    const canvas = document.getElementById('particle-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        
        const setCanvasSize = () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        };
        setCanvasSize();

        let particles = [];
        const mouse = { x: null, y: null };

        window.addEventListener("mousemove", (e) => {
            const rect = canvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });

        window.addEventListener("mouseleave", () => {
            mouse.x = null;
            mouse.y = null;
        });

        for (let i = 0; i < 130; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * 0.6,
                vy: (Math.random() - 0.5) * 0.6
            });
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach((p, i) => {
                p.x += p.vx;
                p.y += p.vy;

                // Wrap around edges
                if (p.x < 0) p.x = canvas.width;
                if (p.x > canvas.width) p.x = 0;
                if (p.y < 0) p.y = canvas.height;
                if (p.y > canvas.height) p.y = 0;

                ctx.beginPath();
                ctx.arc(p.x, p.y, 2, 0, Math.PI * 2);
                ctx.fillStyle = "rgba(255, 255, 255, 0.8)"; // Bright white particles
                ctx.fill();

                for (let j = i; j < particles.length; j++) {
                    let dx = p.x - particles[j].x;
                    let dy = p.y - particles[j].y;
                    let dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 120) {
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.strokeStyle = `rgba(200, 146, 42, ${0.3 * (1 - dist/120)})`; // Brighter Gold lines
                        ctx.lineWidth = 0.8;
                        ctx.stroke();
                    }
                }

                if (mouse.x) {
                    let dx = p.x - mouse.x;
                    let dy = p.y - mouse.y;
                    let dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 220) {
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(mouse.x, mouse.y);
                        // Neon Green for cursor interaction - extremely visible
                        ctx.strokeStyle = `rgba(57, 255, 20, ${0.7 * (1 - dist/220)})`; 
                        ctx.lineWidth = 1.5;
                        ctx.stroke();
                    }
                }
            });
            requestAnimationFrame(animate);
        }

        animate();
        window.addEventListener('resize', () => {
            setCanvasSize();
            // Optional: redistribute particles on large resize
        });
    }
    </script>
</body>
</html>
