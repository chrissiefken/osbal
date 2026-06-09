<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/system.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ha.php';

$services = getServices();
$service_count = count($services);

$total_servers = 0;
foreach ($services as $service) {
    if (isset($service['servers'])) {
        $total_servers += count($service['servers']);
    }
}

$isSandbox = ApplianceSystem::isSandbox();
$liveMetrics = null;
$stats = null;

if (!$isSandbox) {
    $liveMetrics = ApplianceSystem::getLiveMetrics();
    $stats = ApplianceSystem::getHaproxyStats();
}

$activeConnectionsVal = 0;
$throughputVal = "0.0";
if ($isSandbox || $liveMetrics === null) {
    $activeConnectionsVal = 242;
    $throughputVal = "48.2";
} else {
    $activeConnectionsVal = $liveMetrics['active_connections'];
    $throughputVal = number_format($liveMetrics['throughput'], 1);
}

// Determine VRRP HA Status dynamically
$ha = getHaSettings();
$haStatus = 'Disabled';
$haBadgeColor = 'var(--text-muted)';
$haDotColor = 'var(--text-muted)';
$haDotShadow = 'rgba(255, 255, 255, 0.1)';

if ($ha['enabled']) {
    if ($isSandbox) {
        $haStatus = 'Active';
        $haBadgeColor = 'var(--accent)';
        $haDotColor = 'var(--success)';
        $haDotShadow = 'var(--success)';
    } else {
        $serviceStatus = ApplianceSystem::getServiceStatus('keepalived');
        if ($serviceStatus['active']) {
            $interface = escapeshellarg($ha['interface']);
            $ipOutput = [];
            $ipCode = 0;
            @exec("ip addr show dev {$interface} 2>&1", $ipOutput, $ipCode);
            $ipText = implode(' ', $ipOutput);
            if (strpos($ipText, $ha['virtual_ip']) !== false) {
                $haStatus = 'Active (MASTER)';
                $haBadgeColor = 'var(--accent)';
                $haDotColor = 'var(--success)';
                $haDotShadow = 'var(--success)';
            } else {
                $haStatus = 'Standby (BACKUP)';
                $haBadgeColor = 'var(--warning)';
                $haDotColor = 'var(--warning)';
                $haDotShadow = 'var(--warning)';
            }
        } else {
            $haStatus = 'Inactive (Error)';
            $haBadgeColor = 'var(--danger)';
            $haDotColor = 'var(--danger)';
            $haDotShadow = 'var(--danger)';
        }
    }
}

// Generate connection chart points from connection history JSON
if (!$isSandbox && $liveMetrics !== null) {
    $connHistoryFile = config::getConfigDir() . 'connections_history.json';
    $chart_points = [];
    if (file_exists($connHistoryFile)) {
        $chart_points = json_decode(@file_get_contents($connHistoryFile), true);
    }
    if (!is_array($chart_points) || empty($chart_points)) {
        $chart_points = array_fill(0, 15, 0);
    }
} else {
    $chart_points = array(15, 38, 25, 45, 62, 55, 78, 92, 85, 110, 98, 125, 142);
}

$max_val = max($chart_points);
if ($max_val <= 0) $max_val = 10;
$chart_height = 180;
$chart_width = 1000;
$points_count = count($chart_points);

$svg_coords = "";
$area_coords = "0," . $chart_height . " ";
for ($i = 0; $i < $points_count; $i++) {
    $x = ($i / ($points_count - 1)) * $chart_width;
    $y = $chart_height - (($chart_points[$i] / $max_val) * ($chart_height - 20)) - 10;
    $svg_coords .= "$x,$y ";
    $area_coords .= "$x,$y ";
}
$area_coords .= $chart_width . "," . $chart_height;
?>

