<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/lib/check.php';
$results = checkStatus();

$error_count = 0;
$list = '<div class="list-group" style="margin-bottom: 24px;">';

foreach($results as $result) {
    if ($result['error'] == 1 && $result['type'] == 'package') {
        $badge = '<span class="badge badge-danger">Missing</span>';
        $error_count += 1;
    } else if ($result['error'] == 0 && $result['type'] == 'config') {
        $badge = '<span class="badge badge-success">Configured</span>';
    } else {
        $badge = '<span class="badge badge-success">Ready</span>';
    }
    $list .= '
        <div class="list-item" style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight: 500;">' . htmlspecialchars($result['message']) . '</div>
            ' . $badge . '
        </div>';
}
$list .= '</div>';

if ($error_count != 0) {
    $alert = '
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
            <strong>Missing Dependencies!</strong> Please install the required packages on your system and refresh this page.
        </div>
    ';
    $next_disabled = 'disabled';
} else {
    $alert = '
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
            <strong>System Checked!</strong> All required packages are installed and ready.
        </div>
    ';
    $next_disabled = '';
}
?>

<div class="card-glass" style="max-width: 650px; margin: 0 auto;">
    <!-- Wizard Steps Indicator -->
    <div style="display:flex; justify-content:space-between; margin-bottom: 40px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px;">
        <div class="step-indicator active-step" id="ind-1" style="font-weight:600; color: var(--accent);">1. System Check</div>
        <div class="step-indicator" id="ind-2" style="font-weight:500; color: var(--text-muted);">2. Configuration</div>
        <div class="step-indicator" id="ind-3" style="font-weight:500; color: var(--text-muted);">3. Complete</div>
    </div>

    <!-- Step 1: System Check -->
    <div id="step-1">
        <h2>Appliance Environment Check</h2>
        <p>OSBal requires several native utilities to be installed on your Ubuntu / Raspberry Pi host system to manage the load balancer.</p>
        
        <?php echo $list; ?>
        <?php echo $alert; ?>
        
        <div style="display:flex; justify-content:flex-end; margin-top:30px;">
            <button id="to-step-2" class="btn btn-primary" <?php echo $next_disabled; ?>>
                Next: Configure Appliance
            </button>
        </div>
    </div>

    <!-- Step 2: Settings Configuration -->
    <div id="step-2" style="display:none;">
        <h2>Initialize Settings</h2>
        <p>Set up your administrator credentials and primary network configuration for this load balancer.</p>
        
        <form id="config-form" style="margin-top: 24px;">
            <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 16px; color: var(--accent);">Admin Account</h4>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="userName">Administrator Username</label>
                    <input type="text" class="form-control" id="userName" value="admin" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="inputPassword">Account Password</label>
                    <input type="password" class="form-control" id="inputPassword" placeholder="Enter password" required>
                </div>
            </div>

            <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 16px; margin-top: 24px; color: var(--accent);">Appliance Network</h4>
            
            <div class="form-group">
                <label class="form-label">Network Management Mode</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="networkMode" id="net-override" value="override" checked>
                        Configure custom management IP (replaces default OS interfaces)
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="networkMode" id="net-keep" value="keep">
                        Use current operating system network settings (recommended for VPC/Raspberry Pi)
                    </label>
                </div>
            </div>

            <div id="network-fields" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 20px; border-radius: 12px; margin-bottom: 24px;">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="ip">Management IP Address</label>
                        <input type="text" class="form-control" id="ip" value="<?php echo isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '192.168.1.100'; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="subnet">Subnet Mask</label>
                        <input type="text" class="form-control" id="subnet" placeholder="255.255.255.0" value="255.255.255.0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="gateway">Default Gateway</label>
                        <input type="text" class="form-control" id="gateway" placeholder="192.168.1.1" value="192.168.1.1">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="name">Friendly Hostname</label>
                        <input type="text" class="form-control" id="name" value="<?php echo isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'osbal-node1'; ?>">
                    </div>
                </div>
            </div>
        </form>

        <div style="display:flex; justify-content:space-between; margin-top:30px;">
            <button id="back-to-1" class="btn btn-secondary">Back</button>
            <button id="to-step-3" class="btn btn-primary">Next: Finalize</button>
        </div>
    </div>

    <!-- Step 3: Confirmation -->
    <div id="step-3" style="display:none;">
        <h2>Ready to Deploy!</h2>
        <p>We are ready to write config directories, encrypt credentials, and initialize HAProxy/Keepalived configurations.</p>
        
        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 24px; border-radius: 12px; margin: 30px 0;">
            <h4 style="color: var(--accent); margin-bottom: 12px;">Configuration Summary:</h4>
            <div style="display:grid; grid-template-columns: 150px 1fr; gap: 8px; font-size: 0.95rem;">
                <div style="color: var(--text-muted);">Admin Username:</div>
                <div id="summary-user" style="font-weight: 500;"></div>
                <div style="color: var(--text-muted);">Hostname:</div>
                <div id="summary-hostname" style="font-weight: 500;"></div>
                <div style="color: var(--text-muted);">IP / Subnet:</div>
                <div id="summary-ip" style="font-weight: 500;"></div>
            </div>
        </div>

        <div id="install-loading" style="display:none; text-align:center; margin-bottom: 24px;">
            <div style="display:inline-block; border: 3px solid rgba(255,255,255,0.1); border-radius: 50%; border-top: 3px solid var(--accent); width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 10px;"></div>
            <style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
            <div style="font-size:0.9rem; color: var(--accent);">Applying configurations & writing service configs...</div>
        </div>

        <div id="install-error" style="display:none; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
        </div>

        <div style="display:flex; justify-content:space-between; margin-top:30px;">
            <button id="back-to-2" class="btn btn-secondary">Back</button>
            <button id="btn-apply" class="btn btn-primary" style="background: linear-gradient(135deg, var(--success) 0%, #059669 100%); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                Apply Settings & Initialize
            </button>
        </div>
    </div>
