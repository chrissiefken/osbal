# OSBal (Open Source Loadbalancer)

OSBal is a modernized, open-source load balancer stack and configuration manager. It provides a premium web-based GUI to configure and manage a highly available layer 7 network appliance running **HAProxy**, **Keepalived**, and **Stunnel4** on physical bare-metal servers, virtual machines, or low-cost hardware like the **Raspberry Pi**.

---

## Core Capabilities

* **Commercial-Grade Alternative**: A free alternative to closed-source hardware load balancers and virtual appliances.
* **Web-Based Management**: Configure load balancer routing, SSL termination, and high-availability failover directly from your web browser.
* **Low Footprint**: Runs comfortably on minimal hardware (e.g. Raspberry Pi with 1GB to 2GB RAM).
* **Native Web Application Firewall (WAF)**: Block SQL injections, Cross-Site Scripting (XSS), and rate-limit abusers natively in HAProxy.
* **Real-time Traffic Stats**: Dynamic charting, access log terminals, and simulated stress testing built right into the interface.
* **Optional OSecure AI Sidecar**: Deep API analysis, Gemini-driven request triage, and cloud WAF sync (AWS WAF/GCP Armor) using a non-blocking asynchronous daemon sidecar.

---

## Quick Start (Run & Test Instantly)

You can run and test the web interface locally on your development machine using PHP's built-in webserver:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/chrissiefken/osbal.git
   cd osbal
   ```
2. **Spin up the server**:
   ```bash
   php -S localhost:8080
   ```
3. **Access the GUI**: Open your browser and navigate to `http://localhost:8080/`.

> [!NOTE]
> **Development Fallback Mode**: When running locally without root privileges, OSBal operates in sandbox mode. It writes credentials, lists, and compiled HAProxy configs to a local `osbal/config/` directory instead of system `/etc/` paths, allowing you to test without permission conflicts.

---

## Appliance Production Setup & Upgrades (Ubuntu & Raspberry Pi)

To turn a physical Ubuntu or Raspberry Pi OS server into a dedicated load balancer, or to upgrade an existing deployment, you can use our single-line deployment script.

### Recommended: Automated Installation & Upgrades

The deployment script automatically installs packages, clones the code, configures file permissions, and sets up passwordless service reloading.

Run the following command in your terminal:
```bash
curl -sSL https://raw.githubusercontent.com/chrissiefken/osbal/master/scripts/deploy.sh | sudo bash
```

#### Installer Script Options
You can customize the installer/upgrader behavior by passing flags directly to the script:
* **`-f`, `--fresh`**: Forces a clean installation. It ignores any pre-existing configurations (skips backup restoration) and leaves setup wizard endpoints intact so you can reconfigure the server from scratch.
* **`--no-cleanup`**: Upgrades the codebase but bypasses the automatic running of the production hardening script. This preserves setup wizard files, git metadata, and testing suites (recommended for development environments).

```bash
# Example: Running a forced clean setup
sudo ./scripts/deploy.sh --fresh
```

> [!TIP]
> **Seamless Upgrades**: Running this command without flags on an existing OSBal appliance automatically detects the current installation, backups your configurations, cleans code directories, deploys the latest version, restores your settings without downtime, and automatically runs the production cleanup script to keep your server hardened.

---

### Advanced: Manual Installation

If you prefer to configure the appliance manually, execute the following commands as root:

#### Step 1: Install System Packages
```bash
sudo apt-get update
sudo apt-get install -y haproxy stunnel4 keepalived apache2 php php-cli php-json git
```

#### Step 2: Deploy OSBal Web Files
```bash
# Clear default files
sudo rm -rf /var/www/html/*

# Clone the code into place
git clone https://github.com/chrissiefken/osbal.git /tmp/osbal
sudo cp -r /tmp/osbal/* /var/www/html/

# Set ownership to Apache's web user
sudo chown -R www-data:www-data /var/www/html/
```

#### Step 3: Configure File Permissions & Sudo Access
Allow the OSBal PHP backend (`www-data` user) to edit configuration files and reload/restart services without requiring a root password.

