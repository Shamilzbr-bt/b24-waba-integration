<?php
/**
 * WhatsApp Bitrix24 Integration - API Controller
 * 
 * This file serves as the API controller for the WhatsApp Bitrix24 Integration.
 * It provides endpoints for checking connection status and manual testing.
 */

// Require the Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Use the IntegrationService class
use App\Utils\IntegrationService;
use App\Config\ConfigManager;

// Set content type to JSON
header('Content-Type: application/json');

// Get request method and path
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($requestPath, '/'));
$endpoint = end($pathParts);

// Create integration service
$integrationService = new IntegrationService();

// Handle different endpoints
switch ($endpoint) {
    case 'status':
        // Check connection status
        $status = $integrationService->checkConnectionStatus();
        echo json_encode($status);
        break;
        
    case 'test-send':
        // Test sending a message to WhatsApp
        if ($requestMethod !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }
        
        // Get request body
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);
        
        // Validate required fields
        if (!isset($data['to']) || !isset($data['message'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }
        
        // Send test message
        $result = $integrationService->processOutgoingBitrixMessage(
            $data['to'],
            $data['message'],
            $data['media_url'] ?? '',
            $data['media_type'] ?? ''
        );
        
        echo json_encode(['success' => $result]);
        break;
        
    case 'test-webhook':
        // Test webhook processing
        if ($requestMethod !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }
        
        // Get request body
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);
        
        // Validate webhook payload
        if (!isset($data['entry']) || !is_array($data['entry'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid webhook payload']);
            break;
        }
        
        // Process webhook payload
        $processed = false;
        foreach ($data['entry'] as $entry) {
            if (isset($entry['changes']) && is_array($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    if (isset($change['value']['messages']) && is_array($change['value']['messages'])) {
                        foreach ($change['value']['messages'] as $message) {
                            $contact = [];
                            if (isset($change['value']['contacts']) && is_array($change['value']['contacts'])) {
                                foreach ($change['value']['contacts'] as $c) {
                                    if (isset($c['wa_id']) && $c['wa_id'] === $message['from']) {
                                        $contact = $c;
                                        break;
                                    }
                                }
                            }
                            
                            $result = $integrationService->processIncomingWhatsAppMessage($message, $contact);
                            if ($result) {
                                $processed = true;
                            }
                        }
                    }
                }
            }
        }
        
        echo json_encode(['success' => $processed]);
        break;
        
    default:
        // Unknown endpoint
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}
