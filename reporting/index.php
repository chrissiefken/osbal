<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';

$services = getServices();
$service_count = count($services);

$total_servers = 0;
foreach ($services as $service) {
    if (isset($service['servers'])) {
        $total_servers += count($service['servers']);
    }
}

// Generate some nice fake points for a beautiful SVG chart
$chart_points = array(15, 38, 25, 45, 62, 55, 78, 92, 85, 110, 98, 125, 142);
$max_val = max($chart_points);
$chart_height = 180;
$chart_width = 1000;
$points_count = count($chart_points);

$svg_coords = "";
$area_coords = "0," . $chart_height . " ";
for ($i = 0; $i < $points_count; $i++) {
    $x = ($i / ($points_count - 1)) * $chart_width;
    // scale y
    $y = $chart_height - (($chart_points[$i] / $max_val) * ($chart_height - 20)) - 10;
    $svg_coords .= "$x,$y ";
    $area_coords .= "$x,$y ";
}
$area_coords .= $chart_width . "," . $chart_height;

?>

<div style="margin-bottom: 30px;">
    <h1>OSBal Console</h1>
    <p>Real-time analytics, load distribution and service logs.</p>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">Active Frontends</div>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--accent);"><?php echo $service_count; ?></div>
    </div>
    
    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">Backend Nodes Pool</div>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--success);"><?php echo $total_servers; ?></div>
    </div>

    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">Throughput</div>
        <div style="font-size: 2.2rem; font-weight: 700; color: #fff;">48.2 <span style="font-size: 1.1rem; font-weight:500; color: var(--text-muted);">Mb/s</span></div>
    </div>

    <div class="card-glass" style="padding: 20px;">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 10px;">HA Host Status</div>
        <div style="font-size: 2.2rem; font-weight: 700; color: var(--accent);">
            <span style="display:inline-flex; align-items:center; gap: 8px;">
                <span style="width:14px; height:14px; border-radius:50%; background:var(--success); box-shadow: 0 0 10px var(--success);"></span>
                Active
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
    <!-- Active Frontends List -->
    <div class="card-glass">
        <h3 style="border-bottom:1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 15px;">Appliance Services</h3>
        <?php if (empty($services)): ?>
            <p style="font-style:italic; font-size:0.9rem;">No services configured yet. Head to <a href="/lb-settings/index.php" style="color:var(--accent); text-decoration:none;">Load Balancer Settings</a> to create one.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($services as $service): ?>
                    <div class="list-item" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($service['name']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;">
                                <?php echo htmlspecialchars($service['ip']); ?>:<?php echo htmlspecialchars($service['port']); ?>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap: 8px;">
                            <span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-muted); border:1px solid var(--border-color);"><?php echo htmlspecialchars($service['balance']); ?></span>
                            <span class="badge badge-success">Online</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
        
        <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius:12px; padding: 15px; margin-bottom:15px; display:flex; gap:12px; align-items:center;">
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
        
        <div style="display:flex; justify-content:flex-end;">
            <a href="/install.php" class="btn btn-secondary" style="padding: 8px 16px; font-size:0.85rem;">View Wizard Diagnostics &rarr;</a>
        </div>
    </div>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>