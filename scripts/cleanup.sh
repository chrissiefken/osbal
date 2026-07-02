#!/usr/bin/env bash
# OSbal Production Environment Cleanup & Hardening Script
# This script secures the server after initial admin configuration is completed.

# Move to the project root directory
cd "$(dirname "$0")/.." || exit 1
PROJECT_ROOT="$(pwd)"

echo "========================================================="
echo "    OSbal Production Environment Cleanup & Hardening     "
echo "========================================================="

# 1. Verify that the configuration is actually initialized
CONFIG_DIR="${PROJECT_ROOT}/config"
if [ ! -f "${CONFIG_DIR}/adminIp.json" ] && [ ! -f "/usr/local/osbal/config/adminIp.json" ]; then
    echo "WARNING: OSbal has not been configured via the web setup wizard yet."
    echo "Please run the setup wizard at http://your-server-ip/install.php first."
    read -p "Are you sure you want to proceed with hardening anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Cleanup aborted."
        exit 1
    fi
fi

echo "Removing non-essential files..."

# 2. Hardening installation wizard with a secure placeholder
if [ -f "${PROJECT_ROOT}/install.xml" ]; then
    rm -f "${PROJECT_ROOT}/install.xml"
fi

if [ -f "${PROJECT_ROOT}/install.php" ]; then
    echo " - Replacing install.php with a secure instructions placeholder..."
    cat << 'EOF' > "${PROJECT_ROOT}/install.php"
<?php
// Secure placeholder to prevent administrative account override attacks
header('HTTP/1.1 403 Forbidden');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Locked - OSbal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #060913;
            --card-bg: rgba(13, 20, 38, 0.45);
            --primary: #9b51e0;
            --text-muted: #8a99ad;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(at 0% 0%, rgba(155, 81, 224, 0.08) 0px, transparent 50%), radial-gradient(at 100% 0%, rgba(0, 242, 254, 0.05) 0px, transparent 50%);
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .card-lock {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            padding: 40px;
            border-radius: 16px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .code-box {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            padding: 12px 16px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #00f2fe;
            text-align: left;
            margin-bottom: 15px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .btn-back {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(155, 81, 224, 0.3);
            margin-top: 10px;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(155, 81, 224, 0.45);
        }
    </style>
</head>
<body>
    <div class="card-lock">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9b51e0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:10px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        <h2>Installation Wizard Locked</h2>
        <p>This appliance has already been initialized and secured. For security reasons, the active installation logic has been disabled to prevent administrative override attacks.</p>
        
        <div style="text-align:left; margin-bottom: 20px;">
            <strong style="font-size:0.9rem; display:block; margin-bottom:8px; color:#fff;">To perform a clean reinstall:</strong>
            <div class="code-box">sudo ./scripts/deploy.sh --fresh</div>
            
            <strong style="font-size:0.9rem; display:block; margin-bottom:8px; color:#fff;">To keep dev tools & preserve the wizard file:</strong>
            <div class="code-box">sudo ./scripts/deploy.sh --no-cleanup</div>
        </div>

        <a href="/index.php" class="btn-back">Go to Dashboard</a>
    </div>
</body>
</html>
EOF
fi

if [ -f "${PROJECT_ROOT}/api/createUser.php" ]; then
    echo " - Deleting api/createUser.php (prevent unauthorized account overwrites)..."
    rm -f "${PROJECT_ROOT}/api/createUser.php"
fi

# 3. Remove developer testing directories and local verification scripts
if [ -d "${PROJECT_ROOT}/tests" ]; then
    echo " - Deleting tests/ folder..."
    rm -rf "${PROJECT_ROOT}/tests"
fi

if [ -d "${PROJECT_ROOT}/docs" ]; then
    echo " - Deleting docs/ folder (removing local marketing site)..."
    rm -rf "${PROJECT_ROOT}/docs"
fi

# 4. Remove Git history and metadata to prevent structural exposure
echo " - Removing Git repository files (.git, .gitignore, README.md)..."
rm -rf "${PROJECT_ROOT}/.git"
rm -f "${PROJECT_ROOT}/.gitignore"
rm -f "${PROJECT_ROOT}/.gitattributes"
rm -f "${PROJECT_ROOT}/README.md"

# 5. Remove backup files and local logs caches
echo " - Cleaning temp/backup caches..."
find "${PROJECT_ROOT}" -name "*.bak" -type f -delete
find "${PROJECT_ROOT}" -name "*.log" -type f -delete

# 6. Reset system log streams to fresh state
if [ -f "/var/log/osbal/system_events.log" ]; then
    echo " - Clearing production system events log..."
    echo -n "" > "/var/log/osbal/system_events.log"
fi
if [ -d "${PROJECT_ROOT}/logs" ]; then
    echo " - Deleting development logs/ folder..."
    rm -rf "${PROJECT_ROOT}/logs"
fi

# 7. Generate Production Hardening .htaccess configuration
echo " - Generating production-hardening .htaccess file..."
cat << 'EOF' > "${PROJECT_ROOT}/.htaccess"
# Disable directory indexes
Options -Indexes

# Deny direct HTTP access to sensitive config files, databases, and scripts
<FilesMatch "\.(json|lst|log|sh|bak)$">
    Require all denied
</FilesMatch>

# Block PHP error output displaying on screen
php_flag display_errors off
php_value error_reporting 2147483647
EOF
chmod 644 "${PROJECT_ROOT}/.htaccess"

# 8. Set secure file permissions on production settings folders
echo "Applying secure permission lock..."
if [ -d "/usr/local/osbal/config" ]; then
    # Production directory permissions
    chmod 750 /usr/local/osbal/config
    chmod 640 /usr/local/osbal/config/*.json 2>/dev/null || true
else
    chmod 750 "${CONFIG_DIR}"
    chmod 640 "${CONFIG_DIR}"/*.json 2>/dev/null || true
fi

echo "---------------------------------------------------------"
echo "SUCCESS: Production environment hardened."
echo "Wizard endpoints, git metadata, and local docs deleted."
echo "HTACCESS rules written. Directory permission locks set."
echo "---------------------------------------------------------"
echo "RECOMMENDED: Restrict access to this admin GUI (port 80/443) to secure"
echo "private networks (VPN/VPC) only. Do not expose this panel to the public WAN."
echo "========================================================="
exit 0
