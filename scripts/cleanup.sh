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

# 3. Remove developer testing directories and local verification scripts
if [ -d "${PROJECT_ROOT}/tests" ]; then
    echo " - Deleting tests/ folder..."
    rm -rf "${PROJECT_ROOT}/tests"
fi

if [ -d "${PROJECT_ROOT}/docs" ]; then
    echo " - Deleting docs/ folder (removing local marketing site)..."
    rm -rf "${PROJECT_ROOT}/docs"
fi

# 4. Remove backup files and local logs caches
echo " - Cleaning temp/backup caches..."
find "${PROJECT_ROOT}" -name "*.bak" -type f -delete
find "${PROJECT_ROOT}" -name "*.log" -type f -delete

# 5. Set secure file permissions on production settings folders
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
echo "Removed install wizard files, docs, test suites, and secured permissions."
echo "---------------------------------------------------------"
echo "RECOMMENDED: Restrict access to this admin GUI (port 80/443) to secure"
echo "private networks (VPN/VPC) only. Do not expose this panel to the public WAN."
echo "========================================================="
exit 0
