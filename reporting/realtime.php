<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/system.php';

$services = getServices();
$service_names = array();
$server_pools = array();

foreach ($services as $id => $service) {
    $service_names[$id] = $service['name'];
    $server_pools[$id] = array();
    foreach ($service['servers'] as $srv) {
        $server_pools[$id][] = $srv['name'] . ' (' . $srv['ip'] . ':' . $srv['port'] . ')';
    }
}

// Convert arrays to JSON for Javascript use
$services_json = json_encode($service_names);
$pools_json = json_encode($server_pools);

$isSandbox = ApplianceSystem::isSandbox();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px; flex-wrap:wrap; gap: 12px;">
    <div>
        <h1 style="margin-bottom:6px;">Real-Time Traffic Monitor</h1>
        <p style="margin-bottom:0;">Live metrics feed, latency charts, and access logs from the HAProxy load balancer.</p>
    </div>
    <?php if ($isSandbox): ?>
    <div class="card-glass" style="padding: 10px 18px; margin-bottom:0; display:flex; align-items:center; gap: 10px;">
        <span style="font-size:0.8rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Simulator Load:</span>
        <div style="display:inline-flex; gap: 6px;">
            <button class="btn btn-secondary sim-load-btn" data-load="low" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;">Low</button>
            <button class="btn btn-secondary sim-load-btn active-load" data-load="med" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; background:var(--primary); color:#fff;">Medium</button>
            <button class="btn btn-secondary sim-load-btn" data-load="high" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;">High</button>
            <button class="btn btn-secondary sim-load-btn" data-load="ddos" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; border-color:var(--danger); color:var(--danger);">DDoS</button>
        </div>
    </div>
    <?php else: ?>
    <div class="card-glass" style="padding: 10px 18px; margin-bottom:0; display:flex; align-items:center; gap: 10px; border-color: rgba(16, 185, 129, 0.25);">
        <span class="badge badge-success" style="animation: pulse 2s infinite;">Live Engine Active</span>
        <style>@keyframes pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }</style>
    </div>
    <?php endif; ?>
</div>

<!-- KPI Summary Widgets -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">Active Connections</div>
        <div style="font-size: 2.2rem; font-weight: 700; color: #fff;" id="kpi-connections">242</div>
    </div>
    
    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">Request Rate</div>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--accent);" id="kpi-rps">32 <span style="font-size: 1.1rem; font-weight:500; color: var(--text-muted);">req/s</span></div>
    </div>

    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">Avg Response Latency</div>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--success);" id="kpi-latency">4.2 <span style="font-size: 1.1rem; font-weight:500; color: var(--text-muted);">ms</span></div>
    </div>

    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">Blocked Requests (WAF)</div>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--danger);" id="kpi-blocked">0</div>
    </div>
</div>

<style>
    .active-load {
        box-shadow: 0 0 12px var(--primary-glow) !important;
    }
</style>

<!-- Live Chart Section -->
<div class="card-glass" style="padding: 25px;">
    <h3>Real-Time Latency & Throughput (1.5s Intervals)</h3>
    <div style="background: rgba(0,0,0,0.2); border-radius:12px; padding:15px; border:1px solid var(--border-color); position:relative;">
        <svg class="chart-svg" id="live-chart" viewBox="0 0 1000 180" preserveAspectRatio="none">
            <defs>
                <linearGradient id="live-gradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--accent)" stop-opacity="0.4"/>
                    <stop offset="100%" stop-color="var(--primary)" stop-opacity="0"/>
                </linearGradient>
            </defs>
            <!-- Grid Lines -->
            <line class="chart-grid" x1="0" y1="30" x2="1000" y2="30"/>
            <line class="chart-grid" x1="0" y1="75" x2="1000" y2="75"/>
            <line class="chart-grid" x1="0" y1="120" x2="1000" y2="120"/>
            <line class="chart-grid" x1="0" y1="165" x2="1000" y2="165"/>
            
            <polygon class="chart-area" id="chart-polygon" points="0,180 1000,180"/>
            <polyline class="chart-line" id="chart-polyline" points="0,180"/>
        </svg>
    </div>
