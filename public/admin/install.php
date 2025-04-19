<?php
/**
 * WhatsApp Bitrix24 Integration - Installation Script
 * 
 * This script handles the installation of the WhatsApp Bitrix24 Integration app.
 */

// Require the Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Get installation parameters from Bitrix24
$auth = $_REQUEST['AUTH'] ?? '';
$memberId = $_REQUEST['member_id'] ?? '';
$refreshToken = $_REQUEST['REFRESH_ID'] ?? '';
$applicationToken = $_REQUEST['APP_SID'] ?? '';

// Store installation data
$installData = [
    'auth' => $auth,
    'member_id' => $memberId,
    'refresh_token' => $refreshToken,
    'application_token' => $applicationToken,
    'installed_at' => date('Y-m-d H:i:s')
];

// Save installation data to file
$installDataFile = dirname(__DIR__, 2) . '/config/install_data.json';
file_put_contents($installDataFile, json_encode($installData, JSON_PRETTY_PRINT));

// Redirect to setup page
header('Location: setup.php');
exit;
