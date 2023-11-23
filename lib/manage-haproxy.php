<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function readHaproxyConfig() {
    $configPath = config::haproxyCfg;
    $configContents = file_get_contents($configPath);
    return $configContents;
}

function writeHaproxyConfig($newConfig) {
    $configPath = config::haproxyCfg;
    file_put_contents($configPath, $newConfig);
}

function updateHaproxyConfig($key, $value) {
    $config = readHaproxyConfig();
    // TODO: Update the $config with the new $key and $value
    writeHaproxyConfig($config);
}
?>