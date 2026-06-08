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
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom: 6px;">
                        <h2 style="margin-bottom:0; font-size:1.4rem;"><?php echo htmlspecialchars($service['name']); ?></h2>
                        <span class="badge badge-success"><?php echo strtoupper($service['mode']); ?></span>
                        <span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-muted); border:1px solid var(--border-color);"><?php echo htmlspecialchars($service['balance']); ?></span>
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
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>