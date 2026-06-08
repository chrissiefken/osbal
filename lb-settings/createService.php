<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $ip = trim($_POST['ip']);
    $port = intval($_POST['port']);
    $mode = $_POST['mode'];
    $balance = $_POST['balance'];

    if (empty($name) || $port <= 0) {
        $error = 'Please fill out the Service Name and valid Bind Port.';
    } else {
        // Create the service
        $id = createService($name, $ip, $port, $mode, $balance);
        if ($id) {
            header('Location: /lb-settings/index.php');
            exit;
        } else {
            $error = 'Failed to create service configuration.';
        }
    }
}
?>

<div class="card-glass" style="max-width: 650px; margin: 0 auto;">
    <h2 style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px;">Add a New Service</h2>
    
    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="createService.php">
        <div class="form-group">
            <label class="form-label" for="name">Service Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Production Web Frontend" required>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="ip">Bind IP Address</label>
                <input type="text" class="form-control" id="ip" name="ip" placeholder="e.g. * (all interfaces) or 10.0.0.101">
            </div>
            <div class="form-group">
                <label class="form-label" for="port">Bind Port</label>
                <input type="number" class="form-control" id="port" name="port" value="80" required min="1" max="65535">
            </div>
        </div>

        <div class="grid-2" style="margin-top: 10px;">
            <div class="form-group">
                <label class="form-label">Transport Mode</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="mode" id="mode-http" value="http" checked>
                        HTTP (App layer routing)
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="mode" id="mode-tcp" value="tcp">
                        TCP (Raw connection proxying)
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Balancing Strategy</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="balance" id="strategy-rr" value="roundrobin" checked>
                        Round Robin
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="balance" id="strategy-cookie" value="cookie">
                        Cookie-based Stickiness
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="balance" id="strategy-ip" value="ip">
                        Source IP Hash
                    </label>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 30px;">
            <a href="/lb-settings/index.php" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit">Create Virtual Service</button>
        </div>
    </form>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>