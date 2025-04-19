<?php

namespace App\WhatsApp;

use App\Config\ConfigManager;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Meta WhatsApp API Integration
 * 
 * Handles direct integration with Meta's WhatsApp Business API
 */
class MetaWhatsAppAPI
{
    private $client;
    private $config;
    private $logger;
    private $phoneNumberId;
    private $apiVersion;
    private $apiToken;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->apiVersion = $this->config->get('WHATSAPP_API_VERSION', 'v18.0');
        $this->phoneNumberId = $this->config->get('WHATSAPP_PHONE_NUMBER_ID');
        $this->apiToken = $this->config->get('WHATSAPP_API_TOKEN');
        
        $this->client = new Client([
            'base_uri' => "https://graph.facebook.com/{$this->apiVersion}/",
            'headers' => [
                'Authorization' => "Bearer {$this->apiToken}",
                'Content-Type' => 'application/json',
            ],
        ]);
        
        // Set up logger
        $this->logger = new Logger('meta_whatsapp_api');
        $this->logger->pushHandler(new StreamHandler(
            dirname(__DIR__, 2) . '/logs/meta_whatsapp_api.log',
            $this->config->get('APP_DEBUG') ? Logger::DEBUG : Logger::INFO
        ));
    }
    
    /**
     * Get WhatsApp Business Account information
     * 
     * @return array Account information
     */
    public function getBusinessAccountInfo(): array
    {
        try {
            $businessAccountId = $this->config->get('WHATSAPP_BUSINESS_ACCOUNT_ID');
            $response = $this->client->get("{$businessAccountId}");
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Retrieved WhatsApp Business Account info', [
                'businessAccountId' => $businessAccountId,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get WhatsApp Business Account info', [
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get WhatsApp Phone Number information
     * 
     * @return array Phone number information
     */
    public function getPhoneNumberInfo(): array
    {
        try {
            $response = $this->client->get("{$this->phoneNumberId}");
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Retrieved WhatsApp Phone Number info', [
                'phoneNumberId' => $this->phoneNumberId,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get WhatsApp Phone Number info', [
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send text message
     * 
     * @param string $to Recipient phone number
     * @param string $message Message text
     * @param bool $previewUrl Whether to enable URL previews
     * @return array API response
     */
    public function sendTextMessage(string $to, string $message, bool $previewUrl = false): array
    {
        try {
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => $previewUrl,
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
     * Send image message
     * 
     * @param string $to Recipient phone number
     * @param string $imageUrl Image URL
     * @param string $caption Optional image caption
     * @return array API response
     */
    public function sendImageMessage(string $to, string $imageUrl, string $caption = ''): array
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl,
                ]
            ];
            
            if (!empty($caption)) {
                $payload['image']['caption'] = $caption;
            }
            
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'json' => $payload
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp image message', [
                'to' => $to,
                'imageUrl' => $imageUrl,
                'caption' => $caption,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp image message', [
                'to' => $to,
                'imageUrl' => $imageUrl,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send document message
     * 
     * @param string $to Recipient phone number
     * @param string $documentUrl Document URL
     * @param string $filename Document filename
     * @param string $caption Optional document caption
     * @return array API response
     */
    public function sendDocumentMessage(string $to, string $documentUrl, string $filename, string $caption = ''): array
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'document',
                'document' => [
                    'link' => $documentUrl,
                    'filename' => $filename
                ]
            ];
            
            if (!empty($caption)) {
                $payload['document']['caption'] = $caption;
            }
            
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'json' => $payload
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp document message', [
                'to' => $to,
                'documentUrl' => $documentUrl,
                'filename' => $filename,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp document message', [
                'to' => $to,
                'documentUrl' => $documentUrl,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send audio message
     * 
     * @param string $to Recipient phone number
     * @param string $audioUrl Audio URL
     * @return array API response
     */
    public function sendAudioMessage(string $to, string $audioUrl): array
    {
        try {
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'audio',
                    'audio' => [
                        'link' => $audioUrl
                    ]
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp audio message', [
                'to' => $to,
                'audioUrl' => $audioUrl,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp audio message', [
                'to' => $to,
                'audioUrl' => $audioUrl,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send video message
     * 
     * @param string $to Recipient phone number
     * @param string $videoUrl Video URL
     * @param string $caption Optional video caption
     * @return array API response
     */
    public function sendVideoMessage(string $to, string $videoUrl, string $caption = ''): array
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'video',
                'video' => [
                    'link' => $videoUrl
                ]
            ];
            
            if (!empty($caption)) {
                $payload['video']['caption'] = $caption;
            }
            
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'json' => $payload
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp video message', [
                'to' => $to,
                'videoUrl' => $videoUrl,
                'caption' => $caption,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp video message', [
                'to' => $to,
                'videoUrl' => $videoUrl,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send location message
     * 
     * @param string $to Recipient phone number
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string $name Optional location name
     * @param string $address Optional location address
     * @return array API response
     */
    public function sendLocationMessage(string $to, float $latitude, float $longitude, string $name = '', string $address = ''): array
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'location',
                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ];
            
            if (!empty($name)) {
                $payload['location']['name'] = $name;
            }
            
            if (!empty($address)) {
                $payload['location']['address'] = $address;
            }
            
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'json' => $payload
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp location message', [
                'to' => $to,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp location message', [
                'to' => $to,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send contact message
     * 
     * @param string $to Recipient phone number
     * @param array $contacts Contacts array
     * @return array API response
     */
    public function sendContactMessage(string $to, array $contacts): array
    {
        try {
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'contacts',
                    'contacts' => $contacts
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp contact message', [
                'to' => $to,
                'contacts' => $contacts,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp contact message', [
                'to' => $to,
                'contacts' => $contacts,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send typing indicator
     * 
     * @param string $to Recipient phone number
     * @return array API response
     */
    public function sendTypingIndicator(string $to): array
    {
        try {
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'reaction',
                    'reaction' => [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $to,
                        'type' => 'typing'
                    ]
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $this->logger->info('Sent WhatsApp typing indicator', [
                'to' => $to,
                'response' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp typing indicator', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Mark message as read
     * 
     * @param string $messageId Message ID
     * @return array API response
     */
    public function markMessageAsRead(string $messageId): array
    {
        try {
            $response = $this->client->post("{$this->phoneNumberId}/messages", [
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
    
    /**
     * Get media URL
     * 
     * @param string $mediaId Media ID
     * @return string Media URL
     */
    public function getMediaUrl(string $mediaId): string
    {
        try {
            $response = $this->client->get("{$mediaId}");
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['url'])) {
                $this->logger->info('Retrieved WhatsApp media URL', [
                    'mediaId' => $mediaId,
                    'url' => $result['url']
                ]);
                
                return $result['url'];
            }
            
            return '';
        } catch (\Exception $e) {
            $this->logger->error('Failed to get WhatsApp media URL', [
                'mediaId' => $mediaId,
                'error' => $e->getMessage()
            ]);
            
            return '';
        }
    }
    
    /**
     * Download media
     * 
     * @param string $mediaUrl Media URL
     * @return string|false Media content or false on failure
     */
    public function downloadMedia(string $mediaUrl)
    {
        try {
            $response = $this->client->get($mediaUrl);
            
            $content = $response->getBody()->getContents();
            $this->logger->info('Downloaded WhatsApp media', [
                'mediaUrl' => $mediaUrl,
                'contentLength' => strlen($content)
            ]);
            
            return $content;
        } catch (\Exception $e) {
            $this->logger->error('Failed to download WhatsApp media', [
                'mediaUrl' => $mediaUrl,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
