<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function getOSecureSettingsFile() {
    return config::getConfigDir() . 'osecure.json';
}

function getOSecureSettings() {
    $file = getOSecureSettingsFile();
    $defaults = array(
        'enabled' => false,
        'license_key' => '',
        'server_url' => 'http://localhost:8000', // Default server URL for testing
        'sync_interval' => 60,
        'last_sync' => 'Never',
        'share_metrics' => true
    );

    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data)) {
            return array_merge($defaults, $data);
        }
    }
    return $defaults;
}

function saveOSecureSettings($settings) {
    $file = getOSecureSettingsFile();
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT), LOCK_EX);
}
?>
