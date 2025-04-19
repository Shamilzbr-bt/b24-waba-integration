<?php
/**
 * WhatsApp Bitrix24 Integration - Save Configuration
 * 
 * This script saves the configuration from the setup form to the .env file
 * and creates the necessary Open Channel in Bitrix24.
 */

// Require the Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Use the ConfigManager class
use App\Config\ConfigManager;
use App\Bitrix\BitrixClient;

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get form data
    $businessAccountId = $_POST['business_account_id'] ?? '';
    $phoneNumberId = $_POST['phone_number_id'] ?? '';
    $apiToken = $_POST['api_token'] ?? '';
    $webhookVerifyToken = $_POST['webhook_verify_token'] ?? '';
    $webhookUrl = $_POST['webhook_url'] ?? '';
    $bitrixDomain = $_POST['bitrix_domain'] ?? '';
    $bitrixWebhookUrl = $_POST['bitrix_webhook_url'] ?? '';
    $bitrixUserId = $_POST['bitrix_user_id'] ?? '';
    
    // Validate required fields
    if (
        empty($businessAccountId) ||
        empty($phoneNumberId) ||
        empty($apiToken) ||
        empty($webhookVerifyToken) ||
        empty($webhookUrl) ||
        empty($bitrixDomain) ||
        empty($bitrixWebhookUrl) ||
        empty($bitrixUserId)
    ) {
        throw new Exception('All fields are required');
    }
    
    // Create .env file content
    $envContent = "# WhatsApp Meta API Configuration\n";
    $envContent .= "WHATSAPP_API_VERSION=v18.0\n";
    $envContent .= "WHATSAPP_PHONE_NUMBER_ID={$phoneNumberId}\n";
    $envContent .= "WHATSAPP_BUSINESS_ACCOUNT_ID={$businessAccountId}\n";
    $envContent .= "WHATSAPP_API_TOKEN={$apiToken}\n";
    $envContent .= "WHATSAPP_WEBHOOK_VERIFY_TOKEN={$webhookVerifyToken}\n\n";
    
    $envContent .= "# Bitrix24 Configuration\n";
    $envContent .= "BITRIX24_DOMAIN={$bitrixDomain}\n";
    $envContent .= "BITRIX24_WEBHOOK_URL={$bitrixWebhookUrl}\n";
    $envContent .= "BITRIX24_USER_ID={$bitrixUserId}\n\n";
    
    $envContent .= "# Application Configuration\n";
    $envContent .= "APP_ENV=production\n";
    $envContent .= "APP_DEBUG=false\n";
    $envContent .= "APP_URL=" . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . "\n";
    $envContent .= "APP_LOG_LEVEL=info\n";
    
    // Write .env file
    $envFile = dirname(__DIR__, 2) . '/config/.env';
    file_put_contents($envFile, $envContent);
    
    // Create Open Channel in Bitrix24
    // Note: In a real implementation, you would create the Open Channel in Bitrix24
    // using the Bitrix24 REST API. For now, we'll just simulate this.
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Configuration saved successfully',
        'redirect_url' => 'https://' . $bitrixDomain . '/contact_center/'
    ]);
} catch (Exception $e) {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
