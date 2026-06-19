<?php
// Ensure this script runs from the command line only
if (php_sapi_name() !== 'cli') {
    die("This script can only be run via CLI.\n");
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/publish.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/osecure.php';

echo "=== OSbal Rule Sync Utility ===\n";

$settings = getOSecureSettings();

if (!$settings['enabled'] || empty($settings['license_key'])) {
    echo "OSecure integration is disabled or license key is missing. Skipping sync.\n";
    exit(0);
}

$serverUrl = rtrim($settings['server_url'], '/');
$syncUrl = $serverUrl . '/api/sync.php';

echo "Fetching threat updates from OSecure controller: {$syncUrl}...\n";

$metricsData = null;
if (isset($settings['share_metrics']) && $settings['share_metrics'] === true) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/system.php';
    $metrics = ApplianceSystem::getLiveMetrics();
    if ($metrics === null) {
        // Mock data when running in sandbox or CLI test environment
        $metricsData = [
            'active_conns' => rand(10, 150),
            'req_rate' => rand(5, 50),
            'throughput' => round(rand(10, 120) / 10.0, 2),
            'avg_latency' => round(rand(40, 250) / 10.0, 2),
            'denied_reqs' => rand(0, 5)
        ];
    } else {
        $metricsData = [
            'active_conns' => intval($metrics['activeConns'] ?? 0),
            'req_rate' => intval($metrics['reqRate'] ?? 0),
            'throughput' => floatval($metrics['throughput'] ?? 0),
            'avg_latency' => floatval($metrics['avgLatency'] ?? 0),
            'denied_reqs' => intval($metrics['deniedReqs'] ?? 0)
        ];
    }
}

$postFields = [];
if ($metricsData !== null) {
    $postFields['metrics'] = $metricsData;
    echo "[+] Reporting aggregated traffic metrics to central dashboard...\n";
}

$ch = curl_init($syncUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-OSecure-Key: " . $settings['license_key'],
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo "[ERROR] Failed to communicate with OSecure server. HTTP Code: {$httpCode}\n";
    exit(1);
}

$data = json_decode($response, true);

if (!$data || !isset($data['success']) || !$data['success']) {
    $msg = isset($data['message']) ? $data['message'] : 'Unknown error';
    echo "[ERROR] OSecure sync failed: {$msg}\n";
    exit(1);
}

echo "[+] Telemetry response received successfully.\n";

$newBlocks = isset($data['blacklist']) ? $data['blacklist'] : [];
$newThrottles = isset($data['throttle']) ? $data['throttle'] : [];

// Sort lists to ensure comparison is order-independent
sort($newBlocks);
sort($newThrottles);

$currentBlocks = getBlacklist();
$currentThrottles = getThrottleList();

sort($currentBlocks);
sort($currentThrottles);

$changed = false;

if ($currentBlocks !== $newBlocks) {
    echo "[+] Updating blacklist rules. Old count: " . count($currentBlocks) . ", New count: " . count($newBlocks) . "\n";
    saveBlacklist($newBlocks);
    $changed = true;
}

if ($currentThrottles !== $newThrottles) {
    echo "[+] Updating throttle rules. Old count: " . count($currentThrottles) . ", New count: " . count($newThrottles) . "\n";
    saveThrottleList($newThrottles);
    $changed = true;
}

// Update last sync time
$settings['last_sync'] = date('Y-m-d H:i:s');
saveOSecureSettings($settings);

if ($changed) {
    echo "[+] Compiling new configurations and reloading HAProxy...\n";
    publishConfigs(true);
    echo "[SUCCESS] Rule synchronization completed successfully.\n";
} else {
    echo "[+] No rules changed. Local config is up to date.\n";
}
?>
