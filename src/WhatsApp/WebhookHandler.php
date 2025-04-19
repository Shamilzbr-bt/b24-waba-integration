<?php

namespace App\WhatsApp;

use App\Config\ConfigManager;
use App\Bitrix\BitrixClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * WhatsApp Webhook Handler
 * 
 * Processes incoming webhook requests from the Meta WhatsApp Business API
 */
class WebhookHandler
{
    private $config;
    private $logger;
    private $bitrixClient;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->bitrixClient = new BitrixClient();
        
        // Set up logger
        $this->logger = new Logger('webhook');
        $this->logger->pushHandler(new StreamHandler(
            dirname(__DIR__, 2) . '/logs/webhook.log',
            $this->config->get('APP_DEBUG') ? Logger::DEBUG : Logger::INFO
        ));
    }
    
    /**
     * Verify webhook
     * 
     * @param string $mode Hub mode
     * @param string $challenge Hub challenge
     * @param string $verifyToken Verify token
     * @return string|false Challenge string if verified, false otherwise
     */
    public function verifyWebhook(string $mode, string $challenge, string $verifyToken)
    {
        $configToken = $this->config->get('WHATSAPP_WEBHOOK_VERIFY_TOKEN');
        
        $this->logger->info('Webhook verification attempt', [
            'mode' => $mode,
            'verifyToken' => $verifyToken,
            'configToken' => $configToken
        ]);
        
        if ($mode === 'subscribe' && $verifyToken === $configToken) {
            $this->logger->info('Webhook verification successful');
            return $challenge;
        }
        
        $this->logger->warning('Webhook verification failed', [
            'mode' => $mode,
            'verifyToken' => $verifyToken
        ]);
        
        return false;
    }
    
    /**
     * Process webhook payload
     * 
     * @param array $payload Webhook payload
     * @return bool True if processed successfully
     */
    public function processWebhook(array $payload): bool
    {
        $this->logger->info('Processing webhook payload', [
            'payload' => $payload
        ]);
        
        // Check if this is a WhatsApp message notification
        if (!isset($payload['object']) || $payload['object'] !== 'whatsapp_business_account') {
            $this->logger->warning('Invalid webhook payload object', [
                'object' => $payload['object'] ?? 'not set'
            ]);
            return false;
        }
        
        // Process each entry in the payload
        if (isset($payload['entry']) && is_array($payload['entry'])) {
            foreach ($payload['entry'] as $entry) {
                $this->processEntry($entry);
            }
        }
        
        return true;
    }
    
    /**
     * Process webhook entry
     * 
     * @param array $entry Webhook entry
     * @return bool True if processed successfully
     */
    private function processEntry(array $entry): bool
    {
        // Check if this entry is for our WhatsApp Business Account
        $businessAccountId = $this->config->get('WHATSAPP_BUSINESS_ACCOUNT_ID');
        if (!isset($entry['id']) || $entry['id'] !== $businessAccountId) {
            $this->logger->warning('Entry for unknown business account', [
                'entryId' => $entry['id'] ?? 'not set',
                'expectedId' => $businessAccountId
            ]);
            return false;
        }
        
        // Process each change in the entry
        if (isset($entry['changes']) && is_array($entry['changes'])) {
            foreach ($entry['changes'] as $change) {
                $this->processChange($change);
            }
        }
        
        return true;
    }
    
    /**
     * Process webhook change
     * 
     * @param array $change Webhook change
     * @return bool True if processed successfully
     */
    private function processChange(array $change): bool
    {
        // Check if this is a change for the WhatsApp messaging service
        if (!isset($change['field']) || $change['field'] !== 'messages') {
            $this->logger->info('Ignoring non-message change', [
                'field' => $change['field'] ?? 'not set'
            ]);
            return false;
        }
        
        // Get the value of the change
        if (!isset($change['value']) || !is_array($change['value'])) {
            $this->logger->warning('Invalid change value', [
                'change' => $change
            ]);
            return false;
        }
        
        $value = $change['value'];
        
        // Process each message in the change
        if (isset($value['messages']) && is_array($value['messages'])) {
            foreach ($value['messages'] as $message) {
                $this->processMessage($message, $value['contacts'] ?? []);
            }
        }
        
        // Process each status in the change
        if (isset($value['statuses']) && is_array($value['statuses'])) {
            foreach ($value['statuses'] as $status) {
                $this->processStatus($status);
            }
        }
        
        return true;
    }
    
    /**
     * Process message
     * 
     * @param array $message Message data
     * @param array $contacts Contact data
     * @return bool True if processed successfully
     */
    private function processMessage(array $message, array $contacts): bool
    {
        // Extract message details
        $messageId = $message['id'] ?? '';
        $from = $message['from'] ?? '';
        $timestamp = $message['timestamp'] ?? '';
        $type = $message['type'] ?? '';
        
        // Find contact information
        $contactName = '';
        foreach ($contacts as $contact) {
            if (isset($contact['wa_id']) && $contact['wa_id'] === $from) {
                $contactName = $contact['profile']['name'] ?? '';
                break;
            }
        }
        
        $this->logger->info('Processing message', [
            'messageId' => $messageId,
            'from' => $from,
            'contactName' => $contactName,
            'timestamp' => $timestamp,
            'type' => $type
        ]);
        
        // Process different message types
        $messageContent = '';
        $mediaUrl = '';
        
        switch ($type) {
            case 'text':
                $messageContent = $message['text']['body'] ?? '';
                break;
                
            case 'image':
                $mediaUrl = $this->getMediaUrl($message['image']['id'] ?? '');
                $messageContent = $message['image']['caption'] ?? 'Image';
                break;
                
            case 'video':
                $mediaUrl = $this->getMediaUrl($message['video']['id'] ?? '');
                $messageContent = $message['video']['caption'] ?? 'Video';
                break;
                
            case 'audio':
                $mediaUrl = $this->getMediaUrl($message['audio']['id'] ?? '');
                $messageContent = 'Audio message';
                break;
                
            case 'document':
                $mediaUrl = $this->getMediaUrl($message['document']['id'] ?? '');
                $messageContent = $message['document']['caption'] ?? $message['document']['filename'] ?? 'Document';
                break;
                
            case 'location':
                $latitude = $message['location']['latitude'] ?? '';
                $longitude = $message['location']['longitude'] ?? '';
                $messageContent = "Location: {$latitude}, {$longitude}";
                break;
                
            default:
                $messageContent = "Unsupported message type: {$type}";
                break;
        }
        
        // Forward message to Bitrix24
        $result = $this->bitrixClient->forwardMessageToBitrix(
            $from,
            $contactName,
            $messageContent,
            $mediaUrl,
            $type,
            $messageId,
            $timestamp
        );
        
        // Mark message as read
        $whatsAppClient = new WhatsAppClient();
        $whatsAppClient->markMessageAsRead($messageId);
        
        return $result;
    }
    
    /**
     * Process status update
     * 
     * @param array $status Status data
     * @return bool True if processed successfully
     */
    private function processStatus(array $status): bool
    {
        $messageId = $status['id'] ?? '';
        $recipientId = $status['recipient_id'] ?? '';
        $statusType = $status['status'] ?? '';
        $timestamp = $status['timestamp'] ?? '';
        
        $this->logger->info('Processing status update', [
            'messageId' => $messageId,
            'recipientId' => $recipientId,
            'status' => $statusType,
            'timestamp' => $timestamp
        ]);
        
        // Update message status in Bitrix24 if needed
        if (in_array($statusType, ['delivered', 'read'])) {
            $this->bitrixClient->updateMessageStatus($messageId, $statusType);
        }
        
        return true;
    }
    
    /**
     * Get media URL from media ID
     * 
     * @param string $mediaId Media ID
     * @return string Media URL
     */
    private function getMediaUrl(string $mediaId): string
    {
        if (empty($mediaId)) {
            return '';
        }
        
        try {
            $phoneNumberId = $this->config->get('WHATSAPP_PHONE_NUMBER_ID');
            $apiVersion = $this->config->get('WHATSAPP_API_VERSION');
            $token = $this->config->get('WHATSAPP_API_TOKEN');
            
            $client = new \GuzzleHttp\Client();
            $response = $client->get("https://graph.facebook.com/{$apiVersion}/{$mediaId}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}"
                ]
            ]);
            
            $mediaData = json_decode($response->getBody()->getContents(), true);
            
            if (isset($mediaData['url'])) {
                // Get the actual media content
                $mediaResponse = $client->get($mediaData['url'], [
                    'headers' => [
                        'Authorization' => "Bearer {$token}"
                    ]
                ]);
                
                // For simplicity, we're returning the URL that requires the token
                // In a production environment, you might want to download the media
                // and store it locally or in cloud storage for easier access
                return $mediaData['url'];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to get media URL', [
                'mediaId' => $mediaId,
                'error' => $e->getMessage()
            ]);
        }
        
        return '';
    }
}
