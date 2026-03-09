<?php
/**
 * CivicTrack — Landing Page
 * Redirects to user/admin dashboard if logged in, otherwise to login.
 */
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin_dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

header('Location: login.php');
exit;
