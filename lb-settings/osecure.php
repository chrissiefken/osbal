<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/osecure.php';

$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_osecure_settings') {
        $enabled = isset($_POST['osecure_enabled']) ? true : false;
        $license_key = isset($_POST['license_key']) ? trim($_POST['license_key']) : '';
        $server_url = isset($_POST['server_url']) ? trim($_POST['server_url']) : 'http://localhost:8000';
        $share_metrics = isset($_POST['share_metrics']) ? true : false;
        
        $current = getOSecureSettings();
        $settings = array(
            'enabled' => $enabled,
            'license_key' => $license_key,
            'server_url' => $server_url,
            'sync_interval' => isset($current['sync_interval']) ? $current['sync_interval'] : 60,
            'last_sync' => isset($current['last_sync']) ? $current['last_sync'] : 'Never',
            'share_metrics' => $share_metrics
        );
        
        saveOSecureSettings($settings);
        if ($enabled) {
            $feedback = '<div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">OSecure settings updated successfully.</div>';
        } else {
            $feedback = '<div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); color: var(--warning); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">OSecure Integration disabled.</div>';
        }
    }
}

$osecure = getOSecureSettings();
?>

<div style="max-width: 1000px; margin: 0 auto; padding-bottom: 40px;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
        <h2 style="margin:0; color: var(--accent); display:flex; align-items:center; gap:10px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            OSecure Integration Settings
        </h2>
        <div>
            <span class="badge <?php echo $osecure['enabled'] ? 'badge-success' : 'badge-secondary'; ?>" style="font-size: 0.8rem; padding: 6px 12px; <?php echo !$osecure['enabled'] ? 'background:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:var(--text-muted);' : 'background:rgba(155, 81, 224, 0.15); color:var(--primary); border:1px solid rgba(155, 81, 224, 0.3);'; ?>">
                <?php echo $osecure['enabled'] ? 'ACTIVE INTEGRATION' : 'OPTIONAL PLUGIN'; ?>
            </span>
        </div>
    </div>

    <?php echo $feedback; ?>

    <div class="grid-2">
        <!-- LEFT COLUMN: Configurations & Setup -->
        <div>
            <?php if (!$osecure['enabled']): ?>
                <!-- Marketing/Onboarding Panel -->
                <div class="card-glass" style="padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); background: rgba(15, 23, 42, 0.35);">
                    <h3 style="margin-top:0; margin-bottom:15px; color:#fff; font-weight:600;">Enable Threat Intelligence</h3>
                    <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6; margin-bottom:20px;">
                        Connect your physical OSbal stack node to OSecure's centralized threat intelligence network to unlock automated, agentic threat mitigation.
                    </p>
                    
                    <ul style="color:var(--text-muted); font-size:0.9rem; line-height:1.6; padding-left:20px; margin-bottom:25px;">
                        <li style="margin-bottom:10px;"><strong style="color:#fff;">Dynamic Threat Lists:</strong> Instantly sync hard blocks and rate-throttling rules evaluated by the cloud engine.</li>
                        <li style="margin-bottom:10px;"><strong style="color:#fff;">Cloud Telemetry:</strong> View aggregated traffic trends, request graphs, and real-time security events in the OSecure dashboard portal.</li>
                        <li style="margin-bottom:10px;"><strong style="color:#fff;">Zero-Touch Maintenance:</strong> No manual IP updating required—threat feeds are kept up to date outbound automatically.</li>
                    </ul>

                    <form method="POST" action="osecure.php">
                        <input type="hidden" name="action" value="update_osecure_settings">
                        <input type="hidden" name="osecure_enabled" value="1">
                        <input type="hidden" name="license_key" value="OSecure-NEW-LICENSE-KEY">
                        <input type="hidden" name="server_url" value="http://localhost:8000">
                        <input type="hidden" name="share_metrics" value="1">
                        
                        <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:20px;">
                            Don't have an appliance key? Register your load balancer at <a href="#" style="color:var(--primary); font-weight:600; text-decoration:none;">osecure.dev</a> to claim your free key.
                        </div>

                        <button class="btn btn-primary" type="submit" style="width:100%; background:var(--primary); border-color:var(--primary); box-shadow: 0 4px 15px rgba(155, 81, 224, 0.4); font-weight:600; padding:12px 20px;">
                            Enable OSecure Threat Guard
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Active Settings Form -->
                <div class="card-glass" style="padding: 25px; border-radius: 12px; border: 1px solid rgba(155, 81, 224, 0.3); background: rgba(15, 23, 42, 0.45);">
                    <h3 style="margin-top:0; margin-bottom:15px; color:#fff; font-weight:600;">Active Connection Settings</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:25px;">
                        This node is linked and syncing threat definitions. Fill in your keys below to modify integration paths.
                    </p>

                    <form method="POST" action="osecure.php">
                        <input type="hidden" name="action" value="update_osecure_settings">
                        <input type="hidden" name="osecure_enabled" value="1">

                        <div class="form-group" style="margin-bottom:20px;">
                            <label class="form-label" for="license_key" style="font-weight:600; margin-bottom:8px;">OSecure Appliance Key</label>
                            <input type="text" id="license_key" class="form-control" name="license_key" value="<?php echo htmlspecialchars($osecure['license_key']); ?>" placeholder="e.g. OSecure-XXXX-XXXX-XXXX" required style="padding: 10px 12px; font-family:monospace; background: rgba(0,0,0,0.25);">
                        </div>

                        <div class="form-group" style="margin-bottom:20px;">
                            <label class="form-label" for="server_url" style="font-weight:600; margin-bottom:8px;">OSecure Central Server URL</label>
                            <input type="text" id="server_url" class="form-control" name="server_url" value="<?php echo htmlspecialchars($osecure['server_url']); ?>" placeholder="https://osecure-central.com" required style="padding: 10px 12px; background: rgba(0,0,0,0.25);">
                        </div>

                        <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:25px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 15px; border-radius: 8px;">
                            <label class="form-label" style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:0.9rem; margin:0;">
                                <input type="checkbox" name="share_metrics" value="1" <?php echo isset($osecure['share_metrics']) && $osecure['share_metrics'] ? 'checked' : ''; ?> style="width:16px; height:16px; accent-color:var(--primary);">
                                Share aggregated traffic and latency metrics
                            </label>
                            <span style="font-size:0.8rem; color:var(--text-muted); padding-left:26px; line-height:1.4;">
                                Transmits anonymous request metrics so OSecure can calculate traffic Z-scores and run automated threat detection modeling.
                            </span>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color); padding-top:20px;">
                            <span style="font-size:0.85rem; color:var(--text-muted);">
                                Last Sync: <strong style="color:#fff;"><?php echo htmlspecialchars($osecure['last_sync']); ?></strong>
                            </span>
                            <button class="btn btn-primary" type="submit" style="background:var(--primary); border-color:var(--primary); font-weight:600;">
                                Save Settings
                            </button>
                        </div>
                    </form>
                    
                    <form method="POST" action="osecure.php" style="margin-top:20px; border-top:1px solid var(--border-color); padding-top:15px; display:flex; justify-content:flex-end;">
                        <input type="hidden" name="action" value="update_osecure_settings">
                        <!-- Leave osecure_enabled out of POST to disable -->
                        <input type="hidden" name="license_key" value="<?php echo htmlspecialchars($osecure['license_key']); ?>">
                        <input type="hidden" name="server_url" value="<?php echo htmlspecialchars($osecure['server_url']); ?>">
                        <input type="hidden" name="share_metrics" value="<?php echo $osecure['share_metrics'] ? '1' : '0'; ?>">
                        <button class="btn" type="submit" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); color: var(--danger); font-size:0.85rem; padding: 6px 14px; border-radius: 8px; font-weight:600; cursor:pointer;">
                            Disable OSecure Integration
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: Gemini AI Educational / Value Explanation -->
        <div>
            <div class="card-glass" style="padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); background: rgba(15, 23, 42, 0.25); height:100%; display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <h3 style="margin-top:0; margin-bottom:20px; color:#fff; display:flex; align-items:center; gap:8px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        Why Agentic AI Threat Mitigation?
                    </h3>
                    
                    <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6; margin-bottom:25px;">
                        Traditional web security depends on **static signature files** and **hardcoded local rules**. These are fragile, easily bypassed, and create massive administration overhead. OSecure uses Google Gemini models and cloud analytics to defend your application proactively.
                    </p>

                    <!-- Feature comparison card -->
                    <div style="background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 25px;">
                        <h4 style="color:#fff; margin-top:0; margin-bottom:12px; font-size:0.95rem; text-transform:uppercase; letter-spacing:0.5px;">Static Rules vs. OSecure Gemini AI</h4>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; font-size:0.85rem;">
                            <!-- Static Rules Column -->
                            <div style="border-right: 1px solid var(--border-color); padding-right:15px;">
                                <div style="color:var(--danger); font-weight:600; margin-bottom:8px; display:flex; align-items:center; gap:4px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    Static Local Rules
                                </div>
                                <ul style="padding-left:14px; margin:0; color:var(--text-muted); line-height:1.4;">
                                    <li>Blocks exact IPs or regex matches only</li>
                                    <li>Highly prone to blocking legitimate users (false positives)</li>
                                    <li>Completely blind to distributed scans or slow rate attacks</li>
                                </ul>
                            </div>
                            
                            <!-- Gemini AI Column -->
                            <div>
                                <div style="color:var(--primary); font-weight:600; margin-bottom:8px; display:flex; align-items:center; gap:4px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    OSecure Agentic AI
                                </div>
                                <ul style="padding-left:14px; margin:0; color:var(--text-muted); line-height:1.4;">
                                    <li>Identifies threats by correlation and behavior over time</li>
                                    <li>LLM triages request intent to protect true customers</li>
                                    <li>Throttles suspicious traffic instead of breaking connections</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Tech breakdowns -->
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <div>
                            <h4 style="color:#fff; margin:0 0 6px 0; font-size:0.95rem; display:flex; align-items:center; gap:6px;">
                                <span style="display:inline-block; width:6px; height:6px; background:var(--primary); border-radius:50%;"></span>
                                Time-Series Z-Score Anomaly Detection
                            </h4>
                            <p style="color:var(--text-muted); font-size:0.85rem; line-height:1.5; margin:0; padding-left:12px;">
                                By measuring request volume deviations over rolling windows, the anomaly engine computes statistical Z-scores. It spots coordinated, distributed sweeps that standard firewalls fail to see.
                            </p>
                        </div>
                        
                        <div>
                            <h4 style="color:#fff; margin:0 0 6px 0; font-size:0.95rem; display:flex; align-items:center; gap:6px;">
                                <span style="display:inline-block; width:6px; height:6px; background:var(--primary); border-radius:50%;"></span>
                                Context-Aware LLM Triage
                            </h4>
                            <p style="color:var(--text-muted); font-size:0.85rem; line-height:1.5; margin:0; padding-left:12px;">
                                When unusual behavior is detected, traffic logs are triaged using a Gemini LLM agent. Gemini evaluates factors like header structure, path sequence, and payload content to judge if the traffic represents a real scanner or a developer running integrations.
                            </p>
                        </div>

                        <div>
                            <h4 style="color:#fff; margin:0 0 6px 0; font-size:0.95rem; display:flex; align-items:center; gap:6px;">
                                <span style="display:inline-block; width:6px; height:6px; background:var(--primary); border-radius:50%;"></span>
                                Dual-Mode Mitigation (Block vs. Throttle)
                            </h4>
                            <p style="color:var(--text-muted); font-size:0.85rem; line-height:1.5; margin:0; padding-left:12px;">
                                Instead of only blocking, OSecure supports dual responses: **suspicious** traffic is rate-throttled (using tarpit delays/WAF rate limits) to safely test the user's client, while **malicious** traffic is instantly dropped to preserve your backend's capacity.
                            </p>
                        </div>
                    </div>
                </div>

                <div style="margin-top:30px; border-top:1px solid var(--border-color); padding-top:15px; font-size:0.8rem; color:var(--text-muted); line-height:1.4;">
                    <strong>Security Decoupling Guarantee:</strong> OSbal works completely independently. If OSecure is disabled or offline, OSbal continues handling all local routing and static IP rules with zero disruption.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>
