# OSBal (Open Source Loadbalancer)

OSBal is a modernized, open-source load balancer stack and configuration manager. It provides a premium web-based GUI to configure and manage a highly available layer 7 network appliance running **HAProxy**, **Keepalived**, and **Stunnel4** on physical bare-metal servers, virtual machines, or low-cost hardware like the **Raspberry Pi**.

---

## Core Capabilities

* **Commercial-Grade Alternative**: A free alternative to closed-source hardware load balancers and virtual appliances.
* **Web-Based Management**: Configure load balancer routing, SSL termination, and high-availability failover directly from your web browser.
* **Low Footprint**: Runs comfortably on minimal hardware (e.g. Raspberry Pi with 1GB to 2GB RAM).
* **Native Web Application Firewall (WAF)**: Block SQL injections, Cross-Site Scripting (XSS), and rate-limit abusers natively in HAProxy.
* **Real-time Traffic Stats**: Dynamic charting, access log terminals, and simulated stress testing built right into the interface.

---

## Quick Start (Run & Test Instantly)

You can run and test the web interface locally on your development machine using PHP's built-in webserver:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/siefkencp/osbal.git
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

## Appliance Production Setup (Ubuntu & Raspberry Pi)

To turn a physical Ubuntu or Raspberry Pi OS server into a dedicated load balancer, follow these steps:

### Step 1: Install System Packages
Update your host machine's packages and install the load balancer utilities:
```bash
sudo apt-get update
sudo apt-get install -y haproxy stunnel4 keepalived apache2 php php-cli php-json
```

### Step 2: Deploy OSBal Web Files
Clear Apache's default files and copy the OSBal codebase into the document root:
```bash
# Clear default files
sudo rm -rf /var/www/html/*

# Clone the code into place
git clone https://github.com/siefkencp/osbal.git /tmp/osbal
sudo cp -r /tmp/osbal/* /var/www/html/

# Set ownership to Apache's web user
sudo chown -R www-data:www-data /var/www/html/
```

### Step 3: Configure File Permissions & Sudo Access
Allow the OSBal PHP backend (`www-data` user) to edit configuration files and reload/restart services without requiring a root password.

1. **Set file ownership**: Run these commands in your host terminal to create the configuration folders and grant the web server permission to write to them:
   ```bash
   sudo mkdir -p /usr/local/osbal/config /etc/stunnel/certs
   sudo touch /etc/haproxy/haproxy.cfg /etc/keepalived/keepalived.conf /etc/stunnel/stunnel.conf
   sudo chown -R www-data:www-data /usr/local/osbal/config /etc/stunnel/certs /etc/haproxy/haproxy.cfg /etc/keepalived/keepalived.conf /etc/stunnel/stunnel.conf
   ```

2. **Grant sudo privileges for service controls**: Run `sudo visudo` and append the following lines to the bottom of the file:
   ```sudoers
   www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload haproxy
   www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload keepalived
   www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart stunnel4
   ```

### Step 4: Run the Setup Wizard
Point your web browser to the server's IP address (e.g. `http://192.168.1.100/`) and complete the 3-step setup:
1. **System Check**: Verifies that all required system packages are present.
2. **Admin Credentials**: Creates your administrator dashboard account.
3. **Network Configuration**: Configures host hostname and management IP parameters.

---

## WAF & Realtime Stats Guides

### Using the Web Application Firewall (WAF)
1. **Enable Shields**: On the **Create Service** or **Load Balancer** page, click the WAF checkbox.
2. **Select Protections**:
   * **Block SQLi**: Rejects requests containing injection keywords (`UNION`, `SELECT`, `DROP`, etc.).
   * **Block XSS**: Blocks requests with HTML script tags and javascript handlers (`<script>`, `onerror`, `onload`).
   * **Rate Limiting**: Denies client IPs exceeding 100 requests per 10 seconds with a `429 Too Many Requests` code.
3. **IP Blacklist**: Add malicious IP addresses line-by-line to the **Global IP Access Blacklist** form at the bottom of the load balancer settings page to block them globally.

### Exploring the Real-Time Monitor
Go to the **Realtime Stats** page to inspect network operations:
* **Load Simulator**: Press **Low**, **Medium**, or **High** to mock traffic levels and watch connections, latency, and request rates react.
* **Access Log Terminal**: Displays standard log entries mapping incoming IPs to destination backend nodes.
* **DDoS Attack Test**: Press the **DDoS** button to simulate a high-volume request flood. You will see WAF rate-limiting rules engage in real time, appending red blocking alerts to the log terminal and counting up WAF-blocked request metrics.
