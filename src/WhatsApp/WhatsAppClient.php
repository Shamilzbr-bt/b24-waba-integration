<?php

namespace App\WhatsApp;

use App\Config\ConfigManager;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * WhatsApp API Client
 * 
 * Handles communication with the Meta WhatsApp Business API
 */
class WhatsAppClient
{
    private $client;
    private $config;
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->client = new Client([
            'base_uri' => 'https://graph.facebook.com/' . $this->config->get('WHATSAPP_API_VERSION') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->get('WHATSAPP_API_TOKEN'),
                'Content-Type' => 'application/json',
            ],
        ]);
        
        // Set up logger
        $this->logger = new Logger('whatsapp');
        $this->logger->pushHandler(new StreamHandler(
            dirname(__DIR__, 2) . '/logs/whatsapp.log',
            $this->config->get('APP_DEBUG') ? Logger::DEBUG : Logger::INFO
        ));
    }
    
    /**
     * Send text message to WhatsApp
     * 
     * @param string $to Recipient phone number
     * @param string $message Message text
     * @return array API response
     */
    public function sendTextMessage(string $to, string $message): array
    {
        $phoneNumberId = $this->config->get('WHATSAPP_PHONE_NUMBER_ID');
        
        try {
            $response = $this->client->post("{$phoneNumberId}/messages", [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message
                    ]
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp text message', [
                'to' => $to,
                'message' => $message,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp text message', [
                'to' => $to,
                'message' => $message,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send media message to WhatsApp
     * 
     * @param string $to Recipient phone number
     * @param string $mediaType Media type (image, document, audio, video)
     * @param string $mediaUrl URL of the media
     * @param string $caption Optional caption for the media
     * @return array API response
     */
    public function sendMediaMessage(string $to, string $mediaType, string $mediaUrl, string $caption = ''): array
    {
        $phoneNumberId = $this->config->get('WHATSAPP_PHONE_NUMBER_ID');
        $validMediaTypes = ['image', 'document', 'audio', 'video'];
        
        if (!in_array($mediaType, $validMediaTypes)) {
            $this->logger->error('Invalid media type', [
                'mediaType' => $mediaType,
                'validTypes' => $validMediaTypes
            ]);
            
            return ['error' => 'Invalid media type. Must be one of: ' . implode(', ', $validMediaTypes)];
        }
        
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => $mediaType,
                $mediaType => [
                    'link' => $mediaUrl,
                ]
            ];
            
            // Add caption if provided and media type supports it
            if ($caption && in_array($mediaType, ['image', 'document', 'video'])) {
                $payload[$mediaType]['caption'] = $caption;
            }
            
            $response = $this->client->post("{$phoneNumberId}/messages", [
                'json' => $payload
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp media message', [
                'to' => $to,
                'mediaType' => $mediaType,
                'mediaUrl' => $mediaUrl,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp media message', [
                'to' => $to,
                'mediaType' => $mediaType,
                'mediaUrl' => $mediaUrl,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Mark message as read
     * 
     * @param string $messageId ID of the message to mark as read
     * @return array API response
     */
    public function markMessageAsRead(string $messageId): array
    {
        $phoneNumberId = $this->config->get('WHATSAPP_PHONE_NUMBER_ID');
        
        try {
            $response = $this->client->post("{$phoneNumberId}/messages", [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'status' => 'read',
                    'message_id' => $messageId
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Marked WhatsApp message as read', [
                'messageId' => $messageId,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark WhatsApp message as read', [
                'messageId' => $messageId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
}
