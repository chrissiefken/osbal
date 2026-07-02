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

# 2. Remove the web installation wizard to prevent credential reset attacks
if [ -f "${PROJECT_ROOT}/install.xml" ]; then
    rm -f "${PROJECT_ROOT}/install.xml"
fi

if [ -f "${PROJECT_ROOT}/install.php" ]; then
    echo " - Deleting install.php (securer lock against setup hijack)..."
    rm -f "${PROJECT_ROOT}/install.php"
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
echo " - Removing Git repository files (.git, .gitignore, README.md, LICENSE)..."
rm -rf "${PROJECT_ROOT}/.git"
rm -f "${PROJECT_ROOT}/.gitignore"
rm -f "${PROJECT_ROOT}/.gitattributes"
rm -f "${PROJECT_ROOT}/README.md"
rm -f "${PROJECT_ROOT}/LICENSE"

# 5. Remove backup files and local logs caches
echo " - Cleaning temp/backup caches..."
find "${PROJECT_ROOT}" -name "*.bak" -type f -delete
find "${PROJECT_ROOT}" -name "*.log" -type f -delete

# 6. Reset system log streams to fresh state
if [ -f "${PROJECT_ROOT}/config/system_events.log" ]; then
    echo " - Clearing configuration log cache..."
    echo -n "" > "${PROJECT_ROOT}/config/system_events.log"
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
echo "Wizard endpoints, Git files, and docs deleted."
echo "HTACCESS rules written. Directory permission locks set."
echo "---------------------------------------------------------"
echo "RECOMMENDED: Restrict access to this admin GUI (port 80/443) to secure"
echo "private networks (VPN/VPC) only. Do not expose this panel to the public WAN."
echo "========================================================="
exit 0
