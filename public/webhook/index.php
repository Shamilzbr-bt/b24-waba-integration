<?php
/**
 * WhatsApp Bitrix24 Integration - Webhook Endpoint
 * 
 * This file serves as the webhook endpoint for receiving messages from the Meta WhatsApp Business API.
 * It will process incoming messages and forward them to Bitrix24 Open Channels.
 */

// Require the Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Use the WebhookHandler class
use App\WhatsApp\WebhookHandler;
use App\Config\ConfigManager;

// Initialize configuration
$config = ConfigManager::getInstance();

// Create webhook handler
$webhookHandler = new WebhookHandler();

// Get request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle WhatsApp webhook verification (GET request)
if ($requestMethod === 'GET' && isset($_GET['hub_mode']) && $_GET['hub_mode'] === 'subscribe') {
    $hubChallenge = $_GET['hub_challenge'] ?? '';
    $hubVerifyToken = $_GET['hub_verify_token'] ?? '';
    
    // Verify webhook
    $challenge = $webhookHandler->verifyWebhook(
        $_GET['hub_mode'],
        $hubChallenge,
        $hubVerifyToken
    );
    
    if ($challenge !== false) {
        // Return challenge string to confirm webhook
        echo $challenge;
        exit;
    } else {
        // Invalid verification token
        http_response_code(403);
        echo json_encode(['error' => 'Verification failed']);
        exit;
    }
}

// Handle incoming messages (POST request)
if ($requestMethod === 'POST') {
    // Get request body
    $requestBody = file_get_contents('php://input');
    $payload = json_decode($requestBody, true);
    
    // Process webhook payload
    if ($payload) {
        $result = $webhookHandler->processWebhook($payload);
        
        // Return success response
        http_response_code(200);
        echo json_encode(['success' => $result]);
        exit;
    } else {
        // Invalid payload
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    }
}

// If we get here, it's an unsupported request
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
