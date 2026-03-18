<?php

$files = [
    'user/my_complaints.php',
    'user/dashboard.php',
    'user/submit_complaint.php',
    'user/profile.php',
    'admin/heatmap.php',
    'admin/manage_users.php',
    'admin/manage_officers.php',
    'admin/admin_dashboard.php',
    'admin/manage_complaints.php',
    'officer/my_assignments.php',
    'officer/officer_dashboard.php',
    'officer/profile.php'
];

$headerLeftHTML = <<<HTML
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
                    <div class="header-logo-group">
                        <img src="../assets/images/govt_emblem.png" alt="Emblem" style="height: 35px; width: auto; filter: drop-shadow(0 0 4px rgba(200,146,42,0.3));">
                        <span>CivicTrack</span>
                    </div>
HTML;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // First, remove old standalone sidebar-toggle
    $content = preg_replace('/<button class="sidebar-toggle"[^>]*>☰<\/button>/i', '', $content);
    
    // If it has header-left-target (from my previous run)
    if (strpos($content, '<div class="header-left-target">') !== false) {
        $content = str_replace('<div class="header-left-target">', $headerLeftHTML, $content);
    } else {
        // If it doesn't, it usually looks like:
        // <div class="page-header">
        //     <h1>...</h1>
        // OR <div class="page-header">\n    <h1...
        $content = preg_replace('/(<div class="page-header">\s*)(<h1[^>]*>)/i', '$1' . $headerLeftHTML . "\n                    $2", $content);
        
        // Also we need to close the <div class="header-left"> after the h1.
        // It looks like <h1...>Text</h1>
        $content = preg_replace('/(<h1[^>]*>.*?<\/h1>)/is', "$1\n                </div>", $content);
    }
    
    file_put_contents($file, $content);
}
echo "Done fixing headers.\n";
