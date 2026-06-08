<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access.'));
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/system.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'service_status') {
    $service = isset($_POST['service']) ? $_POST['service'] : '';
    $status = ApplianceSystem::getServiceStatus($service);
    echo json_encode(array('success' => true, 'active' => $status['active'], 'output' => $status['output']));
    exit;
}

if ($action === 'validate_configs') {
    $status = ApplianceSystem::validateHaproxyConfig();
    echo json_encode(array('success' => $status['success'], 'output' => $status['output']));
    exit;
}

if ($action === 'test_connection') {
    $ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
    $port = isset($_POST['port']) ? intval($_POST['port']) : 0;
    
    if (empty($ip) || $port <= 0 || $port > 65535) {
        echo json_encode(array('success' => false, 'message' => 'Invalid IP or port.'));
        exit;
    }
    
    // Validate IP structure (supports IPv4/IPv6 or hostnames)
    if (!filter_var($ip, FILTER_VALIDATE_IP) && !filter_var($ip, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        echo json_encode(array('success' => false, 'message' => 'Invalid IP address or domain format.'));
        exit;
    }
    
    $start = microtime(true);
    $fp = @fsockopen($ip, $port, $errno, $errstr, 2);
    $end = microtime(true);
    
    if ($fp) {
        $duration = round(($end - $start) * 1000, 2);
        fclose($fp);
        echo json_encode(array(
            'success' => true,
            'message' => "Successfully connected to {$ip}:{$port} in {$duration} ms."
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'message' => "Connection failed to {$ip}:{$port} - Error: {$errstr} ({$errno})"
        ));
    }
    exit;
}

if ($action === 'get_live_metrics') {
    if (!ApplianceSystem::isSandbox()) {
        $metrics = ApplianceSystem::getLiveMetrics();
        if ($metrics !== null) {
            $logs = [];
            if (file_exists('/var/log/haproxy.log') && is_readable('/var/log/haproxy.log')) {
                $fileContent = @file('/var/log/haproxy.log');
                if (is_array($fileContent)) {
                    $logs = array_slice($fileContent, -10);
                }
            } else {
                $output = [];
                $code = 0;
                @exec('sudo tail -n 10 /var/log/haproxy.log 2>&1', $output, $code);
                if ($code === 0 && !empty($output) && strpos(implode('', $output), 'tail: ') === false && strpos(implode('', $output), 'permission') === false) {
                    $logs = $output;
                }
            }
            
            $formattedLogs = [];
            foreach ($logs as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $formattedLogs[] = htmlspecialchars($line);
            }
            
            echo json_encode([
                'success' => true,
                'sandbox' => false,
                'metrics' => $metrics,
                'logs' => $formattedLogs
            ]);
            exit;
        }
    }
    
    echo json_encode([
        'success' => true,
        'sandbox' => true,
        'metrics' => null,
        'logs' => []
    ]);
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid action.'));
?>
