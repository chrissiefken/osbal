<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';

$feedback = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_server') {
        $serviceId = $_POST['service_id'];
        $name = trim($_POST['server_name']);
        $ip = trim($_POST['server_ip']);
        $port = intval($_POST['server_port']);
        $weight = isset($_POST['weight']) ? intval($_POST['weight']) : 1;
        $check = isset($_POST['check']) ? true : false;
        
        if (!empty($name) && !empty($ip) && $port > 0) {
            if (addServerToService($serviceId, $name, $ip, $port, $weight, $check)) {
                $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Backend server added successfully.</div>';
            } else {
                $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Failed to add backend server.</div>';
            }
        } else {
            $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Invalid server parameters.</div>';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_waf_settings') {
        $serviceId = $_POST['service_id'];
        $waf_enabled = isset($_POST['waf_enabled']) ? true : false;
        $block_sqli = isset($_POST['block_sqli']) ? true : false;
        $block_xss = isset($_POST['block_xss']) ? true : false;
        $rate_limit = isset($_POST['rate_limit']) ? true : false;
        
        $services = getServices();
        if (isset($services[$serviceId])) {
            $srv = $services[$serviceId];
            if (updateService($serviceId, $srv['name'], $srv['ip'], $srv['port'], $srv['mode'], $srv['balance'], $waf_enabled, $block_sqli, $block_xss, $rate_limit)) {
                $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">WAF settings updated successfully.</div>';
            } else {
                $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Failed to update WAF settings.</div>';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_blacklist') {
        $rawIps = isset($_POST['blacklist']) ? $_POST['blacklist'] : '';
        $ips = preg_split('/[\r\n, ]+/', $rawIps);
        $ips = array_filter(array_map('trim', $ips));
        
        $validIps = array();
        $hasInvalid = false;
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $validIps[] = $ip;
            } else {
                $hasInvalid = true;
                $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Invalid IP address format: ' . htmlspecialchars($ip) . '</div>';
                break;
            }
        }
        
        if (!$hasInvalid) {
            saveBlacklist($validIps);
            $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">IP Access Blacklist saved and compiled successfully.</div>';
        }
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'delete_service' && isset($_GET['id'])) {
        if (deleteService($_GET['id'])) {
            $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Service deleted successfully.</div>';
        }
    } elseif ($_GET['action'] === 'delete_server' && isset($_GET['service_id']) && isset($_GET['server_id'])) {
        if (removeServerFromService($_GET['service_id'], $_GET['server_id'])) {
            $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Backend server removed successfully.</div>';
        }
    }
}

$services = getServices();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
    <div>
        <h1 style="margin-bottom:6px;">Active Load Balancers</h1>
        <p style="margin-bottom:0;">Configure frontends, backend pools, and balancing rules for HAProxy.</p>
    </div>
    <a href="/lb-settings/createService.php" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Create Service
    </a>
</div>

<?php echo $feedback; ?>

<?php if (empty($services)): ?>
    <div class="card-glass" style="text-align: center; padding: 50px 20px;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 16px;"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
        <h3>No Load Balancer Services Configured</h3>
        <p>Get started by defining your first virtual service IP and forwarding rules.</p>
        <a href="/lb-settings/createService.php" class="btn btn-primary" style="margin-top: 15px;">Create Service</a>
    </div>
