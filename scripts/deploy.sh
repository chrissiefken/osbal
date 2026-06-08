#!/bin/bash

# OSBal (Open Source Loadbalancer Stack) Installer
# Targets: Debian, Ubuntu, Raspberry Pi OS (Raspbian)
set -e

# Clear terminal screen and render banner
clear
echo -e "\033[1;36m=====================================================\033[0m"
echo -e "\033[1;36m           OSBal Appliance Setup Wizard              \033[0m"
echo -e "\033[1;36m=====================================================\033[0m"
echo -e "Installing open-source highly available load balancer stack...\n"

# Verify script is run as root/sudo
if [ "$EUID" -ne 0 ]; then
  echo -e "\033[1;31mError: This script must be run as root. Please run with sudo:\033[0m"
  echo -e "sudo bash $0"
  exit 1
fi

# 1. Install system package dependencies
echo -e "\033[1;33m[1/6] Installing system packages...\033[0m"
apt-get update
apt-get install -y haproxy stunnel4 keepalived apache2 php php-cli php-json git

# 2. Setup OSBal web directory files
echo -e "\n\033[1;33m[2/6] Deploying OSBal web interface files...\033[0m"
if [ -f /var/www/html/index.html ]; then
  echo "Backing up default index.html..."
  mv /var/www/html/index.html /var/www/html/index.html.bak
fi

# Clone repository to temp location and copy to document root
TMP_DIR=$(mktemp -d)
echo "Downloading codebase files..."
git clone https://github.com/siefkencp/osbal.git "$TMP_DIR"
cp -r "$TMP_DIR"/* /var/www/html/
rm -rf "$TMP_DIR"

# 3. Create config paths and establish write permissions
echo -e "\n\033[1;33m[3/6] Initializing configuration files & permissions...\033[0m"
mkdir -p /usr/local/osbal/config
mkdir -p /etc/stunnel/certs

# Ensure core files exist before assigning permissions
touch /etc/haproxy/haproxy.cfg
touch /etc/keepalived/keepalived.conf
touch /etc/stunnel/stunnel.conf

# Grant file write permissions to the web server user (www-data)
chown -R www-data:www-data /usr/local/osbal/config
chown -R www-data:www-data /etc/stunnel/certs
chown www-data:www-data /etc/haproxy/haproxy.cfg
chown www-data:www-data /etc/keepalived/keepalived.conf
chown www-data:www-data /etc/stunnel/stunnel.conf
chown -R www-data:www-data /var/www/html/

# 4. Configure passwordless sudo reload rules for daemons management
echo -e "\n\033[1;33m[4/6] Setting up sudoers service reload privileges...\033[0m"
SUDOERS_FILE="/etc/sudoers.d/osbal-web"
cat << 'EOF' > "$SUDOERS_FILE"
# Sudo privileges for OSBal load balancer controls
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload haproxy
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload keepalived
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart stunnel4
EOF
chmod 0440 "$SUDOERS_FILE"

# 5. Restart Apache web server to apply new environment states
echo -e "\n\033[1;33m[5/6] Starting services...\033[0m"
systemctl restart apache2
systemctl enable apache2

# 6. Display complete status message with browser link
echo -e "\n\033[1;36m=====================================================\033[0m"
echo -e "\033[1;32m      ✓ Setup Complete! OSBal is ready.              \033[0m"
echo -e "\033[1;36m=====================================================\033[0m"

# Get current server IP address dynamically
IP_ADDR=$(hostname -I | awk '{print $1}')
if [ -z "$IP_ADDR" ]; then
  IP_ADDR="localhost"
fi

echo -e "Access the web manager dashboard to complete setup:"
echo -e "\033[1;34mhttp://$IP_ADDR/\033[0m\n"