<div style="margin-bottom: 30px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin-bottom:6px;">OSBal Console</h1>
            <p style="margin-bottom:0;">Real-time analytics, load distribution and service logs.</p>
        </div>
        <?php if (!$isSandbox && $liveMetrics === null): ?>
            <div class="badge badge-danger" style="text-transform:none; padding:8px 14px; border-radius:10px; font-weight:600; font-size:0.85rem; border-color: rgba(239, 68, 68, 0.4);">
                ⚠ Stats Socket Unreachable (Simulator Fallback Active)
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">
            Active Frontends
            <span class="help-tooltip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span class="tooltip-text">Listener sockets configured in HAProxy that accept client requests on a designated IP and port.</span>
            </span>
        </div>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--accent);"><?php echo $service_count; ?></div>
    </div>
    
    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">
            Backend Nodes Pool
            <span class="help-tooltip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span class="tooltip-text">Target destination servers (e.g. web servers) grouped into pools to handle client load.</span>
            </span>
        </div>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--success);"><?php echo $total_servers; ?></div>
    </div>

    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">
            Throughput
            <span class="help-tooltip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span class="tooltip-text">The volume of network data processed by the load balancer, calculated dynamically in Megabits per second (Mb/s).</span>
            </span>
        </div>
        <div style="font-size: 2.2rem; font-weight: 700; color: #fff;"><?php echo $throughputVal; ?> <span style="font-size: 1.1rem; font-weight:500; color: var(--text-muted);">Mb/s</span></div>
    </div>

    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">
            HA Host Status
            <span class="help-tooltip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span class="tooltip-text">Keepalived host failover state. MASTER indicates this host currently holds the VIP; STANDBY indicates it is waiting.</span>
            </span>
        </div>
        <div style="font-size: 2.2rem; font-weight: 700; color: <?php echo $haBadgeColor; ?>;">
            <span style="display:inline-flex; align-items:center; gap: 8px;">
                <span style="width:14px; height:14px; border-radius:50%; background:<?php echo $haDotColor; ?>; box-shadow: 0 0 10px <?php echo $haDotShadow; ?>;"></span>
                <?php echo $haStatus; ?>
            </span>
        </div>
    </div>
</div>

<!-- Connections Chart -->
<div class="card-glass">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h3 style="margin-bottom:0;">Network Traffic (Active Connections)</h3>
        <span class="badge badge-success" style="animation: pulse 2s infinite;">Live</span>
        <style>@keyframes pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }</style>
    </div>
    
    <div style="background: rgba(0,0,0,0.15); border-radius:12px; padding:15px; border:1px solid var(--border-color);">
        <svg class="chart-svg" viewBox="0 0 1000 180" preserveAspectRatio="none">
            <defs>
                <linearGradient id="chart-gradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--accent)" stop-opacity="0.5"/>
                    <stop offset="100%" stop-color="var(--primary)" stop-opacity="0"/>
                </linearGradient>
            </defs>
            <!-- Grid Lines -->
            <line class="chart-grid" x1="0" y1="30" x2="1000" y2="30"/>
            <line class="chart-grid" x1="0" y1="75" x2="1000" y2="75"/>
            <line class="chart-grid" x1="0" y1="120" x2="1000" y2="120"/>
            <line class="chart-grid" x1="0" y1="165" x2="1000" y2="165"/>
            
            <!-- Area & Line -->
            <polygon class="chart-area" points="<?php echo $area_coords; ?>"/>
            <polyline class="chart-line" points="<?php echo $svg_coords; ?>"/>
        </svg>
    </div>
</div>

