<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ha.php';
$ha = getHaSettings();

$authenticated = false;

// 1. Session auth for local UI users
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $authenticated = true;
} else {
    // 2. API Key header auth for node-to-node communication
    $headers = apache_request_headers();
    $providedKey = '';
    
    if (isset($headers['X-OSBAL-API-KEY'])) {
        $providedKey = $headers['X-OSBAL-API-KEY'];
    } elseif (isset($headers['x-osbal-api-key'])) {
        $providedKey = $headers['x-osbal-api-key'];
    } elseif (isset($_SERVER['HTTP_X_OSBAL_API_KEY'])) {
        $providedKey = $_SERVER['HTTP_X_OSBAL_API_KEY'];
    }
    
    if ($ha['enabled'] && !empty($ha['api_key']) && !empty($providedKey) && hash_equals($ha['api_key'], $providedKey)) {
        $authenticated = true;
    }
}

if (!$authenticated) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access.'));
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ssl.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Export full configuration payload
    $payload = array(
        'services' => getServices(),
        'ssl' => getSslCertificates(),
        'blacklist' => getBlacklist(),
        'ha_settings' => $ha
    );
    echo json_encode(array('success' => true, 'config' => $payload));
    exit;
}

if ($method === 'POST') {
    // Import configuration payload
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !is_array($data)) {
        echo json_encode(array('success' => false, 'message' => 'Invalid configuration payload.'));
        exit;
    }
    
    // Save Services
    if (isset($data['services'])) {
        file_put_contents(getServicesFile(), json_encode($data['services'], JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    // Save SSL Certificates
    if (isset($data['ssl'])) {
        file_put_contents(getSslFile(), json_encode($data['ssl'], JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    // Save WAF Blacklist
    if (isset($data['blacklist'])) {
        file_put_contents(getBlacklistFile(), implode("\n", array_filter(array_map('trim', $data['blacklist']))) . "\n", LOCK_EX);
    }
    
    // Save HA Settings
    if (isset($data['ha_settings'])) {
        $incomingHa = $data['ha_settings'];
        // Swap incoming MASTER role to local BACKUP role
        if ($incomingHa['role'] === 'MASTER') {
            $ha['role'] = 'BACKUP';
        } else {
            $ha['role'] = 'MASTER';
        }
        $ha['enabled'] = $incomingHa['enabled'];
        $ha['virtual_ip'] = $incomingHa['virtual_ip'];
        $ha['interface'] = $incomingHa['interface'];
        $ha['router_id'] = $incomingHa['router_id'];
        $ha['auth_pass'] = $incomingHa['auth_pass'];
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ha['partner_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        $ha['api_key'] = $incomingHa['api_key'];
        
        file_put_contents(getHaSettingsFile(), json_encode($ha, JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    // Trigger compilation and reloads
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/publish.php';
    $res = publishConfigs(false); // Pass false to prevent looping sync triggers
    
    if ($res['success']) {
        echo json_encode(array('success' => true, 'message' => 'Configuration synchronized and reloaded successfully.'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Config saved locally, but reload failed: ' . $res['message']));
    }
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Unsupported method.'));
?>
