#!/bin/bash

# OSBal (Open Source Loadbalancer Stack) Installer & Upgrader
# Targets: Debian, Ubuntu, Raspberry Pi OS (Raspbian)
set -e

# Parse command line options
FORCE_FRESH=0
SKIP_CLEANUP=0

while [[ "$#" -gt 0 ]]; do
  case $1 in
    -f|--fresh) FORCE_FRESH=1 ;;
    --no-cleanup) SKIP_CLEANUP=1 ;;
    -h|--help)
      echo "Usage: $0 [options]"
      echo "Options:"
      echo "  -f, --fresh     Force a clean setup installation (ignores existing configurations)"
      echo "  --no-cleanup    Skip running the automatic production hardening/cleanup script"
      echo "  -h, --help      Display this help menu"
      exit 0
      ;;
    *)
      echo "Unknown parameter passed: $1"
      exit 1
      ;;
  esac
  shift
done

# Detect existing installation
IS_UPGRADE=0
if [ "$FORCE_FRESH" -eq 0 ]; then
  if [ -d /usr/local/osbal/config ] || [ -f /var/www/html/lib/global-settings.php ]; then
    IS_UPGRADE=1
  fi
fi

# Clear terminal screen and render banner
clear
echo -e "\033[1;36m=====================================================\033[0m"
if [ "$IS_UPGRADE" -eq 1 ]; then
  echo -e "\033[1;36m           OSBal Appliance Upgrade Wizard            \033[0m"
else
  echo -e "\033[1;36m           OSBal Appliance Setup Wizard              \033[0m"
fi
echo -e "\033[1;36m=====================================================\033[0m"
if [ "$IS_UPGRADE" -eq 1 ]; then
  echo -e "Upgrading OSBal highly available load balancer stack...\n"
else
  echo -e "Installing open-source highly available load balancer stack...\n"
fi

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
if [ "$IS_UPGRADE" -eq 1 ]; then
  echo -e "\n\033[1;33m[2/6] Backing up configs and upgrading files...\033[0m"
else
  echo -e "\n\033[1;33m[2/6] Deploying OSBal web interface files...\033[0m"
fi

if [ -f /var/www/html/index.html ]; then
  echo "Backing up default index.html..."
  mv /var/www/html/index.html /var/www/html/index.html.bak || true
fi

# Backup configuration files if upgrading
BACKUP_DIR=""
if [ "$IS_UPGRADE" -eq 1 ]; then
  BACKUP_DIR=$(mktemp -d)
  echo "Backing up existing configurations to $BACKUP_DIR..."
  if [ -d /usr/local/osbal/config ]; then
    cp -r /usr/local/osbal/config "$BACKUP_DIR/local_config" || true
  fi
  if [ -d /var/www/html/config ]; then
    cp -r /var/www/html/config "$BACKUP_DIR/web_config" || true
  fi
fi

# Clone repository to temp location and copy to document root
TMP_DIR=$(mktemp -d)
echo "Downloading latest codebase files..."
git clone https://github.com/chrissiefken/osbal.git "$TMP_DIR"

if [ "$IS_UPGRADE" -eq 1 ]; then
  echo "Removing old application files..."
  # Clean old web files except config folder to prevent pollution
  find /var/www/html/ -mindepth 1 -maxdepth 1 ! -name 'config' -exec rm -rf {} +
fi

cp -r "$TMP_DIR"/* /var/www/html/
rm -rf "$TMP_DIR"

# Restore configurations
if [ "$IS_UPGRADE" -eq 1 ] && [ -n "$BACKUP_DIR" ]; then
  echo "Restoring configurations..."
  if [ -d "$BACKUP_DIR/local_config" ]; then
    mkdir -p /usr/local/osbal/config
    cp -r "$BACKUP_DIR/local_config"/* /usr/local/osbal/config/ || true
  fi
  if [ -d "$BACKUP_DIR/web_config" ]; then
    mkdir -p /var/www/html/config
    cp -r "$BACKUP_DIR/web_config"/* /var/www/html/config/ || true
  fi
  rm -rf "$BACKUP_DIR"
fi

# 3. Create config paths, logs paths, and establish write permissions
echo -e "\n\033[1;33m[3/6] Initializing configuration files & permissions...\033[0m"
mkdir -p /usr/local/osbal/config
mkdir -p /etc/stunnel/certs
mkdir -p /var/log/osbal

# Enable Stunnel daemon startup in Debian/Ubuntu config
if [ -f /etc/default/stunnel4 ]; then
  if grep -q "ENABLED=" /etc/default/stunnel4; then
    sed -i 's/ENABLED=0/ENABLED=1/g' /etc/default/stunnel4
  else
    echo "ENABLED=1" >> /etc/default/stunnel4
  fi
fi

# Ensure core files exist before assigning permissions
touch /etc/haproxy/haproxy.cfg
touch /etc/keepalived/keepalived.conf
touch /etc/stunnel/stunnel.conf

# Grant file write permissions to the web server user (www-data)
chown -R www-data:www-data /usr/local/osbal/config
chown -R www-data:www-data /etc/stunnel/certs
chown -R www-data:www-data /var/log/osbal
chmod 750 /var/log/osbal
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

# Enable and start daemon services
systemctl enable haproxy || true
systemctl enable keepalived || true
systemctl enable stunnel4 || true

if [ "$IS_UPGRADE" -eq 1 ]; then
  echo "Hot-reloading active load-balancer services..."
  systemctl reload haproxy || true
  systemctl reload keepalived || true
  systemctl restart stunnel4 || true
  
  if [ "$SKIP_CLEANUP" -eq 0 ] && [ -f /var/www/html/scripts/cleanup.sh ]; then
    echo "Running production hardening cleanup..."
    bash /var/www/html/scripts/cleanup.sh
  fi
else
  # On first-time install, start them up to initialize active states
  systemctl restart haproxy || true
  systemctl restart keepalived || true
  systemctl restart stunnel4 || true
fi

# 6. Display complete status message with browser link
echo -e "\n\033[1;36m=====================================================\033[0m"
if [ "$IS_UPGRADE" -eq 1 ]; then
  echo -e "\033[1;32m      ✓ Upgrade Complete! OSBal is updated.          \033[0m"
else
  echo -e "\033[1;32m      ✓ Setup Complete! OSBal is ready.              \033[0m"
fi
echo -e "\033[1;36m=====================================================\033[0m"

# Get current server IP address dynamically
IP_ADDR=$(hostname -I | awk '{print $1}')
if [ -z "$IP_ADDR" ]; then
  IP_ADDR="localhost"
fi

echo -e "Access the web manager dashboard to complete setup:"
echo -e "\033[1;34mhttp://$IP_ADDR/\033[0m\n"
