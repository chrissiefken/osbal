<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/system.php';

function getPendingChangesFile() {
    return config::getConfigDir() . 'pending_changes';
}

function setPendingChanges() {
    @file_put_contents(getPendingChangesFile(), "1");
}

function clearPendingChanges() {
    $file = getPendingChangesFile();
    if (file_exists($file)) {
        @unlink($file);
    }
}

function hasPendingChanges() {
    return file_exists(getPendingChangesFile());
}

function publishConfigs() {
    // 1. Compile HAProxy Config (with reload=true)
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
    compileHaproxyConfig(null, true);

    // 2. Compile Stunnel Config (with reload=true)
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ssl.php';
    compileStunnelConfig(null, true);

    // 3. Compile Keepalived Config (read from network settings, with reload=true)
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/network.php';
    $netSettings = getAdminSettings();
    if (!empty($netSettings['ip'])) {
        // Generate Keepalived config using virtual IP derived from subnet or config
        // In simple bare-metal setups, the VIP is what Keepalived manages.
        // Let's write the keepalived config using the saved management IP as VIP
        // or a dedicated VIP setting. For simplicity, we'll compile Keepalived config
        // using the adminSettings IP as the Virtual IP.
        writeKeepalivedConfig($netSettings['ip'], 'eth0', 'MASTER', 51, 101, true);
    }

    // 4. Clear pending changes flag
    clearPendingChanges();
    return array('success' => true, 'message' => 'All configurations compiled and services reloaded successfully.');
}
?>
