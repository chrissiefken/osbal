<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function getHaSettingsFile() {
    return config::getConfigDir() . config::haPartner;
}

function getHaSettings() {
    $file = getHaSettingsFile();
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data)) {
            // Ensure all fields are present
            $defaults = array(
                'enabled' => false,
                'role' => 'MASTER',
                'virtual_ip' => '',
                'interface' => 'eth0',
                'router_id' => 51,
                'auth_pass' => 'osbal_vrrp',
                'partner_ip' => '',
                'api_key' => ''
            );
            return array_merge($defaults, $data);
        }
    }
    return array(
        'enabled' => false,
        'role' => 'MASTER',
        'virtual_ip' => '',
        'interface' => 'eth0',
        'router_id' => 51,
        'auth_pass' => 'osbal_vrrp',
        'partner_ip' => '',
        'api_key' => ''
    );
}

function saveHaSettings($settings) {
    file_put_contents(getHaSettingsFile(), json_encode($settings, JSON_PRETTY_PRINT), LOCK_EX);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/publish.php';
    setPendingChanges();
}

function triggerHaSync() {
    $ha = getHaSettings();
    if (!$ha['enabled'] || empty($ha['partner_ip']) || empty($ha['api_key'])) {
        return array('success' => false, 'message' => 'HA is not active or partner configurations are missing.');
    }
    
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ssl.php';
    
    $payload = array(
        'services' => getServices(),
        'ssl' => getSslCertificates(),
        'blacklist' => getBlacklist(),
        'ha_settings' => $ha
    );
    
    $jsonData = json_encode($payload);
    $partnerUrl = 'http://' . $ha['partner_ip'] . '/api/config.php';
    
    $ch = curl_init($partnerUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-OSBAL-API-KEY: ' . $ha['api_key'],
        'Content-Length: ' . strlen($jsonData)
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 8); // 8 second timeout
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        return array('success' => false, 'message' => 'CURL Connection Error: ' . $err);
    }
    
    $resDecoded = json_decode($response, true);
    if ($httpCode === 200 && isset($resDecoded['success']) && $resDecoded['success'] === true) {
        return array('success' => true, 'message' => 'Partner node synchronized successfully.');
    } else {
        $msg = isset($resDecoded['message']) ? $resDecoded['message'] : 'HTTP Status ' . $httpCode;
        return array('success' => false, 'message' => 'Sync failed: ' . $msg);
    }
}
?>
