<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

class ApplianceSystem {
    
    public static function isSandbox() {
        // If the main HAProxy config in /etc/ is writable, we are running in production mode
        $haproxyPath = '/etc/haproxy/haproxy.cfg';
        if (file_exists($haproxyPath) && is_writable($haproxyPath)) {
            return false;
        }
        return config::getConfigDir() !== '/usr/local/osbal/config/';
    }

    private static function execute($command) {
        if (self::isSandbox()) {
            // Log locally to terminal logs or config log folder
            $logFile = config::getConfigDir() . 'system_events.log';
            $logMessage = "[" . date('Y-m-d H:i:s') . "] [SANDBOX BYPASS] Executed: " . $command . "\n";
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
            return array('success' => true, 'output' => 'Sandbox bypass success.');
        }

        // Run system command safely
        $output = array();
        $code = 0;
        exec($command . ' 2>&1', $output, $code);
        
        $logFile = config::getConfigDir() . 'system_events.log';
        $logMessage = "[" . date('Y-m-d H:i:s') . "] Executed: " . $command . " | Code: " . $code . " | Output: " . implode(" ", $output) . "\n";
        @file_put_contents($logFile, $logMessage, FILE_APPEND);

        return array(
            'success' => ($code === 0),
            'output' => implode("\n", $output),
            'code' => $code
        );
    }

    public static function reloadHaproxy() {
        return self::execute('sudo systemctl reload haproxy');
    }

    public static function reloadKeepalived() {
        return self::execute('sudo systemctl reload keepalived');
    }

    public static function restartStunnel() {
        // Stunnel4 does not natively support clean hot-reloads on some systems, restart is safer
        return self::execute('sudo systemctl restart stunnel4');
    }

    public static function getServiceStatus($service) {
        if (self::isSandbox()) {
            return array('active' => true, 'output' => 'active (sandbox mockup)');
        }
        $allowed = array('haproxy', 'keepalived', 'stunnel4', 'apache2');
        if (!in_array($service, $allowed)) {
            return array('active' => false, 'output' => 'Invalid service name.');
        }
        
        $output = array();
        $code = 0;
        exec('systemctl is-active ' . escapeshellarg($service) . ' 2>&1', $output, $code);
        $resText = trim(implode("\n", $output));
        
        return array(
            'active' => ($code === 0 || $resText === 'active'),
            'output' => $resText
        );
    }

    public static function validateHaproxyConfig() {
        $cfgPath = config::getHaproxyCfg();
        
        if (self::isSandbox()) {
            $haproxyBin = @shell_exec('which haproxy');
            if (empty($haproxyBin)) {
                return array('success' => true, 'output' => 'Configuration syntax valid (mocked sandbox check).');
            }
        }
        
        $output = array();
        $code = 0;
        exec('haproxy -c -f ' . escapeshellarg($cfgPath) . ' 2>&1', $output, $code);
        
        return array(
            'success' => ($code === 0),
            'output' => implode("\n", $output)
        );
    }

