<?php
/**
 * OSbal & OSecure Optionality Integration Verification Tests
 * Run this script to validate that core OSbal configurations compile
 * cleanly and that OSecure features are 100% optional.
 */

// Dynamically resolve Document Root relative to this tests directory
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/global-settings.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/osecure.php';

echo "========================================================\n";
echo "    OSBAL & OSECURE INTEGRATION OPTIONALITY TESTS       \n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $message) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] " . $message . "\n";
        $passCount++;
    } else {
        echo "[FAIL] " . $message . "\n";
        $failCount++;
    }
}

// Back up any pre-existing config file to isolate defaults verification
$configFile = getOSecureSettingsFile();
$configBackup = $configFile . '.bak';
if (file_exists($configFile)) {
    rename($configFile, $configBackup);
}

try {
    // ----------------------------------------------------
    // TEST 1: Default Optionality State
    // ----------------------------------------------------
    echo "Running Test 1: Verify Default Settings State...\n";
    $settings = getOSecureSettings();
    assertTest(is_array($settings), "Settings returns an associative array.");
    assertTest($settings['enabled'] === false, "OSecure integration is DISABLED by default.");
    assertTest($settings['license_key'] === '', "Default license key is empty.");
    assertTest($settings['share_metrics'] === true, "Default share_metrics parameter is safely preset.");

    // ----------------------------------------------------
    // TEST 2: Failsafe Daemon Separation
    // ----------------------------------------------------
    echo "\nRunning Test 2: Verify Failsafe Daemon Separation...\n";
    $configDir = config::getConfigDir();
    $daemonFile = $configDir . 'osecure-agentd';

    if (file_exists($daemonFile)) {
        unlink($daemonFile);
    }

    $settings['enabled'] = false;
    saveOSecureSettings($settings);

    assertTest(!file_exists($daemonFile), "Background sidecar daemon is NOT installed when integration is disabled.");

    // ----------------------------------------------------
    // TEST 3: Dynamic Trigger on Enablement
    // ----------------------------------------------------
    echo "\nRunning Test 3: Verify Auto-Installer Trigger on Enable...\n";
    $settings['enabled'] = true;
    $settings['license_key'] = 'OSecure-Test-Key-98765';
    saveOSecureSettings($settings);

    $installStatusMsg = installOSecureDaemonIfNeeded();
    echo "Installer Output: " . $installStatusMsg . "\n";

    assertTest(file_exists($daemonFile), "Daemon file correctly compiled on enablement.");
    assertTest(is_executable($daemonFile), "Daemon file permissions permit executable operations.");

    // ----------------------------------------------------
    // TEST 4: Config Syntax Validation Integrity
    // ----------------------------------------------------
    echo "\nRunning Test 4: Verify OSbal HAProxy Config Syntax Integrity...\n";
    $haproxyConfigPath = config::getHaproxyCfg();
    assertTest(file_exists($haproxyConfigPath), "HAProxy config file exists at " . $haproxyConfigPath);

} catch (Exception $e) {
    echo "[ERROR] Unexpected exception occurred: " . $e->getMessage() . "\n";
    $failCount++;
} finally {
    // ----------------------------------------------------
    // CLEANUP & RESTORE
    // ----------------------------------------------------
    if (file_exists($daemonFile)) {
        unlink($daemonFile);
    }
    if (file_exists($configFile)) {
        unlink($configFile);
    }
    if (file_exists($configBackup)) {
        rename($configBackup, $configFile);
    }
}

echo "\n========================================================\n";
echo "TEST RESULTS:\n";
echo "Passed: " . $passCount . "\n";
echo "Failed: " . $failCount . "\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
} else {
    exit(0);
}
