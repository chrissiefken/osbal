<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';

// Enforce session check on all pages except login/install wizard
$currentPage = $_SERVER['SCRIPT_NAME'];
$isPublicPage = (strpos($currentPage, 'index.php') !== false || strpos($currentPage, 'install.php') !== false || strpos($currentPage, 'createUser.php') !== false);

if (!$isPublicPage) {
    checkAuth();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OSBal - Open Source Loadbalancer Stack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/css/modern.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar-custom">
            <a class="navbar-brand-custom" href="/reporting/index.php">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent);"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                OSBal
            </a>
            <?php if (!$isPublicPage || (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true)): ?>
            <ul class="nav-links">
                <li class="<?php echo (strpos($currentPage, 'reporting/index.php') !== false) ? 'active' : ''; ?>">
                    <a href="/reporting/index.php">Reporting</a>
                </li>
                <li class="<?php echo (strpos($currentPage, 'realtime.php') !== false) ? 'active' : ''; ?>">
                    <a href="/reporting/realtime.php">Realtime Stats</a>
                </li>
                <li class="<?php echo (strpos($currentPage, 'lb-settings') !== false) ? 'active' : ''; ?>">
                    <a href="/lb-settings/index.php">Load Balancer</a>
                </li>
                <li class="<?php echo (strpos($currentPage, 'users') !== false) ? 'active' : ''; ?>">
                    <a href="/users/index.php">Users</a>
                </li>
                <li class="dropdown-custom">
                    <a href="#">Advanced ▼</a>
                    <div class="dropdown-menu-custom">
                        <a href="/install.php">Install Wizard</a>
                        <a href="/logout.php">Sign Out</a>
                    </div>
                </li>
            </ul>
            <?php endif; ?>
        </nav>