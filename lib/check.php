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