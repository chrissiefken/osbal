<?php
// global server configuration settings are here
class config {
    const VERSION = '1.4.3';
    
    // Determine the path to a writable settings directory
    public static function getConfigDir() {
        $path = '/usr/local/osbal/config/';
        if (!is_dir($path) || !is_writable($path)) {
            // Fallback to project-local config directory
            $path = dirname(__DIR__) . '/config/';
            if (!file_exists($path)) {
                @mkdir($path, 0755, true);
            }
        }
        return $path;
    }

    // Determine the path to the system log file
    public static function getLogFile() {
        $prodDir = '/var/log/osbal/';
        if (is_dir($prodDir) && is_writable($prodDir)) {
            return $prodDir . 'system_events.log';
        }
        // Fallback to project-local logs directory
        $localDir = dirname(__DIR__) . '/logs/';
        if (!file_exists($localDir)) {
            @mkdir($localDir, 0755, true);
        }
        return $localDir . 'system_events.log';
    }

    // Config files
    const userFile = 'users.json';
    const sslFile = 'ssl.json';
    const adminIpSettings = 'adminIp.json';
    const haPartner = 'partner.json';
    const lbServices = 'services.json';

    // Environment/System settings
    public static function getHaproxyCfg() {
        $path = '/etc/haproxy/haproxy.cfg';
        if (!file_exists($path) || !is_writable($path)) {
            return self::getConfigDir() . 'haproxy.cfg';
        }
        return $path;
    }

    public static function getStunnelCfg() {
        $path = '/etc/stunnel/stunnel.conf';
        if (!file_exists($path) || !is_writable($path)) {
            return self::getConfigDir() . 'stunnel.conf';
        }
        return $path;
    }

    public static function getKeepalivedCfg() {
        $path = '/etc/keepalived/keepalived.conf';
        if (!file_exists($path) || !is_writable($path)) {
            return self::getConfigDir() . 'keepalived.conf';
        }
        return $path;
    }
}
?>