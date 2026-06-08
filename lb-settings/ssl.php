<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ssl.php';

$feedback = '';

// Handle creating certificate profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_cert') {
    $name = trim($_POST['name']);
    $cert = $_POST['cert_pem'];
    $key = $_POST['key_pem'];
    $bindIp = trim($_POST['bind_ip']);
    $bindPort = intval($_POST['bind_port']);
    $targetPort = intval($_POST['target_port']);

    if (empty($name) || empty($cert) || empty($key) || $bindPort <= 0 || $targetPort <= 0) {
        $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">All fields, including PEM keys and ports, are required.</div>';
    } else {
        try {
            addCertificate($name, $cert, $key, $bindIp, $bindPort, $targetPort);
            $feedback = '<div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">✓ Certificate profile saved successfully.</div>';
        } catch (Exception $e) {
            $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Handle deleting certificate profile
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['cert'])) {
    $certName = $_GET['cert'];
    if (deleteCertificate($certName)) {
        $feedback = '<div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">✓ Certificate profile deleted successfully.</div>';
    } else {
        $feedback = '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">Failed to delete certificate profile.</div>';
    }
}

$activeCerts = getSslCertificates();
?>

<div style="margin-bottom: 30px;">
    <h1>SSL Certificates</h1>
    <p>Configure Stunnel4 SSL termination profiles to offload HTTPS/TLS sessions before routing to HAProxy.</p>
</div>

<?php echo $feedback; ?>

<div class="grid-2">
    <!-- Active Certificates List -->
    <div class="card-glass">
        <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px;">Active SSL Profiles</h3>
        
        <?php if (empty($activeCerts)): ?>
            <p style="font-style:italic; font-size:0.9rem; color:var(--text-muted); text-align:center; padding: 20px 0;">No SSL certificate profiles configured. Use the form to add one.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($activeCerts as $name => $cert): ?>
                    <div class="list-item" style="display:flex; justify-content:space-between; align-items:center; padding: 14px 16px;">
                        <div>
                            <div style="font-weight: 600; font-size: 1.05rem; display:flex; align-items:center; gap:6px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                <?php echo htmlspecialchars($name); ?>
                            </div>
                            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:6px; font-family:monospace;">
                                Bind: <?php echo htmlspecialchars($cert['bindIp']); ?>:<?php echo htmlspecialchars($cert['bindPort']); ?> &rarr; Local: <?php echo htmlspecialchars($cert['targetPort']); ?>
                            </div>
                            <div style="font-size:0.75rem; color:rgba(255,255,255,0.25); margin-top:4px; font-family:monospace; word-break:break-all;">
                                File: <?php echo htmlspecialchars($cert['pemPath']); ?>
                            </div>
                        </div>
                        <a href="?action=delete&cert=<?php echo urlencode($name); ?>" class="btn btn-danger" style="padding: 6px 12px; font-size:0.8rem;" onclick="return confirm('Are you sure you want to delete this SSL profile?');">
                            Remove
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create SSL Profile Form -->
    <div class="card-glass">
        <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px;">Add SSL Profile</h3>
        <form method="POST" action="ssl.php">
            <input type="hidden" name="action" value="add_cert">
            
            <div class="form-group">
                <label class="form-label" for="name">Friendly Name / Domain</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="example.com" required>
            </div>

            <div class="grid-3" style="gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="bind_ip">Bind IP</label>
                    <input type="text" class="form-control" id="bind_ip" name="bind_ip" value="*" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="bind_port">Bind Port</label>
                    <input type="number" class="form-control" id="bind_port" name="bind_port" value="443" min="1" max="65535" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="target_port">Target Port</label>
                    <input type="number" class="form-control" id="target_port" name="target_port" placeholder="80" min="1" max="65535" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="cert_pem">Certificate PEM</label>
                <textarea class="form-control" id="cert_pem" name="cert_pem" placeholder="-----BEGIN CERTIFICATE-----&#10;..." style="font-family:monospace; font-size:0.75rem; height:100px; resize:vertical;" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="key_pem">Private Key PEM</label>
                <textarea class="form-control" id="key_pem" name="key_pem" placeholder="-----BEGIN PRIVATE KEY-----&#10;..." style="font-family:monospace; font-size:0.75rem; height:100px; resize:vertical;" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; justify-content: center; height:45px;">
                Save SSL Profile
            </button>
        </form>
    </div>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>