</div>

<!-- Console Access Logs Terminal -->
<div class="card-glass" style="padding: 25px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
        <h3 style="margin-bottom:0;">Live Appliance Requests & Firewall Log</h3>
        <button id="clear-console-btn" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 6px;">Clear Terminal</button>
    </div>
    
    <div id="terminal" style="background: #060913; border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; color: #10b981; height: 320px; overflow-y: scroll; display: flex; flex-direction: column; gap: 6px; box-shadow: inset 0 2px 10px rgba(0,0,0,0.8);">
        <div>[SYSTEM] OSBal Live Terminal Connection Established. Listening on local syslog interfaces...</div>
    </div>
</div>

<script>
$(function() {
    var services = <?php echo $services_json; ?>;
    var pools = <?php echo $pools_json; ?>;
    var currentLoad = 'med'; // 'low', 'med', 'high', 'ddos'
    var isSandbox = <?php echo $isSandbox ? 'true' : 'false'; ?>;
    
    // Stats variables
    var activeConnections = 240;
    var requestRate = 32;
    var latency = 4.2;
    var totalBlocked = 0;
    
    // Live chart data points (size 25)
    var chartPoints = Array(25).fill(0);
    var chartHeight = 180;
    var chartWidth = 1000;

    // Traffic Simulator control buttons
    $('.sim-load-btn').click(function() {
        $('.sim-load-btn').removeClass('active-load').css('background', '').css('color', '');
        $(this).addClass('active-load');
        currentLoad = $(this).data('load');
        
        if (currentLoad === 'low') {
            $(this).css('background', 'var(--primary)').css('color', '#fff');
            logTerminal('[SIMULATION] Load state shifted to LOW. Traffic throttled.', 'system');
        } else if (currentLoad === 'med') {
            $(this).css('background', 'var(--primary)').css('color', '#fff');
            logTerminal('[SIMULATION] Load state shifted to MEDIUM. Standard operational flow.', 'system');
        } else if (currentLoad === 'high') {
            $(this).css('background', 'var(--primary)').css('color', '#fff');
            logTerminal('[SIMULATION] Load state shifted to HIGH. Heavy load simulation active.', 'system');
        } else if (currentLoad === 'ddos') {
            $(this).css('background', 'var(--danger)').css('color', '#fff');
            logTerminal('[WAF ALERT] DDoS ATTACK PATTERN SIMULATED. Web Application Firewall monitoring active.', 'alert');
        }
    });

    $('#clear-console-btn').click(function() {
        $('#terminal').empty();
    });

    function runSimulatorData() {
        var multiplier = 1;
        
        if (currentLoad === 'low') {
            activeConnections = Math.floor(40 + Math.random() * 20);
            requestRate = Math.floor(5 + Math.random() * 5);
            latency = (2.1 + Math.random() * 1.5).toFixed(2);
            multiplier = 0.2;
        } else if (currentLoad === 'med') {
            activeConnections = Math.floor(180 + Math.random() * 80);
            requestRate = Math.floor(25 + Math.random() * 15);
            latency = (3.8 + Math.random() * 2.1).toFixed(2);
            multiplier = 0.8;
        } else if (currentLoad === 'high') {
            activeConnections = Math.floor(650 + Math.random() * 250);
            requestRate = Math.floor(120 + Math.random() * 50);
            latency = (9.5 + Math.random() * 8.2).toFixed(2);
            multiplier = 2.5;
        } else if (currentLoad === 'ddos') {
            activeConnections = Math.floor(2500 + Math.random() * 1200);
            requestRate = Math.floor(1800 + Math.random() * 600);
            latency = (42.1 + Math.random() * 38.5).toFixed(2);
            multiplier = 15;
            var blocks = Math.floor(15 + Math.random() * 35);
            totalBlocked += blocks;
            $('#kpi-blocked').text(totalBlocked).css('color', 'var(--danger)');
        }

        $('#kpi-connections').text(activeConnections);
        $('#kpi-rps').html(requestRate + ' <span style="font-size: 1.1rem; font-weight:500; color: var(--text-muted);">req/s</span>');
        $('#kpi-latency').html(latency + ' <span style="font-size: 1.1rem; font-weight:500; color: var(--text-muted);">ms</span>');

        var plotVal = requestRate;
        if (currentLoad === 'ddos') plotVal = 250 + Math.random() * 50;
        chartPoints.push(plotVal);
        chartPoints.shift();
        drawChart();

        generateMockLogs(multiplier);
    }

    // Loop logic to update charts and append logs
    function updateMetrics() {
        if (isSandbox) {
            runSimulatorData();
            return;
        }

        // Production query
        $.ajax({
            type: "POST",
            url: "/api/diagnostics.php",
            data: { action: 'get_live_metrics' }
        }).done(function(res) {
            if (res.success && !res.sandbox) {
                activeConnections = res.metrics.active_connections;
                requestRate = res.metrics.request_rate;
                latency = res.metrics.latency;
                var blocked = res.metrics.blocked_requests;
                
                // Update widgets
                $('#kpi-connections').text(activeConnections);
                $('#kpi-rps').html(requestRate + ' <span style="font-size: 1.1rem; font-weight:500; color: var(--text-muted);">req/s</span>');
                $('#kpi-latency').html(latency + ' <span style="font-size: 1.1rem; font-weight:500; color: var(--text-muted);">ms</span>');
                $('#kpi-blocked').text(blocked).css('color', blocked > 0 ? 'var(--danger)' : 'var(--text-muted)');

                // Chart shift
                chartPoints.push(requestRate);
                chartPoints.shift();
                drawChart();

                // Append logs
                if (res.logs && res.logs.length > 0) {
                    if (!window.recentLogs) window.recentLogs = [];
                    res.logs.forEach(function(line) {
                        if (window.recentLogs.indexOf(line) === -1) {
                            window.recentLogs.push(line);
                            if (window.recentLogs.length > 50) window.recentLogs.shift();
                            
                            var lineHtml = "";
                            if (line.indexOf('BLOCKED') !== -1 || line.indexOf('ALERT') !== -1 || line.indexOf(' 403 ') !== -1 || line.indexOf(' 429 ') !== -1) {
                                lineHtml = '<div style="color:var(--danger);">' + line + '</div>';
                            } else {
                                lineHtml = '<div style="color:var(--text-muted);">' + line + '</div>';
                            }
                            logTerminal(lineHtml);
                        }
                    });
                }
            } else {
                // HAProxy down or statistics unavailable - fallback to simulator
                runSimulatorData();
                
                if (!window.errLogged) {
                    logTerminal('<div style="color:var(--warning);">[WARNING] Unable to read HAProxy statistics. Defaulting to sandbox simulator mockups. Please publish configurations to enable live metrics collection.</div>');
                    window.errLogged = true;
                }
            }
        }).fail(function() {
            // Fallback to simulator
            runSimulatorData();
        });
    }

    function drawChart() {
        var maxVal = Math.max(...chartPoints, 50);
        var svgCoords = "";
        var areaCoords = "0," + chartHeight + " ";
        
        for (var i = 0; i < chartPoints.length; i++) {
            var x = (i / (chartPoints.length - 1)) * chartWidth;
            var y = chartHeight - ((chartPoints[i] / maxVal) * (chartHeight - 30)) - 10;
            svgCoords += x + "," + y + " ";
            areaCoords += x + "," + y + " ";
        }
        areaCoords += chartWidth + "," + chartHeight;

        $('#chart-polyline').attr('points', svgCoords);
        $('#chart-polygon').attr('points', areaCoords);
    }

    function generateMockLogs(multiplier) {
        var loopCount = Math.floor(1 + Math.random() * 3 * multiplier);
        if (loopCount > 10) loopCount = 10; // clamp log overflow in DOM

        var methods = ['GET', 'POST', 'GET', 'GET', 'PUT'];
        var paths = ['/index.php', '/api/v1/status', '/lb-settings/index.php', '/reporting/index.php', '/css/modern.css', '/js/bootstrap.min.js'];
        var ips = ['192.168.1.45', '192.168.1.62', '10.0.0.12', '10.0.0.88', '192.168.1.102', '172.16.5.14'];
        
        // WAF triggers
        var maliciousQueries = [
            '?id=1%20UNION%20SELECT%20username,%20password%20FROM%20users',
            '?query=SELECT%20*%20FROM%20users%20WHERE%201=1',
            '?payload=%3Cscript%3Ealert(1)%3C/script%3E',
            '?url=javascript:alert(document.cookie)',
            '?user=admin%20OR%201=1'
        ];

        for (var i = 0; i < loopCount; i++) {
            var timestamp = new Date().toISOString().replace('T', ' ').substring(0, 19);
            var ip = ips[Math.floor(Math.random() * ips.length)];
            var method = methods[Math.floor(Math.random() * methods.length)];
            var path = paths[Math.floor(Math.random() * paths.length)];
            var code = '200 OK';
            var latencyMs = (1.5 + Math.random() * 5).toFixed(1);
            var isWafBlocked = false;
            var blockType = '';

            // Inject occasional WAF events if DDoS or randomly on high load
            if (currentLoad === 'ddos' && Math.random() > 0.3) {
                isWafBlocked = true;
                blockType = 'Rate Limiting Exceeded (max 100/10s)';
                code = '429 Too Many Requests';
                ip = '198.51.100.' + Math.floor(1 + Math.random() * 254);
            } else if (Math.random() > 0.9) {
                isWafBlocked = true;
                blockType = Math.random() > 0.5 ? 'SQL Injection Attack Blocked' : 'XSS Attempt Filtered';
                code = '403 Forbidden';
                path += maliciousQueries[Math.floor(Math.random() * maliciousQueries.length)];
                ip = '203.0.113.' + Math.floor(1 + Math.random() * 254);
            }

            var logLine = "";
            if (isWafBlocked) {
                logLine = '<div style="color:var(--danger);">[' + timestamp + '] [WAF SHIELD ALERT] IP ' + ip + ' - ' + method + ' ' + path + ' - ' + code + ' - BLOCKED BY RULE: ' + blockType + '</div>';
            } else {
                // Determine mock service and server names
                var sKeys = Object.keys(services);
                if (sKeys.length > 0) {
                    var serviceKey = sKeys[Math.floor(Math.random() * sKeys.length)];
                    var serviceName = services[serviceKey];
                    var serverPool = pools[serviceKey];
                    var serverName = (serverPool && serverPool.length > 0) ? serverPool[Math.floor(Math.random() * serverPool.length)] : 'LocalHost';
                    
                    logLine = '<div style="color:var(--text-muted);">[' + timestamp + '] ' + ip + ' - ' + method + ' ' + path + ' -> Pool: [' + serviceName + '] -> Server: ' + serverName + ' - ' + code + ' - ' + latencyMs + 'ms</div>';
                } else {
                    logLine = '<div style="color:var(--text-muted);">[' + timestamp + '] ' + ip + ' - ' + method + ' ' + path + ' - ' + code + ' - ' + latencyMs + 'ms</div>';
                }
            }

            logTerminal(logLine);
        }
    }

    function logTerminal(message, type = 'log') {
        var el = $('#terminal');
        var formatted = message;
        if (type === 'system') {
            formatted = '<div style="color:var(--accent); font-weight:600;">' + message + '</div>';
        } else if (type === 'alert') {
            formatted = '<div style="color:var(--danger); font-weight:600; animation: blinker 1s linear infinite;">' + message + '</div>';
            if ($('#ddos-style').length === 0) {
                $('head').append('<style id="ddos-style">@keyframes blinker { 50% { opacity: 0; } }</style>');
            }
        }
        
        el.append(formatted);
        
        // Auto scroll terminal to bottom
        el.scrollTop(el[0].scrollHeight);
    }

    // Run updates every 1500ms
    setInterval(updateMetrics, 1500);
    updateMetrics(); // trigger first
});
</script>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>
