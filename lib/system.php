<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';

class ApplianceSystem {
    
    private static function isSandbox() {
        return config::getConfigDir() !== '/usr/local/osbal/config/';
    }

    private static function execute($command) {
        if (self::isSandbox()) {
            // Log locally to terminal logs or config log folder
            $logFile = config::getConfigDir() . 'system_events.log';
            $logMessage = "[" . date('Y-m-d H:i:s') . "] [SANDBOX BYPASS] Executed: " . $command . "\n";
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
            return array('success' => true, 'output' => 'Sandbox bypass success.');
        }

        // Run system command safely
        $output = array();
        $code = 0;
        exec($command . ' 2>&1', $output, $code);
        
        $logFile = config::getConfigDir() . 'system_events.log';
        $logMessage = "[" . date('Y-m-d H:i:s') . "] Executed: " . $command . " | Code: " . $code . " | Output: " . implode(" ", $output) . "\n";
        @file_put_contents($logFile, $logMessage, FILE_APPEND);

        return array(
            'success' => ($code === 0),
            'output' => implode("\n", $output),
            'code' => $code
        );
    }

    public static function reloadHaproxy() {
        return self::execute('sudo systemctl reload haproxy');
    }

    public static function reloadKeepalived() {
        return self::execute('sudo systemctl reload keepalived');
    }

    public static function restartStunnel() {
        // Stunnel4 does not natively support clean hot-reloads on some systems, restart is safer
        return self::execute('sudo systemctl restart stunnel4');
    }
}
?>
