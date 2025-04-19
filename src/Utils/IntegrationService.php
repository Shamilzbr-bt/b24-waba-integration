<?php

namespace App\Utils;

use App\Config\ConfigManager;
use App\WhatsApp\MetaWhatsAppAPI;
use App\Bitrix\BitrixClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Integration Service
 * 
 * Connects the WhatsApp API with Bitrix24 Open Channels
 */
class IntegrationService
{
    private $config;
    private $logger;
    private $whatsappApi;
    private $bitrixClient;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->whatsappApi = new MetaWhatsAppAPI();
        $this->bitrixClient = new BitrixClient();
        
        // Set up logger
        $this->logger = new Logger('integration');
        $this->logger->pushHandler(new StreamHandler(
            dirname(__DIR__, 2) . '/logs/integration.log',
            $this->config->get('APP_DEBUG') ? Logger::DEBUG : Logger::INFO
        ));
    }
    
    /**
     * Process incoming WhatsApp message
     * 
     * @param array $message Message data
     * @param array $contact Contact data
     * @return bool True if processed successfully
     */
    public function processIncomingWhatsAppMessage(array $message, array $contact): bool
    {
        $this->logger->info('Processing incoming WhatsApp message', [
            'messageId' => $message['id'] ?? '',
            'from' => $message['from'] ?? '',
            'type' => $message['type'] ?? ''
        ]);
        
        try {
            // Extract message details
            $messageId = $message['id'] ?? '';
            $from = $message['from'] ?? '';
            $timestamp = $message['timestamp'] ?? '';
            $type = $message['type'] ?? '';
            
            // Find contact information
            $contactName = '';
            if (isset($contact['profile']['name'])) {
                $contactName = $contact['profile']['name'];
            }
            
            // Mark message as read in WhatsApp
            $this->whatsappApi->markMessageAsRead($messageId);
            
            // Process different message types
            $messageContent = '';
            $mediaUrl = '';
            
            switch ($type) {
                case 'text':
                    $messageContent = $message['text']['body'] ?? '';
                    break;
                    
                case 'image':
                    $mediaId = $message['image']['id'] ?? '';
                    $mediaUrl = $this->whatsappApi->getMediaUrl($mediaId);
                    $messageContent = $message['image']['caption'] ?? 'Image';
                    break;
                    
                case 'video':
                    $mediaId = $message['video']['id'] ?? '';
                    $mediaUrl = $this->whatsappApi->getMediaUrl($mediaId);
                    $messageContent = $message['video']['caption'] ?? 'Video';
                    break;
                    
                case 'audio':
                    $mediaId = $message['audio']['id'] ?? '';
                    $mediaUrl = $this->whatsappApi->getMediaUrl($mediaId);
                    $messageContent = 'Audio message';
                    break;
                    
                case 'document':
                    $mediaId = $message['document']['id'] ?? '';
                    $mediaUrl = $this->whatsappApi->getMediaUrl($mediaId);
                    $messageContent = $message['document']['caption'] ?? $message['document']['filename'] ?? 'Document';
                    break;
                    
                case 'location':
                    $latitude = $message['location']['latitude'] ?? '';
                    $longitude = $message['location']['longitude'] ?? '';
                    $messageContent = "Location: {$latitude}, {$longitude}";
                    break;
                    
                case 'contacts':
                    $contactsData = $message['contacts'] ?? [];
                    $contactCount = count($contactsData);
                    $messageContent = "Shared {$contactCount} contact" . ($contactCount > 1 ? 's' : '');
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
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error processing incoming WhatsApp message', [
                'error' => $e->getMessage(),
                'messageId' => $message['id'] ?? ''
            ]);
            
            return false;
        }
    }
    
    /**
     * Process outgoing Bitrix24 message
     * 
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param string $mediaUrl Media URL (if any)
     * @param string $mediaType Media type
     * @return bool True if processed successfully
     */
    public function processOutgoingBitrixMessage(
        string $to,
        string $message,
        string $mediaUrl = '',
        string $mediaType = ''
    ): bool {
        $this->logger->info('Processing outgoing Bitrix24 message', [
            'to' => $to,
            'message' => $message,
            'mediaUrl' => $mediaUrl,
            'mediaType' => $mediaType
        ]);
        
        try {
            // Send typing indicator
            $this->whatsappApi->sendTypingIndicator($to);
            
            // Process different media types
            if (!empty($mediaUrl)) {
                switch ($mediaType) {
                    case 'image':
                        $result = $this->whatsappApi->sendImageMessage($to, $mediaUrl, $message);
                        break;
                        
                    case 'video':
                        $result = $this->whatsappApi->sendVideoMessage($to, $mediaUrl, $message);
                        break;
                        
                    case 'audio':
                        $result = $this->whatsappApi->sendAudioMessage($to, $mediaUrl);
                        break;
                        
                    case 'document':
                        $filename = basename($mediaUrl);
                        $result = $this->whatsappApi->sendDocumentMessage($to, $mediaUrl, $filename, $message);
                        break;
                        
                    default:
                        // If media type is unknown, send text message with media URL
                        $fullMessage = $message;
                        if (!empty($mediaUrl)) {
                            $fullMessage .= "\n\nMedia: {$mediaUrl}";
                        }
                        $result = $this->whatsappApi->sendTextMessage($to, $fullMessage);
                        break;
                }
            } else {
                // Send text message
                $result = $this->whatsappApi->sendTextMessage($to, $message);
            }
            
            return !isset($result['error']);
        } catch (\Exception $e) {
            $this->logger->error('Error processing outgoing Bitrix24 message', [
                'error' => $e->getMessage(),
                'to' => $to,
                'message' => $message
            ]);
            
            return false;
        }
    }
    
    /**
     * Update message status in Bitrix24
     * 
     * @param string $messageId Message ID
     * @param string $status Message status
     * @return bool True if updated successfully
     */
    public function updateMessageStatus(string $messageId, string $status): bool
    {
        $this->logger->info('Updating message status in Bitrix24', [
            'messageId' => $messageId,
            'status' => $status
        ]);
        
        try {
            return $this->bitrixClient->updateMessageStatus($messageId, $status);
        } catch (\Exception $e) {
            $this->logger->error('Error updating message status in Bitrix24', [
                'error' => $e->getMessage(),
                'messageId' => $messageId,
                'status' => $status
            ]);
            
            return false;
        }
    }
    
    /**
     * Check connection status
     * 
     * @return array Connection status
     */
    public function checkConnectionStatus(): array
    {
        $this->logger->info('Checking connection status');
        
        $status = [
            'whatsapp' => false,
            'bitrix24' => false,
            'errors' => []
        ];
        
        // Check WhatsApp connection
        try {
            $whatsappInfo = $this->whatsappApi->getPhoneNumberInfo();
            if (!isset($whatsappInfo['error'])) {
                $status['whatsapp'] = true;
                $status['whatsapp_info'] = $whatsappInfo;
            } else {
                $status['errors'][] = 'WhatsApp API: ' . $whatsappInfo['error'];
            }
        } catch (\Exception $e) {
            $status['errors'][] = 'WhatsApp API: ' . $e->getMessage();
        }
        
        // Check Bitrix24 connection
        try {
            // In a real implementation, you would check the Bitrix24 connection
            // For now, we'll just assume it's connected if the domain is set
            $bitrixDomain = $this->config->get('BITRIX24_DOMAIN');
            if (!empty($bitrixDomain)) {
                $status['bitrix24'] = true;
                $status['bitrix24_info'] = [
                    'domain' => $bitrixDomain
                ];
            } else {
                $status['errors'][] = 'Bitrix24: Domain not configured';
            }
        } catch (\Exception $e) {
            $status['errors'][] = 'Bitrix24: ' . $e->getMessage();
        }
        
        return $status;
    }
}
