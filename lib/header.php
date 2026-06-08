<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/publish.php';

// Enforce session check on all pages except login/install wizard
$currentPage = $_SERVER['SCRIPT_NAME'];
$isPublicPage = (strpos($currentPage, 'index.php') !== false || strpos($currentPage, 'install.php') !== false || strpos($currentPage, 'createUser.php') !== false);

if (!$isPublicPage) {
    checkAuth();
}

$publish_feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'publish_changes') {
    $publish_result = publishConfigs();
    if ($publish_result['success']) {
        $publish_feedback = '
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); text-align: center; padding: 14px; border-radius:12px; font-weight: 500; font-size: 0.95rem; margin-bottom: 24px;">
                ✓ ' . htmlspecialchars($publish_result['message']) . '
            </div>';
    }
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
                        <a href="/lb-settings/network.php">Management IP</a>
                        <a href="/install.php">Install Wizard</a>
                        <a href="/logout.php">Sign Out</a>
                    </div>
                </li>
            </ul>
            <?php endif; ?>
        </nav>
        <?php echo $publish_feedback; ?>
        <?php if (!$isPublicPage && hasPendingChanges()): ?>
            <div class="pending-banner" style="background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); color: #fff; padding: 14px 24px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.25); animation: pulse-shadow 2s infinite;">
                <div style="font-weight: 500; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    You have unpublished configuration changes.
                </div>
                <form method="POST" action="" style="margin:0;">
                    <input type="hidden" name="action" value="publish_changes">
                    <button type="submit" class="btn" style="background:#fff; color:#b45309; padding: 6px 16px; font-size:0.85rem; border-radius: 8px; font-weight:600; border:none; cursor:pointer;">Publish & Apply Changes</button>
                </form>
            </div>
            <style>
                @keyframes pulse-shadow {
                    0% { box-shadow: 0 4px 15px rgba(245, 158, 11, 0.25); }
                    50% { box-shadow: 0 4px 25px rgba(245, 158, 11, 0.5); }
                    100% { box-shadow: 0 4px 15px rgba(245, 158, 11, 0.25); }
                }
            </style>
        <?php endif; ?>