<div class="grid-2">
    <!-- Active Frontends List & Connectivity Tester -->
    <div class="card-glass" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="border-bottom:1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 15px;">Appliance Services</h3>
            <?php if (empty($services)): ?>
                <p style="font-style:italic; font-size:0.9rem;">No services configured yet. Head to <a href="/lb-settings/index.php" style="color:var(--accent); text-decoration:none;">Load Balancer Settings</a> to create one.</p>
            <?php else: ?>
                <div class="list-group" style="margin-bottom: 20px;">
                    <?php foreach ($services as $srvId => $service): 
                        $statusBadgeClass = 'badge-success';
                        $statusText = 'Online';
                        $connText = '';
                        
                        if (!$isSandbox && $stats !== null) {
                            $found = false;
                            foreach ($stats as $row) {
                                if ($row['pxname'] === 'frontend_' . $srvId && $row['svname'] === 'FRONTEND') {
                                    $found = true;
                                    if ($row['status'] === 'OPEN') {
                                        $statusText = 'Active';
                                        $statusBadgeClass = 'badge-success';
                                    } else {
                                        $statusText = $row['status'];
                                        $statusBadgeClass = 'badge-danger';
                                    }
                                    $connText = '<span style="font-size:0.8rem; color:var(--text-muted); font-family:monospace; margin-right:8px;">(' . intval($row['scur']) . ' conns)</span>';
                                    break;
                                }
                            }
                            if (!$found) {
                                $statusText = 'Inactive';
                                $statusBadgeClass = 'badge-secondary';
                            }
                        }
                    ?>
                        <div class="list-item" style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($service['name']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;">
                                    <?php echo htmlspecialchars($service['ip']); ?>:<?php echo htmlspecialchars($service['port']); ?>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap: 8px;">
                                <?php echo $connText; ?>
                                <span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-muted); border:1px solid var(--border-color);"><?php echo htmlspecialchars($service['balance']); ?></span>
                                <span class="badge <?php echo $statusBadgeClass; ?>"><?php echo $statusText; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Connectivity Tester -->
        <?php
            $all_backend_nodes = array();
            foreach ($services as $service) {
                if (isset($service['servers'])) {
                    foreach ($service['servers'] as $srv) {
                        $key = $srv['ip'] . ':' . $srv['port'];
                        $all_backend_nodes[$key] = array(
                            'name' => $srv['name'] . ' (' . $service['name'] . ')',
                            'ip' => $srv['ip'],
                            'port' => $srv['port']
                        );
                    }
                }
            }
        ?>
        <div style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
            <h4 style="margin-top: 0; margin-bottom: 12px; color: var(--accent);">Backend Connectivity Tester</h4>
            <form id="tester-form" style="margin-bottom: 0;">
                <div class="grid-2" style="gap:10px; margin-bottom: 12px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:0.75rem; margin-bottom:4px;">Select Node</label>
                        <select class="form-control" id="test-node-select" style="height: 38px; font-size:0.85rem; padding: 6px 12px;">
                            <option value="custom">-- Custom IP/Port --</option>
                            <?php foreach ($all_backend_nodes as $key => $node): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" data-ip="<?php echo htmlspecialchars($node['ip']); ?>" data-port="<?php echo htmlspecialchars($node['port']); ?>">
                                    <?php echo htmlspecialchars($node['name']); ?> [<?php echo htmlspecialchars($key); ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="font-size:0.75rem; margin-bottom:4px;">Target Port</label>
                        <input type="number" class="form-control" id="test-port" placeholder="80" style="height: 38px; font-size:0.85rem; padding: 6px 12px;" required>
                    </div>
                </div>
                <div class="form-group" id="custom-ip-group">
                    <label class="form-label" style="font-size:0.75rem; margin-bottom:4px;">Target IP / Hostname</label>
                    <input type="text" class="form-control" id="test-ip" placeholder="192.168.1.10" style="height: 38px; font-size:0.85rem; padding: 6px 12px;">
                </div>

                <button type="submit" id="btn-test-connect" class="btn btn-secondary" style="width: 100%; height:38px; font-size:0.85rem; margin-top:10px; justify-content:center;">
                    Test Reachability
                </button>
            </form>
            <div id="test-result" style="display:none; margin-top: 12px; padding:10px 14px; border-radius:10px; font-size:0.85rem; text-align:center;"></div>
        </div>
    </div>

    <!-- System Status Check Summary -->
    <div class="card-glass">
        <h3 style="border-bottom:1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 15px;">System Diagnostics</h3>
        <?php
            $results = checkStatus();
            $all_ok = true;
            foreach ($results as $res) {
                if ($res['error']) {
                    $all_ok = false;
                }
            }
        ?>
        
        <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius:12px; padding: 15px; margin-bottom: 20px; display:flex; gap:12px; align-items:center;">
            <div style="background: <?php echo $all_ok ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>; width: 42px; height: 42px; border-radius:50%; display:grid; place-items:center; color: <?php echo $all_ok ? 'var(--success)' : 'var(--danger)'; ?>; font-size:1.2rem; font-weight:700;">
                <?php echo $all_ok ? '✓' : '⚠'; ?>
            </div>
            <div>
                <div style="font-weight:600; color: #fff;"><?php echo $all_ok ? 'System Health Normal' : 'Diagnostics Warning'; ?></div>
                <div style="font-size:0.8rem; color: var(--text-muted);">
                    <?php echo $all_ok ? 'All configurations compile successfully and system binaries are active.' : 'Some software dependencies are missing or files are not configured.'; ?>
                </div>
            </div>
        </div>

        <!-- Service Daemon States -->
        <h4 style="margin-bottom: 10px; color: var(--accent);">OS Daemons Status</h4>
        <div class="list-group" style="margin-bottom: 20px;">
            <div class="list-item" style="display:flex; justify-content:space-between; align-items:center; padding: 8px 12px;">
                <div style="font-weight:500; font-size:0.9rem;">HAProxy (Load Balancer)</div>
                <span id="status-haproxy" class="badge badge-secondary">Checking...</span>
            </div>
            <div class="list-item" style="display:flex; justify-content:space-between; align-items:center; padding: 8px 12px;">
                <div style="font-weight:500; font-size:0.9rem;">Keepalived (VRRP Failover)</div>
                <span id="status-keepalived" class="badge badge-secondary">Checking...</span>
            </div>
            <div class="list-item" style="display:flex; justify-content:space-between; align-items:center; padding: 8px 12px;">
                <div style="font-weight:500; font-size:0.9rem;">Stunnel4 (SSL Proxy)</div>
                <span id="status-stunnel4" class="badge badge-secondary">Checking...</span>
            </div>
        </div>

        <div style="display:flex; gap:10px; justify-content:space-between; margin-bottom: 15px;">
            <button id="btn-refresh-services" class="btn btn-secondary" style="padding: 8px 14px; font-size:0.85rem;">Refresh Services</button>
            <button id="btn-validate-config" class="btn btn-primary" style="padding: 8px 14px; font-size:0.85rem;">Validate HAProxy Config</button>
        </div>

        <div id="validation-result" style="display:none; background:rgba(0,0,0,0.25); border:1px solid var(--border-color); padding:12px; border-radius:10px; font-family:monospace; font-size:0.8rem; white-space:pre-wrap; max-height:120px; overflow-y:auto; margin-bottom: 20px;"></div>
        
        <div style="display:flex; justify-content:flex-end; border-top:1px solid var(--border-color); padding-top:15px;">
            <a href="/install.php" class="btn btn-secondary" style="padding: 6px 12px; font-size:0.8rem; color:var(--text-muted);">View Wizard Diagnostics &rarr;</a>
        </div>
    </div>
</div>

<script>
$(function() {
    // 1. Service Status Checker
    function updateServiceStatus(service) {
        var badge = $('#status-' + service);
        badge.removeClass('badge-success badge-danger').addClass('badge-secondary').text('Checking...');
        
        $.ajax({
            type: "POST",
            url: "/api/diagnostics.php",
            data: { action: 'service_status', service: service }
        }).done(function(res) {
            if (res.success) {
                if (res.active) {
                    badge.removeClass('badge-secondary').addClass('badge-success').text('Active');
                } else {
                    badge.removeClass('badge-secondary').addClass('badge-danger').text('Inactive (' + res.output + ')');
                }
            } else {
                badge.removeClass('badge-secondary').addClass('badge-danger').text('Error');
            }
        }).fail(function() {
            badge.removeClass('badge-secondary').addClass('badge-danger').text('Failed to query');
        });
    }

    function checkAllServices() {
        updateServiceStatus('haproxy');
        updateServiceStatus('keepalived');
        updateServiceStatus('stunnel4');
    }

    // Run status checks on load
    checkAllServices();

    // Refresh handler
    $('#btn-refresh-services').click(function() {
        checkAllServices();
    });

    // 2. HAProxy Config Validator
    $('#btn-validate-config').click(function() {
        var btn = $(this);
        var resultBox = $('#validation-result');
        btn.prop('disabled', true).text('Validating...');
        resultBox.hide().text('');

        $.ajax({
            type: "POST",
            url: "/api/diagnostics.php",
            data: { action: 'validate_configs' }
        }).done(function(res) {
            resultBox.show();
            if (res.success) {
                resultBox.css('border-color', 'var(--success)').css('color', 'var(--success)')
                    .text('✓ Configuration syntax is OK.\n' + res.output);
            } else {
                resultBox.css('border-color', 'var(--danger)').css('color', 'var(--danger)')
                    .text('✗ Syntax Error detected:\n' + res.output);
            }
        }).fail(function() {
            resultBox.show().css('border-color', 'var(--danger)').css('color', 'var(--danger)')
                .text('Failed to run syntax validation command.');
        }).always(function() {
            btn.prop('disabled', false).text('Validate HAProxy Config');
        });
    });

    // 3. Connectivity Tester Dropdown Control
    $('#test-node-select').change(function() {
        var val = $(this).val();
        if (val === 'custom') {
            $('#custom-ip-group').slideDown();
            $('#test-ip').prop('required', true).val('');
            $('#test-port').val('');
        } else {
            var selected = $(this).find('option:selected');
            $('#custom-ip-group').slideUp();
            $('#test-ip').prop('required', false).val(selected.data('ip'));
            $('#test-port').val(selected.data('port'));
        }
    });

    // Handle Connectivity Tester Submit
    $('#tester-form').submit(function(e) {
        e.preventDefault();
        var ip = $('#test-ip').val();
        var port = $('#test-port').val();
        var btn = $('#btn-test-connect');
        var resBox = $('#test-result');

        btn.prop('disabled', true).text('Connecting...');
        resBox.hide().text('').removeClass('badge-success badge-danger');

        $.ajax({
            type: "POST",
            url: "/api/diagnostics.php",
            data: {
                action: 'test_connection',
                ip: ip,
                port: port
            }
        }).done(function(res) {
            resBox.show();
            if (res.success) {
                resBox.css('background', 'rgba(16, 185, 129, 0.1)')
                    .css('border', '1px solid rgba(16, 185, 129, 0.2)')
                    .css('color', 'var(--success)')
                    .text(res.message);
            } else {
                resBox.css('background', 'rgba(239, 68, 68, 0.1)')
                    .css('border', '1px solid rgba(239, 68, 68, 0.2)')
                    .css('color', 'var(--danger)')
                    .text(res.message);
            }
        }).fail(function() {
            resBox.show()
                .css('background', 'rgba(239, 68, 68, 0.1)')
                .css('border', '1px solid rgba(239, 68, 68, 0.2)')
                .css('color', 'var(--danger)')
                .text('API connection failed.');
        }).always(function() {
            btn.prop('disabled', false).text('Test Reachability');
        });
    });
});
</script>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>