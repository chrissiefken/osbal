<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function checkStatus(){
    $status = array();
    
    // Modern software stack required for physical load balancers
    $required = array(
        'haproxy' => 'haproxy',
        'stunnel4' => 'stunnel',
        'keepalived' => 'keepalived',
        'apache2' => 'apache2',
        'php-cli' => 'php'
    );

    foreach ($required as $package => $binary) {
        $status[] = checkRequirement($package, $binary);
    }

    // check permissions
    $status[] = checkPermissions();

    // check config
    $status[] = checkConfig();

    return $status;
}

function checkRequirement($package, $binary) {
    // Try dpkg first (for Debian/Ubuntu/Raspbian)
    $result = @shell_exec('dpkg -s ' . escapeshellarg($package) . ' 2>&1');
    if ($result && preg_match('/Status: install ok installed/', $result)) {
        return array('message' => $package . ' installed (system package)', 'error' => false, 'type' => 'package');
    }

    // Fallback: check if the binary exists in PATH
    $whichResult = @shell_exec('which ' . escapeshellarg($binary) . ' 2>&1');
    if ($whichResult && !empty(trim($whichResult))) {
        return array('message' => $package . ' available in PATH', 'error' => false, 'type' => 'package');
    }

    return array('message' => $package . ' NOT installed', 'error' => true, 'type' => 'package');
}

function checkPermissions() {
    $haproxyPath = '/etc/haproxy/haproxy.cfg';
    $keepalivedPath = '/etc/keepalived/keepalived.conf';
    $stunnelPath = '/etc/stunnel/stunnel.conf';
    $certsDir = '/etc/stunnel/certs';
    $configDir = '/usr/local/osbal/config';

    $haproxyWritable = file_exists($haproxyPath) && is_writable($haproxyPath);
    $keepalivedWritable = file_exists($keepalivedPath) && is_writable($keepalivedPath);
    $stunnelWritable = file_exists($stunnelPath) && is_writable($stunnelPath);
    $certsWritable = is_dir($certsDir) && is_writable($certsDir);
    $configDirWritable = is_dir($configDir) && is_writable($configDir);

    if ($haproxyWritable && $keepalivedWritable && $stunnelWritable && $certsWritable && $configDirWritable) {
        return array('message' => 'System configuration permissions', 'error' => false, 'type' => 'permissions');
    } else {
        $missing = array();
        if (!$haproxyWritable) $missing[] = 'HAProxy';
        if (!$keepalivedWritable) $missing[] = 'Keepalived';
        if (!$stunnelWritable) $missing[] = 'Stunnel';
        if (!$certsWritable) $missing[] = 'Stunnel Certs';
        if (!$configDirWritable) $missing[] = 'OSBal Config Dir';

        return array(
            'message' => 'Production write access missing (' . implode(', ', $missing) . ')',
            'error' => true,
            'type' => 'permissions'
        );
    }
}

function checkConfig(){
    $configDir = config::getConfigDir();
    $adminFile = $configDir . config::adminIpSettings;
    
    if (file_exists($adminFile)) {
        return array('message' => 'OSBal Configured.', 'error' => false, 'type' => 'config');
    } else {
        return array('message' => 'Configuration is not initialized yet.', 'error' => true, 'type' => 'config');
    }
}
?>