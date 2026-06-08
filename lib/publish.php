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

function publishConfigs($triggerSync = true) {
    // 1. Compile HAProxy Config (with reload=true)
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
    compileHaproxyConfig(null, true);

    // 2. Compile Stunnel Config (with reload=true)
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ssl.php';
    compileStunnelConfig(null, true);

    // 3. Compile Keepalived Config
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/network.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ha.php';
    $ha = getHaSettings();
    
    if ($ha['enabled'] && !empty($ha['virtual_ip'])) {
        $role = $ha['role']; // MASTER or BACKUP
        $priority = ($role === 'MASTER') ? 101 : 100;
        writeKeepalivedConfig($ha['virtual_ip'], $ha['interface'], $role, $ha['router_id'], $priority, true);
    } else {
        // Fallback to standalone Keepalived if management IP exists
        $netSettings = getAdminSettings();
        if (!empty($netSettings['ip'])) {
            writeKeepalivedConfig($netSettings['ip'], 'eth0', 'MASTER', 51, 101, true);
        }
    }

    // 4. Clear pending changes flag
    clearPendingChanges();

    // 5. Trigger automatic replication to partner node
    $syncMsg = '';
    if ($triggerSync && $ha['enabled'] && !empty($ha['partner_ip'])) {
        $syncRes = triggerHaSync();
        if ($syncRes['success']) {
            $syncMsg = ' Partner node successfully synchronized.';
        } else {
            $syncMsg = ' (Warning: Sync to partner node failed: ' . $syncRes['message'] . ')';
        }
    }

    return array('success' => true, 'message' => 'All configurations compiled and services reloaded successfully.' . $syncMsg);
}
?>
