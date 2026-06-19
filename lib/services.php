<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

function getServicesFile() {
    return config::getConfigDir() . config::lbServices;
}

function getBlacklistFile() {
    $file = config::getConfigDir() . 'blacklist.lst';
    if (!file_exists($file)) {
        @file_put_contents($file, "");
    }
    return $file;
}

function getBlacklist() {
    $file = getBlacklistFile();
    $content = file_get_contents($file);
    return array_filter(array_map('trim', explode("\n", $content)));
}

function saveBlacklist($ips) {
    $file = getBlacklistFile();
    file_put_contents($file, implode("\n", array_filter(array_map('trim', $ips))) . "\n", LOCK_EX);
    compileHaproxyConfig();
}

function getThrottleListFile() {
    $file = config::getConfigDir() . 'throttle.lst';
    if (!file_exists($file)) {
        @file_put_contents($file, "");
    }
    return $file;
}

function getThrottleList() {
    $file = getThrottleListFile();
    $content = file_get_contents($file);
    return array_filter(array_map('trim', explode("\n", $content)));
}

function saveThrottleList($ips) {
    $file = getThrottleListFile();
    file_put_contents($file, implode("\n", array_filter(array_map('trim', $ips))) . "\n", LOCK_EX);
    compileHaproxyConfig();
}

