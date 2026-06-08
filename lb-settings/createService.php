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
    $waf_enabled = isset($_POST['waf_enabled']) ? true : false;
    $block_sqli = isset($_POST['block_sqli']) ? true : false;
    $block_xss = isset($_POST['block_xss']) ? true : false;
    $rate_limit = isset($_POST['rate_limit']) ? true : false;

    if (empty($name) || $port <= 0) {
        $error = 'Please fill out the Service Name and valid Bind Port.';
    } else {
        // Create the service
        $id = createService($name, $ip, $port, $mode, $balance, $waf_enabled, $block_sqli, $block_xss, $rate_limit);
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
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; margin-bottom: 0;">A friendly description to identify this virtual load balancer service in the dashboard.</p>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="ip">Bind IP Address</label>
                <input type="text" class="form-control" id="ip" name="ip" placeholder="e.g. * (all interfaces) or 10.0.0.101">
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; margin-bottom: 0;">Use <code>*</code> to listen on all interfaces, or specify a dedicated IP address.</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="port">Bind Port</label>
                <input type="number" class="form-control" id="port" name="port" value="80" required min="1" max="65535">
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; margin-bottom: 0;">The public port clients connect to (e.g. <code>80</code> for HTTP, <code>443</code> for HTTPS).</p>
            </div>
        </div>

        <!-- Collapsible Advanced Routing Configuration -->
        <div style="margin: 20px 0;">
            <button type="button" id="toggle-advanced-routing" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                <span>Show Advanced Protocol & Routing Settings</span>
                <span id="adv-arrow">▼</span>
            </button>
        </div>

        <div id="advanced-routing-settings" style="display: none; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 20px; border-radius: 12px; margin-bottom: 24px; margin-top: 12px;">
            <div class="grid-2">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Transport Mode</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="mode" id="mode-http" value="http" checked>
                            HTTP (Application layer routing - recommended)
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="mode" id="mode-tcp" value="tcp">
                            TCP (Raw connection proxying)
                        </label>
                    </div>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; margin-bottom: 0;">HTTP mode allows cookie stickiness, path headers routing, and Web Application Firewall security rules parsing.</p>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Balancing Strategy</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="balance" id="strategy-rr" value="roundrobin" checked>
                            Round Robin (Standard distribution)
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="balance" id="strategy-cookie" value="cookie">
                            Cookie-based Session Stickiness
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="balance" id="strategy-ip" value="ip">
                            Source IP Hash (Sticky by client IP)
                        </label>
                    </div>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; margin-bottom: 0;">Decides how requests are divided between backends. Round Robin sends traffic sequentially.</p>
                </div>
            </div>
        </div>

        <!-- WAF Shield & Security -->
        <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 16px; margin-top: 24px; color: var(--accent);">WAF Shield & Security</h4>
        <div class="form-group">
            <label class="radio-label" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="waf_enabled" id="waf_enabled" value="1" style="width:18px; height:18px; margin:0;">
                <strong>Enable Web Application Firewall (WAF)</strong>
            </label>
            <p style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Natively inspects HTTP queries and traffic thresholds in HAProxy to block SQLi/XSS scripts and limit request spikes.</p>
        </div>

        <div id="waf-settings" style="display:none; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 20px; border-radius: 12px; margin-bottom: 24px; margin-top: 12px;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="radio-label" style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                    <input type="checkbox" name="block_sqli" value="1" checked style="width:16px; height:16px; margin:0;"> Block SQL Injection (SQLi) patterns
                </label>
                <label class="radio-label" style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                    <input type="checkbox" name="block_xss" value="1" checked style="width:16px; height:16px; margin:0;"> Block Cross-Site Scripting (XSS) vectors
                </label>
                <label class="radio-label" style="display:flex; align-items:center; gap:8px; margin-bottom:0;">
                    <input type="checkbox" name="rate_limit" value="1" checked style="width:16px; height:16px; margin:0;"> Rate Limiting (max 100 requests per 10s per IP)
                </label>
            </div>
        </div>

        <script>
        $(function() {
            // WAF Toggle
            $('#waf_enabled').change(function() {
                if ($(this).is(':checked')) {
                    $('#waf-settings').slideDown();
                } else {
                    $('#waf-settings').slideUp();
                }
            });

            // Collapsible Advanced routing toggle
            $('#toggle-advanced-routing').click(function() {
                var container = $('#advanced-routing-settings');
                var arrow = $('#adv-arrow');
                if (container.is(':visible')) {
                    container.slideUp();
                    $(this).find('span:first').text('Show Advanced Protocol & Routing Settings');
                    arrow.text('▼');
                } else {
                    container.slideDown();
                    $(this).find('span:first').text('Hide Advanced Protocol & Routing Settings');
                    arrow.text('▲');
                }
            });
        });
        </script>

        <div style="display:flex; justify-content:flex-end; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 30px;">
            <a href="/lb-settings/index.php" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit">Create Virtual Service</button>
        </div>
    </form>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>