    public static function getHaproxyStats() {
        $url = 'http://127.0.0.1:9000/haproxy?stats;csv';
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 1.0,
            ]
        ]);
        $csvData = @file_get_contents($url, false, $ctx);
        if ($csvData === false) {
            return null;
        }
        $lines = explode("\n", trim($csvData));
        if (empty($lines)) {
            return null;
        }
        $headerLine = array_shift($lines);
        $headerLine = ltrim($headerLine, '# ');
        $headers = str_getcsv($headerLine);
        $stats = [];
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $row = str_getcsv($line);
            if (count($row) !== count($headers)) continue;
            $assocRow = array_combine($headers, $row);
            $stats[] = $assocRow;
        }
        return $stats;
    }

    public static function getLiveMetrics() {
        if (self::isSandbox()) {
            return null;
        }
        $stats = self::getHaproxyStats();
        if ($stats === null) {
            return null;
        }
        
        $activeConns = 0;
        $reqRate = 0;
        $totalBytes = 0;
        $deniedReqs = 0;
        
        foreach ($stats as $row) {
            if ($row['svname'] === 'FRONTEND') {
                $activeConns += intval($row['scur']);
                $reqRate += intval($row['rate']);
                $totalBytes += floatval($row['bin']) + floatval($row['bout']);
                $deniedReqs += intval($row['dreq']);
            }
        }
        
        $historyFile = config::getConfigDir() . 'metrics_history.json';
        $now = microtime(true);
        $throughput = 0.0;
        
        $history = null;
        if (file_exists($historyFile)) {
            $history = json_decode(@file_get_contents($historyFile), true);
        }
        
        if (is_array($history) && isset($history['timestamp']) && isset($history['bytes'])) {
            $timeDelta = $now - $history['timestamp'];
            if ($timeDelta > 0.2) {
                $bytesDelta = $totalBytes - $history['bytes'];
                if ($bytesDelta >= 0) {
                    $throughput = ($bytesDelta * 8) / (1024 * 1024 * $timeDelta);
                }
            } else {
                $throughput = isset($history['throughput']) ? floatval($history['throughput']) : 0.0;
            }
        }
        
        $newHistory = [
            'timestamp' => $now,
            'bytes' => $totalBytes,
            'throughput' => $throughput
        ];
        @file_put_contents($historyFile, json_encode($newHistory), LOCK_EX);
        
        // Rolling connections history (keep last 15 points)
        $connHistoryFile = config::getConfigDir() . 'connections_history.json';
        $connHistory = [];
        if (file_exists($connHistoryFile)) {
            $connHistory = json_decode(@file_get_contents($connHistoryFile), true);
        }
        if (!is_array($connHistory) || empty($connHistory)) {
            $connHistory = array_fill(0, 15, 0);
        }
        $connHistory[] = $activeConns;
        if (count($connHistory) > 15) {
            array_shift($connHistory);
        }
        @file_put_contents($connHistoryFile, json_encode($connHistory), LOCK_EX);
        
        // Latency
        $latencies = [];
        foreach ($stats as $row) {
            if ($row['svname'] === 'BACKEND' && isset($row['rtime']) && intval($row['rtime']) > 0) {
                $latencies[] = intval($row['rtime']);
            }
        }
        $avgLatency = count($latencies) > 0 ? (array_sum($latencies) / count($latencies)) : 0.0;
        if ($avgLatency == 0.0) {
            $latencies = [];
            foreach ($stats as $row) {
                if ($row['svname'] !== 'FRONTEND' && $row['svname'] !== 'BACKEND' && isset($row['rtime']) && intval($row['rtime']) > 0) {
                    $latencies[] = intval($row['rtime']);
                }
            }
            $avgLatency = count($latencies) > 0 ? (array_sum($latencies) / count($latencies)) : 0.0;
        }
        
        return [
            'active_connections' => $activeConns,
            'request_rate' => $reqRate,
            'throughput' => round($throughput, 2),
            'latency' => round($avgLatency, 2),
            'blocked_requests' => $deniedReqs
        ];
    }

    public static function checkForUpdates() {
        $cacheFile = config::getConfigDir() . 'update_info.json';
        $currentTime = time();
        $cacheDuration = 86400; // 24 hours
        
        if (file_exists($cacheFile)) {
            $data = json_decode(@file_get_contents($cacheFile), true);
            if (is_array($data) && isset($data['last_checked']) && ($currentTime - $data['last_checked']) < $cacheDuration) {
                return $data;
            }
        }
        
        $latestVersion = null;
        $updateAvailable = false;
        
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 2, // 2 seconds timeout to prevent blocking UI
                'header' => "User-Agent: OSBal-Appliance-Updater\r\n"
            ]
        ]);
        
        $remoteUrl = 'https://raw.githubusercontent.com/siefkencp/osbal/master/VERSION';
        $remoteVersion = @file_get_contents($remoteUrl, false, $ctx);
        
        if ($remoteVersion !== false) {
            $latestVersion = trim($remoteVersion);
            if (!empty($latestVersion) && version_compare($latestVersion, config::VERSION, '>')) {
                $updateAvailable = true;
            }
        } else {
            $latestVersion = isset($data['latest_version']) ? $data['latest_version'] : config::VERSION;
            $updateAvailable = isset($data['update_available']) ? $data['update_available'] : false;
        }
        
        $result = [
            'last_checked' => $currentTime,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'current_version' => config::VERSION
        ];
        
        @file_put_contents($cacheFile, json_encode($result), LOCK_EX);
        return $result;
    }

    public static function getApplianceCapacity() {
        $isSandbox = self::isSandbox();
        
        $cpuPct = 0;
        $connPct = 0;
        $bandwidthPct = 0;
        $bottleneck = 'None';
        
        if ($isSandbox) {
            // Mock sandbox values (deterministically fluctuate around low percentages)
            $cpuPct = 12 + (time() % 7);
            $connPct = 5 + (time() % 4);
            $bandwidthPct = 4 + (time() % 3);
        } else {
            // 1. CPU Capacity via load average
            $cores = 1;
            if (file_exists('/proc/cpuinfo')) {
                $cpuinfo = @file_get_contents('/proc/cpuinfo');
                if ($cpuinfo !== false) {
                    $cores = substr_count($cpuinfo, 'processor');
                }
            }
            if ($cores <= 0) $cores = 1;
            
            $load = sys_getloadavg();
            if (is_array($load) && isset($load[0])) {
                $cpuPct = round(($load[0] / $cores) * 100, 1);
            }
            if ($cpuPct > 100) $cpuPct = 100;
            
            // 2. Connection Capacity from HAProxy stats
            $stats = self::getHaproxyStats();
            if ($stats !== null) {
                $maxConn = 0;
                $curConn = 0;
                foreach ($stats as $row) {
                    if ($row['svname'] === 'FRONTEND') {
                        $curConn += intval($row['scur']);
                        $limit = intval($row['slim']);
                        $maxConn += ($limit > 0) ? $limit : 2000;
                    }
                }
                $connPct = ($maxConn > 0) ? round(($curConn / $maxConn) * 100, 1) : 0;
            }
            
            // 3. Bandwidth Capacity (throughput vs 1 Gbps link)
            $metrics = self::getLiveMetrics();
            if ($metrics !== null && isset($metrics['throughput'])) {
                $bandwidthPct = round(($metrics['throughput'] / 1000) * 100, 1);
            }
        }
        
        // Find highest utilization bottleneck
        $utilization = max($cpuPct, $connPct, $bandwidthPct);
        if ($utilization == $cpuPct) {
            $bottleneck = 'CPU Core Utilization';
        } elseif ($utilization == $connPct) {
            $bottleneck = 'Concurrent Connection Limit';
        } else {
            $bottleneck = 'Network Interface Bandwidth';
        }
        
        return [
            'utilization' => $utilization,
            'bottleneck' => $bottleneck,
            'details' => [
                'cpu' => $cpuPct,
                'connections' => $connPct,
                'bandwidth' => $bandwidthPct
            ]
        ];
    }
}
?>