function getServices() {
    $file = getServicesFile();
    if (!file_exists($file)) {
        return array();
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : array();
}

function saveServices($services) {
    file_put_contents(getServicesFile(), json_encode($services, JSON_PRETTY_PRINT), LOCK_EX);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/publish.php';
    setPendingChanges();
}

function createService($name, $ip, $port, $mode = 'http', $balance = 'roundrobin', $waf_enabled = false, $block_sqli = false, $block_xss = false, $rate_limit = false, $ssl_enabled = false, $ssl_port = 443, $ssl_cert_name = '', $rate_limit_type = 'deny', $rate_limit_max = 100, $rate_limit_delay = 5) {
    $services = getServices();
    $id = uniqid();
    $services[$id] = array(
        'id' => $id,
        'name' => $name,
        'ip' => $ip,
        'port' => $port,
        'mode' => $mode,
        'balance' => $balance,
        'waf_enabled' => (bool)$waf_enabled,
        'block_sqli' => (bool)$block_sqli,
        'block_xss' => (bool)$block_xss,
        'rate_limit' => (bool)$rate_limit,
        'rate_limit_type' => $rate_limit_type,
        'rate_limit_max' => intval($rate_limit_max),
        'rate_limit_delay' => intval($rate_limit_delay),
        'ssl_enabled' => (bool)$ssl_enabled,
        'ssl_port' => intval($ssl_port),
        'ssl_cert_name' => $ssl_cert_name,
        'servers' => array()
    );
    saveServices($services);
    return $id;
}

function updateService($id, $name, $ip, $port, $mode, $balance, $waf_enabled = false, $block_sqli = false, $block_xss = false, $rate_limit = false, $ssl_enabled = null, $ssl_port = null, $ssl_cert_name = null, $rate_limit_type = null, $rate_limit_max = null, $rate_limit_delay = null) {
    $services = getServices();
    if (isset($services[$id])) {
        $services[$id]['name'] = $name;
        $services[$id]['ip'] = $ip;
        $services[$id]['port'] = $port;
        $services[$id]['mode'] = $mode;
        $services[$id]['balance'] = $balance;
        $services[$id]['waf_enabled'] = (bool)$waf_enabled;
        $services[$id]['block_sqli'] = (bool)$block_sqli;
        $services[$id]['block_xss'] = (bool)$block_xss;
        $services[$id]['rate_limit'] = (bool)$rate_limit;
        
        if ($rate_limit_type !== null) {
            $services[$id]['rate_limit_type'] = $rate_limit_type;
        } elseif (!isset($services[$id]['rate_limit_type'])) {
            $services[$id]['rate_limit_type'] = 'deny';
        }
        
        if ($rate_limit_max !== null) {
            $services[$id]['rate_limit_max'] = intval($rate_limit_max);
        } elseif (!isset($services[$id]['rate_limit_max'])) {
            $services[$id]['rate_limit_max'] = 100;
        }
        
        if ($rate_limit_delay !== null) {
            $services[$id]['rate_limit_delay'] = intval($rate_limit_delay);
        } elseif (!isset($services[$id]['rate_limit_delay'])) {
            $services[$id]['rate_limit_delay'] = 5;
        }
        
        if ($ssl_enabled !== null) {
            $services[$id]['ssl_enabled'] = (bool)$ssl_enabled;
        }
        if ($ssl_port !== null) {
            $services[$id]['ssl_port'] = intval($ssl_port);
        }
        if ($ssl_cert_name !== null) {
            $services[$id]['ssl_cert_name'] = $ssl_cert_name;
        }
        
        saveServices($services);
        return true;
    }
    return false;
}

function deleteService($id) {
    $services = getServices();
    if (isset($services[$id])) {
        unset($services[$id]);
        saveServices($services);
        return true;
    }
    return false;
}

function addServerToService($serviceId, $serverName, $serverIp, $serverPort, $weight = 1, $check = true) {
    $services = getServices();
    if (isset($services[$serviceId])) {
        $serverId = uniqid();
        $services[$serviceId]['servers'][$serverId] = array(
            'id' => $serverId,
            'name' => $serverName,
            'ip' => $serverIp,
            'port' => $serverPort,
            'weight' => $weight,
            'check' => $check
        );
        saveServices($services);
        return $serverId;
    }
    return false;
}

function removeServerFromService($serviceId, $serverId) {
    $services = getServices();
    if (isset($services[$serviceId]) && isset($services[$serviceId]['servers'][$serverId])) {
        unset($services[$serviceId]['servers'][$serverId]);
        saveServices($services);
        return true;
    }
    return false;
}

function compileHaproxyConfig($services = null, $reload = false) {
    if ($services === null) {
        $services = getServices();
    }

    $cfg = "# Generated by OSBal Loadbalancer Manager\n\n";
    
    // Global Section
    $cfg .= "global\n";
    $cfg .= "    log /dev/log local0\n";
    $cfg .= "    log /dev/log local1 notice\n";
    $cfg .= "    chroot /var/lib/haproxy\n";
    $cfg .= "    user haproxy\n";
    $cfg .= "    group haproxy\n";
    $cfg .= "    daemon\n\n";

    // Defaults Section
    $cfg .= "defaults\n";
    $cfg .= "    log     global\n";
    $cfg .= "    mode    http\n";
    $cfg .= "    option  httplog\n";
    $cfg .= "    option  dontlognull\n";
    $cfg .= "    timeout connect 5000\n";
    $cfg .= "    timeout client  50000\n";
    $cfg .= "    timeout server  50000\n\n";

    // HAProxy local stats endpoint for metrics collection (bound strictly to loopback interface for security)
    $cfg .= "listen stats\n";
    $cfg .= "    bind 127.0.0.1:9000\n";
    $cfg .= "    mode http\n";
    $cfg .= "    stats enable\n";
    $cfg .= "    stats uri /haproxy?stats\n\n";

    // Compile each service
    foreach ($services as $id => $service) {
        $frontName = "frontend_" . $id;
        $backName = "backend_" . $id;
        
        $bindIp = empty($service['ip']) ? '*' : $service['ip'];
        $bindPort = empty($service['port']) ? '80' : $service['port'];
        
        $waf = isset($service['waf_enabled']) && $service['waf_enabled'];
        
        // Frontend configuration
        $cfg .= "frontend " . $frontName . "\n";
        $cfg .= "    bind " . $bindIp . ":" . $bindPort . "\n";
        $cfg .= "    mode " . ($service['mode'] === 'tcp' ? 'tcp' : 'http') . "\n";
        
        // Apply WAF rules if enabled and mode is HTTP
        if ($waf && $service['mode'] !== 'tcp') {
            $blacklistPath = getBlacklistFile();
            $throttlePath = getThrottleListFile();
            $cfg .= "    # WAF Rules\n";
            $cfg .= "    acl is_blacklisted src -f " . $blacklistPath . "\n";
            $cfg .= "    http-request deny deny_status 403 if is_blacklisted\n";
            $cfg .= "    acl is_throttled src -f " . $throttlePath . "\n";
            $cfg .= "    http-request tarpit delay 5s if is_throttled\n";
            
            $waf_type = isset($service['rate_limit_type']) ? $service['rate_limit_type'] : 'deny';
            $waf_delay = isset($service['rate_limit_delay']) ? intval($service['rate_limit_delay']) : 5;
            
            if (isset($service['block_sqli']) && $service['block_sqli']) {
                $cfg .= "    acl is_sqli query -m reg -i (select|insert|update|delete|drop|union)\n";
                if ($waf_type === 'tarpit') {
                    $cfg .= "    http-request tarpit delay " . $waf_delay . "s if is_sqli\n";
                } else {
                    $cfg .= "    http-request deny deny_status 403 if is_sqli\n";
                }
            }
            if (isset($service['block_xss']) && $service['block_xss']) {
                $cfg .= "    acl is_xss query -m reg -i (<script|javascript:|onerror|onload|alert\\()\n";
                if ($waf_type === 'tarpit') {
                    $cfg .= "    http-request tarpit delay " . $waf_delay . "s if is_xss\n";
                } else {
                    $cfg .= "    http-request deny deny_status 403 if is_xss\n";
                }
            }
        }
        
        $cfg .= "    default_backend " . $backName . "\n\n";
        
        // Backend configuration
        $cfg .= "backend " . $backName . "\n";
        $cfg .= "    mode " . ($service['mode'] === 'tcp' ? 'tcp' : 'http') . "\n";
        
        // Balancing Strategy
        $strategy = $service['balance'];
        if ($strategy === 'cookie' && $service['mode'] !== 'tcp') {
            $cfg .= "    balance roundrobin\n";
            $cfg .= "    cookie SERVERID insert indirect nocache\n";
        } elseif ($strategy === 'ip') {
            $cfg .= "    balance source\n";
        } else {
            // Default to roundrobin
            $cfg .= "    balance roundrobin\n";
        }
        
        // Backend Servers
        foreach ($service['servers'] as $srvId => $srv) {
            $srvLine = "    server " . escapeshellcmd($srv['name']) . " " . escapeshellcmd($srv['ip']) . ":" . intval($srv['port']);
            
            if ($strategy === 'cookie' && $service['mode'] !== 'tcp') {
                $srvLine .= " cookie " . escapeshellcmd($srv['name']);
            }
            
            if ($srv['weight'] > 1) {
                $srvLine .= " weight " . intval($srv['weight']);
            }
            
            if ($srv['check']) {
                $srvLine .= " check";
            }
            
            $cfg .= $srvLine . "\n";
        }
        $cfg .= "\n";
    }

    $haproxyCfgPath = config::getHaproxyCfg();
    file_put_contents($haproxyCfgPath, $cfg, LOCK_EX);

    if ($reload) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/system.php';
        ApplianceSystem::reloadHaproxy();
    }
}
?>