</div>

<script>
$(function() {
    // Handle step toggles
    $('#to-step-2').click(function() {
        $('#step-1').hide();
        $('#step-2').show();
        $('.step-indicator').css('color', 'var(--text-muted)').css('font-weight', '500');
        $('#ind-2').css('color', 'var(--accent)').css('font-weight', '600');
    });

    $('#back-to-1').click(function() {
        $('#step-2').hide();
        $('#step-1').show();
        $('.step-indicator').css('color', 'var(--text-muted)').css('font-weight', '500');
        $('#ind-1').css('color', 'var(--accent)').css('font-weight', '600');
    });

    $('#to-step-3').click(function() {
        // Validation check
        if ($('#userName').val().trim() === '' || $('#inputPassword').val() === '') {
            alert('Please fill out the administrator credentials.');
            return;
        }

        // populate summary
        $('#summary-user').text($('#userName').val());
        $('#summary-hostname').text($('#name').val());
        if ($('input[name="networkMode"]:checked').val() === 'override') {
            $('#summary-ip').text($('#ip').val() + ' / ' + $('#subnet').val());
        } else {
            $('#summary-ip').text('Using current OS settings');
        }

        $('#step-2').hide();
        $('#step-3').show();
        $('.step-indicator').css('color', 'var(--text-muted)').css('font-weight', '500');
        $('#ind-3').css('color', 'var(--accent)').css('font-weight', '600');
    });

    $('#back-to-2').click(function() {
        $('#step-3').hide();
        $('#step-2').show();
        $('.step-indicator').css('color', 'var(--text-muted)').css('font-weight', '500');
        $('#ind-2').css('color', 'var(--accent)').css('font-weight', '600');
    });

    // Toggle network fields
    $('input[name="networkMode"]').change(function() {
        if ($(this).val() === 'override') {
            $('#network-fields').slideDown();
        } else {
            $('#network-fields').slideUp();
        }
    });

    // Handle install submit
    $('#btn-apply').click(function() {
        $('#install-loading').show();
        $('#install-error').hide();
        $('#btn-apply').prop('disabled', true);
        
        var ipVal = $('#ip').val();
        var subnetVal = $('#subnet').val();
        var gatewayVal = $('#gateway').val();
        var nameVal = $('#name').val();

        // If keeping settings, pass current default placeholders
        if ($('input[name="networkMode"]:checked').val() === 'keep') {
            ipVal = '127.0.0.1';
            subnetVal = '255.255.255.255';
            gatewayVal = '127.0.0.1';
        }

        // Run both createUser and updateAdminIp via Ajax
        $.ajax({
            type: "POST",
            url: "/api/createUser.php",
            data: { 
                uname: $('#userName').val(),
                passwd: $('#inputPassword').val()
            }
        }).done(function(userRes) {
            if (userRes.success) {
                // Now save IP Settings
                $.ajax({
                    type: "POST",
                    url: "/api/updateAdminIp.php", 
                    data: { 
                        ip: ipVal,
                        subnet: subnetVal,
                        gateway: gatewayVal,
                        name: nameVal
                    }
                }).done(function(netRes) {
                    if (netRes.success) {
                        $('#install-loading').html('<span style="color:var(--success); font-weight:600;">✓ Installation complete! Redirecting to login...</span>');
                        setTimeout(function() {
                            window.location.href = '/index.php';
                        }, 1800);
                    } else {
                        showError(netRes.message);
                    }
                }).fail(function() {
                    showError('Failed to configure network settings endpoint.');
                });
            } else {
                showError(userRes.message);
            }
        }).fail(function() {
            showError('Failed to create user account endpoint.');
        });
    });

    function showError(msg) {
        $('#install-loading').hide();
        $('#install-error').text(msg).show();
        $('#btn-apply').prop('disabled', false);
    }
});
</script>

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/lib/footer.php';
?>