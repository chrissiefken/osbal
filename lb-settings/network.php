<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/network.php';

$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = trim($_POST['ip']);
    $subnet = trim($_POST['subnet']);
    $gateway = trim($_POST['gateway']);
    $name = trim($_POST['name']);

    if (empty($ip) || empty($subnet) || empty($gateway) || empty($name)) {
        $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">All fields are required.</div>';
    } else {
        try {
            updateAdminIp($ip, $subnet, $gateway, $name);
            $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Appliance network settings saved to configuration. Please publish changes to apply them to the system.</div>';
        } catch (Exception $e) {
            $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

$net = getAdminSettings();
?>

<div class="card-glass" style="max-width: 650px; margin: 0 auto;">
    <h2 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px; color: var(--accent); display:flex; align-items:center; gap:8px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
        Edit Management Network IP
    </h2>

    <?php echo $feedback; ?>

    <p style="margin-bottom:24px;">Configure the hostname and physical network interfaces for this load balancer device. Note that setting IP overrides will update files under `/etc/` in production.</p>

    <form method="POST" action="network.php">
        <div class="form-group">
            <label class="form-label" for="name">Friendly Hostname</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($net['hostname']); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="ip">Management IP Address</label>
            <input type="text" class="form-control" id="ip" name="ip" value="<?php echo htmlspecialchars($net['ip']); ?>" required>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="subnet">Subnet Mask</label>
                <input type="text" class="form-control" id="subnet" name="subnet" value="<?php echo htmlspecialchars($net['subnet']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="gateway">Default Gateway</label>
                <input type="text" class="form-control" id="gateway" name="gateway" value="<?php echo htmlspecialchars($net['gateway']); ?>" required>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 30px;">
            <a href="/reporting/index.php" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Network Interface</button>
        </div>
    </form>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>
