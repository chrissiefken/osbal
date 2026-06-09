<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/services.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/ssl.php';

$activeCerts = getSslCertificates();

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
    $ssl_enabled = isset($_POST['ssl_enabled']) ? true : false;
    $ssl_port = isset($_POST['ssl_port']) ? intval($_POST['ssl_port']) : 443;
    $ssl_cert_name = isset($_POST['ssl_cert_name']) ? $_POST['ssl_cert_name'] : '';

    if (empty($name) || $port <= 0) {
        $error = 'Please fill out the Service Name and valid Bind Port.';
    } else {
        // Create the service
        $id = createService($name, $ip, $port, $mode, $balance, $waf_enabled, $block_sqli, $block_xss, $rate_limit, $ssl_enabled, $ssl_port, $ssl_cert_name);
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
            <label class="form-label" for="name">
                Service Name
                <span class="help-tooltip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <span class="tooltip-text">A friendly descriptive label (e.g. "Main Web Cluster") to identify this virtual load balancer service in logs and reports.</span>
                </span>
            </label>
            <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Production Web Frontend" required>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; margin-bottom: 0;">A friendly description to identify this virtual load balancer service in the dashboard.</p>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="ip">
                    Bind IP Address
                    <span class="help-tooltip">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <span class="tooltip-text">The network interface IP HAProxy binds to. Use "*" to listen on all interfaces, or specify a virtual IP (VIP) for clustering.</span>
                    </span>
                </label>
                <input type="text" class="form-control" id="ip" name="ip" placeholder="e.g. * (all interfaces) or 10.0.0.101">
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; margin-bottom: 0;">Use <code>*</code> to listen on all interfaces, or specify a dedicated IP address.</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="port">
                    Bind Port
                    <span class="help-tooltip">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <span class="tooltip-text">The TCP port clients connect to. Commonly port 80 for HTTP, 443 for HTTPS, or custom ports (1-65535).</span>
                    </span>
                </label>
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
                    <label class="form-label">
                        Transport Mode
                        <span class="help-tooltip">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span class="tooltip-text">HTTP mode operates at the application layer, parsing headers and enabling WAF rules. TCP mode performs raw Layer 4 proxying without decryption (very fast).</span>
                        </span>
                    </label>
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
                    <label class="form-label">
                        Balancing Strategy
                        <span class="help-tooltip">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span class="tooltip-text">Round Robin rotates nodes. Cookie Stickiness tracks sessions via injected browser cookies. Source IP Hash hashes the client's IP to consistently hit the same backend.</span>
                        </span>
                    </label>
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

        <!-- Frontend HTTPS Settings -->
        <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 16px; margin-top: 24px; color: var(--accent);">Frontend HTTPS Settings</h4>
        <div class="form-group">
            <label class="radio-label" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="ssl_enabled" id="ssl_enabled" value="1" style="width:18px; height:18px; margin:0;">
                <strong>Enable HTTPS Frontend Acceptance (SSL Termination)</strong>
            </label>
            <p style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Natively offload TLS/SSL sessions using Stunnel4 before proxying plain HTTP to HAProxy.</p>
        </div>

        <div id="ssl-settings" style="display:none; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 20px; border-radius: 12px; margin-bottom: 24px; margin-top: 12px;">
            <div class="grid-2">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="ssl_cert_name">
                        SSL Certificate Profile
                        <span class="help-tooltip">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span class="tooltip-text">Selects an uploaded SSL certificate and private key profile to be bound to Stunnel4 for decryption.</span>
                        </span>
                    </label>
                    <?php if (empty($activeCerts)): ?>
                        <p style="font-size:0.9rem; color:var(--danger); font-weight:500; margin-top: 8px;">No SSL certificate profiles configured. <a href="/lb-settings/ssl.php" style="color:var(--accent); text-decoration:none;">Configure one first &rarr;</a></p>
                    <?php else: ?>
                        <select class="form-control" id="ssl_cert_name" name="ssl_cert_name" style="background: rgba(0,0,0,0.25);">
                            <?php foreach ($activeCerts as $name => $cert): ?>
                                <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; margin-bottom: 0;">Select the cert profile to load (must contain private key + certificate).</p>
                    <?php endif; ?>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="ssl_port">
                        HTTPS Port
                        <span class="help-tooltip">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span class="tooltip-text">The public port Stunnel4 listens on to accept encrypted TLS handshakes (usually port 443).</span>
                        </span>
                    </label>
                    <input type="number" class="form-control" id="ssl_port" name="ssl_port" value="443" min="1" max="65535">
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; margin-bottom: 0;">The port stunnel will listen on to accept HTTPS traffic (typically 443).</p>
                </div>
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

            // SSL Toggle
            $('#ssl_enabled').change(function() {
                if ($(this).is(':checked')) {
                    $('#ssl-settings').slideDown();
                } else {
                    $('#ssl-settings').slideUp();
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