<?php else: ?>
    <?php foreach ($services as $id => $service): ?>
        <div class="card-glass">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 20px; flex-wrap:wrap; gap: 12px;">
                <div>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom: 6px; flex-wrap:wrap;">
                        <h2 style="margin-bottom:0; font-size:1.4rem;"><?php echo htmlspecialchars($service['name']); ?></h2>
                        <span class="badge badge-success"><?php echo strtoupper($service['mode']); ?></span>
                        <span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-muted); border:1px solid var(--border-color);"><?php echo htmlspecialchars($service['balance']); ?></span>
                        <?php if (isset($service['waf_enabled']) && $service['waf_enabled']): ?>
                            <span class="badge" style="background:rgba(92, 98, 236, 0.15); color:var(--accent); border:1px solid rgba(92, 98, 236, 0.3);">WAF Active</span>
                        <?php else: ?>
                            <span class="badge" style="background:rgba(255,255,255,0.02); color:var(--text-muted); border:1px solid var(--border-color);">WAF Off</span>
                        <?php endif; ?>
                    </div>
                    <div style="color:var(--accent); font-family: monospace; font-size: 1.05rem; font-weight:600;">
                        <?php echo htmlspecialchars($service['ip']); ?>:<?php echo htmlspecialchars($service['port']); ?>
                    </div>
                </div>
                
                <a href="?action=delete_service&id=<?php echo $id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this load balancer service?');" style="padding: 8px 16px; font-size:0.85rem;">
                    Delete Service
                </a>
            </div>

            <!-- Backend Servers List -->
            <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Backend Nodes Pool</h4>
            
            <?php if (empty($service['servers'])): ?>
                <p style="font-style: italic; font-size:0.9rem; margin-bottom: 20px;">No backend destination servers added yet.</p>
            <?php else: ?>
                <div style="overflow-x: auto; margin-bottom: 20px;">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Node Name</th>
                                <th>Target IP : Port</th>
                                <th>Weight</th>
                                <th>Health Check</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service['servers'] as $srvId => $srv): ?>
                                <tr>
                                    <td style="font-weight:500;"><?php echo htmlspecialchars($srv['name']); ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($srv['ip']); ?>:<?php echo htmlspecialchars($srv['port']); ?></td>
                                    <td><?php echo intval($srv['weight']); ?></td>
                                    <td>
                                        <?php if ($srv['check']): ?>
                                            <span style="color: var(--success); display:flex; align-items:center; gap:4px;">
                                                <span style="width:6px; height:6px; border-radius:50%; background:var(--success);"></span> Enabled
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="?action=delete_server&service_id=<?php echo $id; ?>&server_id=<?php echo $srvId; ?>" style="color: var(--danger); text-decoration: none; font-size:0.9rem; font-weight:600;" onclick="return confirm('Remove this backend server?');">
                                            Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Add server form -->
            <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-top:20px;">
                <h4 style="margin-bottom: 16px; font-size: 0.95rem;">+ Add Backend Destination Server</h4>
                <form method="POST" action="index.php" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)) 100px 100px; gap: 12px; align-items: flex-end;">
                    <input type="hidden" name="action" value="add_server">
                    <input type="hidden" name="service_id" value="<?php echo $id; ?>">
                    
                    <div>
                        <label class="form-label" style="font-size:0.75rem;">Server Name</label>
                        <input type="text" class="form-control" name="server_name" placeholder="web-node-01" required style="padding: 10px 12px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:0.75rem;">IP Address</label>
                        <input type="text" class="form-control" name="server_ip" placeholder="192.168.1.10" required style="padding: 10px 12px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:0.75rem;">Port</label>
                        <input type="number" class="form-control" name="server_port" value="80" required style="padding: 10px 12px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:0.75rem;">Weight</label>
                        <input type="number" class="form-control" name="weight" value="1" style="padding: 10px 12px;">
                    </div>
                    <div style="display:flex; flex-direction:column; justify-content:center; height:38px;">
                        <label class="radio-label" style="font-size: 0.8rem; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="check" value="1" checked style="width:16px; height:16px; margin:0;"> Health Check
                        </label>
                    </div>
                    <button class="btn btn-secondary" type="submit" style="padding: 10px 16px; font-size: 0.9rem; font-weight:600; width:100%;">
                        Add Node
                    </button>
                </form>
            </div>

            <!-- WAF Shield settings form -->
            <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-top:20px;">
                <h4 style="margin-bottom: 16px; font-size: 0.95rem; color: var(--accent); display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    WAF Shield Settings
                </h4>
                <form method="POST" action="index.php" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                    <input type="hidden" name="action" value="update_waf_settings">
                    <input type="hidden" name="service_id" value="<?php echo $id; ?>">
                    
                    <div style="display:flex; gap: 20px; flex-wrap:wrap;">
                        <label class="radio-label" style="font-size:0.85rem; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="waf_enabled" value="1" <?php echo (isset($service['waf_enabled']) && $service['waf_enabled']) ? 'checked' : ''; ?> style="width:16px; height:16px;"> Enable WAF
                        </label>
                        <label class="radio-label" style="font-size:0.85rem; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="block_sqli" value="1" <?php echo (isset($service['block_sqli']) && $service['block_sqli']) ? 'checked' : ''; ?> style="width:16px; height:16px;"> Block SQLi
                        </label>
                        <label class="radio-label" style="font-size:0.85rem; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="block_xss" value="1" <?php echo (isset($service['block_xss']) && $service['block_xss']) ? 'checked' : ''; ?> style="width:16px; height:16px;"> Block XSS
                        </label>
                        <label class="radio-label" style="font-size:0.85rem; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="rate_limit" value="1" <?php echo (isset($service['rate_limit']) && $service['rate_limit']) ? 'checked' : ''; ?> style="width:16px; height:16px;"> Rate Limiting
                        </label>
                    </div>
                    
                    <button class="btn btn-secondary" type="submit" style="padding: 8px 16px; font-size: 0.85rem; font-weight:600;">
                        Update WAF Rules
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Global IP Access Blacklist -->
<div class="card-glass" style="margin-top:40px;">
    <h3 style="color:var(--danger); display:flex; align-items:center; gap:8px; margin-bottom:12px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        Global IP Access Blacklist
    </h3>
    <p>Specify IPv4/IPv6 addresses that should be denied access globally across all active WAF protected services.</p>
    
    <form method="POST" action="index.php" style="margin-top:16px;">
        <input type="hidden" name="action" value="update_blacklist">
        <div class="form-group">
            <textarea class="form-control" name="blacklist" rows="4" placeholder="e.g.&#10;192.168.1.180&#10;203.0.113.55" style="font-family:monospace; font-size:0.9rem; resize:vertical;"><?php echo htmlspecialchars(implode("\n", getBlacklist())); ?></textarea>
        </div>
        <div style="display:flex; justify-content:flex-end;">
            <button class="btn btn-primary" type="submit" style="background:var(--danger); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);">
                Save & Compile IP Blacklist
            </button>
        </div>
    </form>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>