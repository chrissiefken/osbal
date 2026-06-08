<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ha.php';

$feedback = '';

// Handle updating HA configurations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_ha') {
    $enabled = isset($_POST['enabled']) ? true : false;
    $role = $_POST['role'] === 'BACKUP' ? 'BACKUP' : 'MASTER';
    $vip = trim($_POST['virtual_ip']);
    $interface = trim($_POST['interface']);
    $routerId = intval($_POST['router_id']);
    $authPass = $_POST['auth_pass'];
    $partnerIp = trim($_POST['partner_ip']);
    $apiKey = trim($_POST['api_key']);

    if ($enabled && (empty($vip) || empty($interface) || $routerId <= 0 || empty($authPass))) {
        $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">To enable HA, Virtual IP, Network Interface, Router ID, and Auth Password are required.</div>';
    } else {
        $settings = array(
            'enabled' => $enabled,
            'role' => $role,
            'virtual_ip' => $vip,
            'interface' => $interface,
            'router_id' => $routerId,
            'auth_pass' => $authPass,
            'partner_ip' => $partnerIp,
            'api_key' => $apiKey
        );
        saveHaSettings($settings);
        $feedback = '<div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">✓ HA configurations saved successfully.</div>';
    }
}

// Handle manual Sync Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_configs') {
    $syncRes = triggerHaSync();
    if ($syncRes['success']) {
        $feedback = '<div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">✓ ' . htmlspecialchars($syncRes['message']) . '</div>';
    } else {
        $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">✗ ' . htmlspecialchars($syncRes['message']) . '</div>';
    }
}

$ha = getHaSettings();
?>

<div style="margin-bottom: 30px;">
    <h1>High Availability (HA)</h1>
    <p>Configure active-passive clustering using Keepalived VRRP. Pair two load balancers to share a Virtual IP (VIP) for automatic failover.</p>
</div>

<?php echo $feedback; ?>

