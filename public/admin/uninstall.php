<?php
/**
 * WhatsApp Bitrix24 Integration - Uninstallation Script
 * 
 * This script handles the uninstallation of the WhatsApp Bitrix24 Integration app.
 */

// Require the Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Get uninstallation parameters from Bitrix24
$auth = $_REQUEST['AUTH'] ?? '';
$memberId = $_REQUEST['member_id'] ?? '';

// Log uninstallation
$uninstallLog = [
    'auth' => $auth,
    'member_id' => $memberId,
    'uninstalled_at' => date('Y-m-d H:i:s')
];

// Save uninstallation log to file
$logFile = dirname(__DIR__, 2) . '/logs/uninstall_log.json';
file_put_contents($logFile, json_encode($uninstallLog, JSON_PRETTY_PRINT));

// Remove installation data
$installDataFile = dirname(__DIR__, 2) . '/config/install_data.json';
if (file_exists($installDataFile)) {
    unlink($installDataFile);
}

// Display uninstallation message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Integration Uninstalled</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>WhatsApp Integration Uninstalled</h1>
        </header>
        <div class="setup-container">
            <div class="setup-content">
                <p>The WhatsApp Meta API integration has been successfully uninstalled from your Bitrix24 account.</p>
                <p>Thank you for using our service.</p>
            </div>
        </div>
    </div>
</body>
</html>
