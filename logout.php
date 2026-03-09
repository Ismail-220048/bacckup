<?php
/**
 * CivicTrack — Logout
 */
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<script>
    // Clear notification session tracking so toasts start fresh on next login
    sessionStorage.removeItem('ct_shown_notif_ids');
    sessionStorage.removeItem('ct_notif_initialized');
    window.location.href = 'login.php?logout=1';
</script>
</body>
</html>