<div class="grid-2">
    <!-- HA Configuration Form -->
    <div class="card-glass">
        <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px;">HA Clustering Settings</h3>
        <form method="POST" action="ha.php">
            <input type="hidden" name="action" value="save_ha">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="checkbox-container" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="enabled" value="1" <?php echo $ha['enabled'] ? 'checked' : ''; ?> style="width:18px; height:18px;">
                    <span style="font-weight:600; font-size:0.95rem;">Enable VRRP High-Availability clustering</span>
                </label>
            </div>

            <div class="grid-2" style="gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="role">Node Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="MASTER" <?php echo $ha['role'] === 'MASTER' ? 'selected' : ''; ?>>Master (Active Node)</option>
                        <option value="BACKUP" <?php echo $ha['role'] === 'BACKUP' ? 'selected' : ''; ?>>Backup (Standby Node)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="virtual_ip">Shared Virtual IP (VIP)</label>
                    <input type="text" class="form-control" id="virtual_ip" name="virtual_ip" placeholder="192.168.1.250" value="<?php echo htmlspecialchars($ha['virtual_ip']); ?>">
                </div>
            </div>

            <div class="grid-3" style="gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="interface">Network NIC</label>
                    <input type="text" class="form-control" id="interface" name="interface" placeholder="eth0" value="<?php echo htmlspecialchars($ha['interface']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="router_id">VRRP Router ID</label>
                    <input type="number" class="form-control" id="router_id" name="router_id" min="1" max="255" value="<?php echo intval($ha['router_id']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="auth_pass">VRRP Password</label>
                    <input type="password" class="form-control" id="auth_pass" name="auth_pass" value="<?php echo htmlspecialchars($ha['auth_pass']); ?>">
                </div>
            </div>

            <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 16px; margin-top: 24px; color: var(--accent);">Partner Node Synchronization</h4>
            
            <div class="grid-2" style="gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="partner_ip">Partner IP Address</label>
                    <input type="text" class="form-control" id="partner_ip" name="partner_ip" placeholder="192.168.1.102" value="<?php echo htmlspecialchars($ha['partner_ip']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="api_key">Shared API Key</label>
                    <input type="password" class="form-control" id="api_key" name="api_key" placeholder="Enter remote API key" value="<?php echo htmlspecialchars($ha['api_key']); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px; justify-content: center; height:45px;">
                Save HA Settings
            </button>
        </form>
    </div>

    <!-- Cluster Management Actions -->
    <div class="card-glass" style="display:flex; flex-direction:column; justify-content:space-between;">
        <div>
            <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px;">Cluster Diagnostics & Actions</h3>
            
            <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius:12px; padding: 16px; margin-bottom: 20px;">
                <h4 style="margin-top:0; color:var(--accent); margin-bottom:12px;">Cluster Status</h4>
                <div style="display:grid; grid-template-columns: 140px 1fr; gap: 8px; font-size: 0.9rem;">
                    <div style="color:var(--text-muted);">HA Enabled:</div>
                    <div style="font-weight:600; color:<?php echo $ha['enabled'] ? 'var(--success)' : 'var(--danger)'; ?>;">
                        <?php echo $ha['enabled'] ? 'Yes' : 'No'; ?>
                    </div>
                    <div style="color:var(--text-muted);">Local Mode:</div>
                    <div style="font-weight:600;"><?php echo htmlspecialchars($ha['role']); ?> (Priority: <?php echo $ha['role'] === 'MASTER' ? 101 : 100; ?>)</div>
                    <div style="color:var(--text-muted);">Virtual IP (VIP):</div>
                    <div style="font-weight:600; font-family:monospace;"><?php echo !empty($ha['virtual_ip']) ? htmlspecialchars($ha['virtual_ip']) : 'None'; ?></div>
                    <div style="color:var(--text-muted);">Partner IP:</div>
                    <div style="font-weight:600; font-family:monospace;"><?php echo !empty($ha['partner_ip']) ? htmlspecialchars($ha['partner_ip']) : 'Not configured'; ?></div>
                </div>
            </div>

            <?php if ($ha['enabled'] && !empty($ha['partner_ip'])): ?>
                <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius:12px; padding: 16px; margin-bottom: 20px;">
                    <h4 style="margin-top:0; color:var(--accent); margin-bottom:12px;">Partner Node Health Check</h4>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span id="partner-ping-status" style="font-weight:500; font-size:0.9rem; color:var(--text-muted);">Checking partner reachability...</span>
                        <button id="btn-ping-partner" class="btn btn-secondary" style="padding: 6px 12px; font-size:0.8rem;">Check Status</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($ha['enabled'] && !empty($ha['partner_ip']) && !empty($ha['api_key'])): ?>
            <div style="border-top:1px solid var(--border-color); padding-top:20px; margin-top:20px;">
                <h4 style="margin-top:0; margin-bottom:8px; color:var(--warning);">Force Configurations Synchronization</h4>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:15px; line-height:1.5;">This will override the backup load balancer configurations with the local Master database immediately, triggering a backup daemons hot reload.</p>
                <form method="POST" action="ha.php" style="margin:0;">
                    <input type="hidden" name="action" value="sync_configs">
                    <button type="submit" class="btn btn-warning" style="width:100%; height:45px; justify-content:center; font-weight:600; background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); color:#fff; border:none; box-shadow:0 4px 15px rgba(245, 158, 11, 0.25);">
                        Synchronize Settings Now
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($ha['enabled'] && !empty($ha['partner_ip'])): ?>
<script>
$(function() {
    function pingPartner() {
        var statusLabel = $('#partner-ping-status');
        var btn = $('#btn-ping-partner');
        statusLabel.css('color', 'var(--text-muted)').text('Pinging partner node...');
        btn.prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "/api/diagnostics.php",
            data: {
                action: 'test_connection',
                ip: '<?php echo $ha['partner_ip']; ?>',
                port: 80 // Web service port
            }
        }).done(function(res) {
            if (res.success) {
                statusLabel.css('color', 'var(--success)').text('✓ Partner is Online & Responsive (Port 80)');
            } else {
                statusLabel.css('color', 'var(--danger)').text('✗ Partner is Unreachable');
            }
        }).fail(function() {
            statusLabel.css('color', 'var(--danger)').text('Failed to query diagnostics API');
        }).always(function() {
            btn.prop('disabled', false);
        });
    }

    // Ping partner on load
    pingPartner();

    $('#btn-ping-partner').click(function(e) {
        e.preventDefault();
        pingPartner();
    });
});
</script>
<?php endif; ?>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>