1. **Set file ownership**: Create config directories, enable Stunnel4, and assign permissions:
   ```bash
   sudo mkdir -p /usr/local/osbal/config /etc/stunnel/certs
   sudo touch /etc/haproxy/haproxy.cfg /etc/keepalived/keepalived.conf /etc/stunnel/stunnel.conf
   
   # Enable Stunnel4 daemon startup (Debian/Ubuntu specific)
   sudo sed -i 's/ENABLED=0/ENABLED=1/g' /etc/default/stunnel4 || true
   
   sudo chown -R www-data:www-data /usr/local/osbal/config /etc/stunnel/certs /etc/haproxy/haproxy.cfg /etc/keepalived/keepalived.conf /etc/stunnel/stunnel.conf
   ```

2. **Grant sudo privileges for service controls**: Run `sudo visudo` and append the following lines to the bottom of the file:
   ```sudoers
   www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload haproxy
   www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload keepalived
   www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart stunnel4
   ```

#### Step 4: Run the Setup Wizard
Point your web browser to the server's IP address (e.g. `http://192.168.1.100/`) and complete the 3-step setup to create your credentials and network interfaces.

#### Step 5: Production Cleanup & Hardening
After completing the setup wizard, secure your host by running the production cleanup script. This deletes the setup wizard files, deletes the credentials creation endpoint (`api/createUser.php`), clears the local `docs/` directory, strips Git history, writes a secure `.htaccess` file, and tightens configuration database permissions:
```bash
./scripts/cleanup.sh
```

> [!WARNING]
> **Administrative Panel Private Isolation**
> OSbal's web administration panel grants full control over network services and configs. **Never expose the admin panel (port 80/443 of this host) to the public WAN.** Always restrict management traffic to a trusted private network (VPC, local subnet) or a secure management VPN (e.g. WireGuard, Tailscale).

---

## WAF & Realtime Stats Guides

### Using the Web Application Firewall (WAF)
1. **Enable Shields**: On the **Create Service** or **Load Balancer** page, click the WAF checkbox.
2. **Select Protections & Mitigation Action**:
   * **Block SQLi**: Filters requests containing SQL injection keywords (`UNION`, `SELECT`, `DROP`, etc.).
   * **Block XSS**: Filters requests containing HTML script tags or JavaScript event handlers (`<script>`, `onerror`, `onload`, etc.).
   * **Mitigation Action**: Choose how to handle detected violations. Select **Deny Access** (instantly return HTTP 403 Forbidden) or **Tarpit** (delay the connection response to tie up client resources).
   * **Tarpit Delay**: Set how long (1-60s) HAProxy holds the connection open when a violation occurs, slowing down brute-force scanner scripts.
3. **IP Blacklist**: Add malicious IP addresses line-by-line to the **Global IP Access Blacklist** form at the bottom of the load balancer settings page to block them globally.

### Exploring the Real-Time Monitor
Go to the **Realtime Stats** page to inspect network operations:
* **Load Simulator**: Press **Low**, **Medium**, or **High** to mock traffic levels and watch connections, latency, and request rates react.
* **Access Log Terminal**: Displays standard log entries mapping incoming IPs to destination backend nodes.
* **DDoS Attack Test**: Press the **DDoS** button to simulate a high-volume request flood. You will see WAF rate-limiting rules engage in real time, appending red blocking alerts to the log terminal and counting up WAF-blocked request metrics.

---

## Optional Security Integration: OSecure AI

OSbal supports optional integration with **OSecure** for advanced AI security capabilities. The integration is **100% decoupled and optional**:
* **Zero Latency Impact**: OSecure runs as a separate background sidecar daemon (`osecure-agentd`) on the load balancer host. It analyzes request events asynchronously (via log tailing), preventing any inline blocking overhead or request routing latency.
* **Key Features**:
  * Statistical Z-score calculations to identify volumetric egress exfiltration leaks.
  * Request payload triage using Google Gemini to identify complex, zero-day threat patterns.
  * Cloud WAF Synchronization: Automatically compiles and pushes dynamically triaged IP bans and rate-limits to your edge WAFs (AWS WAF and GCP Cloud Armor).
* **Enablement**: Toggle the integration under **Settings > OSecure**, enter your license key, and the OSbal setup script will automatically handle downloading, verifying, and launching the background agent daemon.

---

## Verification & Optionality Tests

To maintain transparency with the open-source community, OSbal includes automated verification tests. These verify that the load balancer compiles standard configurations without external dependencies and that the OSecure integration is 100% optional and decoupled.

Run the test suite locally using our shell test runner:
```bash
./scripts/run-tests.sh
```

This script executes the assertions in `tests/verify-optionality.php`, validating default configs, failsafe operations, and HAProxy syntax integrity checks.
