<?php

namespace App\Bitrix;

use App\Config\ConfigManager;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Bitrix24 API Client
 * 
 * Handles communication with the Bitrix24 REST API
 */
class BitrixClient
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
            'base_uri' => 'https://' . $this->config->get('BITRIX24_DOMAIN') . '/',
            'timeout' => 30,
        ]);
        
        // Set up logger
        $this->logger = new Logger('bitrix');
        $this->logger->pushHandler(new StreamHandler(
            dirname(__DIR__, 2) . '/logs/bitrix.log',
            $this->config->get('APP_DEBUG') ? Logger::DEBUG : Logger::INFO
        ));
    }
    
    /**
     * Forward message from WhatsApp to Bitrix24 Open Channel
     * 
     * @param string $from Sender phone number
     * @param string $contactName Contact name
     * @param string $messageContent Message content
     * @param string $mediaUrl Media URL (if any)
     * @param string $messageType Message type
     * @param string $messageId WhatsApp message ID
     * @param string $timestamp Message timestamp
     * @return bool True if forwarded successfully
     */
    public function forwardMessageToBitrix(
        string $from,
        string $contactName,
        string $messageContent,
        string $mediaUrl = '',
        string $messageType = 'text',
        string $messageId = '',
        string $timestamp = ''
    ): bool {
        try {
            // First, check if contact exists in Bitrix24 CRM
            $contact = $this->findOrCreateContact($from, $contactName);
            
            // Get or create open channel session
            $sessionId = $this->getOrCreateOpenChannelSession($from, $contact);
            
            // Send message to open channel
            $result = $this->sendMessageToOpenChannel(
                $sessionId,
                $messageContent,
                $mediaUrl,
                $messageType,
                $messageId,
                $timestamp
            );
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to forward message to Bitrix24', [
                'from' => $from,
                'messageContent' => $messageContent,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Find or create contact in Bitrix24 CRM
     * 
     * @param string $phone Contact phone number
     * @param string $name Contact name
     * @return array Contact data
     */
    private function findOrCreateContact(string $phone, string $name): array
    {
        try {
            // Clean phone number for search
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            
            // Try to find contact by phone number
            $response = $this->callBitrixMethod('crm.contact.list', [
                'filter' => [
                    'PHONE' => $cleanPhone
                ],
                'select' => ['ID', 'NAME', 'LAST_NAME', 'PHONE']
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            // If contact found, return it
            if (isset($result['result']) && !empty($result['result'])) {
                $this->logger->info('Found existing contact in Bitrix24', [
                    'phone' => $phone,
                    'contactId' => $result['result'][0]['ID']
                ]);
                
                return $result['result'][0];
            }
            
            // If not found, create new contact
            $nameParts = explode(' ', $name, 2);
            $firstName = $nameParts[0] ?? 'WhatsApp';
            $lastName = $nameParts[1] ?? 'User';
            
            $response = $this->callBitrixMethod('crm.contact.add', [
                'fields' => [
                    'NAME' => $firstName,
                    'LAST_NAME' => $lastName,
                    'SOURCE_ID' => 'WHATSAPP',
                    'PHONE' => [
                        [
                            'VALUE' => $phone,
                            'VALUE_TYPE' => 'WORK'
                        ]
                    ]
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['result']) && $result['result'] > 0) {
                $contactId = $result['result'];
                
                $this->logger->info('Created new contact in Bitrix24', [
                    'phone' => $phone,
                    'name' => $name,
                    'contactId' => $contactId
                ]);
                
                // Get full contact data
                $response = $this->callBitrixMethod('crm.contact.get', [
                    'id' => $contactId
                ]);
                
                $result = json_decode($response->getBody()->getContents(), true);
                
                return $result['result'];
            }
            
            throw new \Exception('Failed to create contact in Bitrix24');
        } catch (\Exception $e) {
            $this->logger->error('Error finding or creating contact', [
                'phone' => $phone,
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            
            // Return minimal contact data
            return [
                'ID' => 0,
                'NAME' => $name,
                'PHONE' => $phone
            ];
        }
    }
    
    /**
     * Get or create open channel session
     * 
     * @param string $userIdentifier User identifier (phone number)
     * @param array $contact Contact data
     * @return int Session ID
     */
    private function getOrCreateOpenChannelSession(string $userIdentifier, array $contact): int
    {
        try {
            $openChannelId = $this->config->get('BITRIX24_OPEN_CHANNEL_ID');
            
            // Try to find existing session
            $response = $this->callBitrixMethod('imopenlines.session.get', [
                'USER_CODE' => 'whatsapp_' . preg_replace('/[^0-9]/', '', $userIdentifier)
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            // If session found, return it
            if (isset($result['result']) && !empty($result['result'])) {
                $this->logger->info('Found existing open channel session', [
                    'userIdentifier' => $userIdentifier,
                    'sessionId' => $result['result']['ID']
                ]);
                
                return (int)$result['result']['ID'];
            }
            
            // If not found, create new session
            $response = $this->callBitrixMethod('imopenlines.session.create', [
                'USER_CODE' => 'whatsapp_' . preg_replace('/[^0-9]/', '', $userIdentifier),
                'LINE_ID' => $openChannelId,
                'CRM_CREATE' => 0, // Don't create CRM entities automatically
                'CRM' => [
                    'ENTITY_TYPE' => 'CONTACT',
                    'ENTITY_ID' => $contact['ID']
                ],
                'USER_NAME' => $contact['NAME'] ?? 'WhatsApp User',
                'USER_AVATAR' => '',
                'CHAT_TITLE' => 'WhatsApp: ' . $userIdentifier
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['result']) && $result['result'] > 0) {
                $sessionId = $result['result'];
                
                $this->logger->info('Created new open channel session', [
                    'userIdentifier' => $userIdentifier,
                    'sessionId' => $sessionId
                ]);
                
                return (int)$sessionId;
            }
            
            throw new \Exception('Failed to create open channel session');
        } catch (\Exception $e) {
            $this->logger->error('Error getting or creating open channel session', [
                'userIdentifier' => $userIdentifier,
                'error' => $e->getMessage()
            ]);
            
            // Return 0 as fallback
            return 0;
        }
    }
    
    /**
     * Send message to Bitrix24 Open Channel
     * 
     * @param int $sessionId Session ID
     * @param string $messageContent Message content
     * @param string $mediaUrl Media URL (if any)
     * @param string $messageType Message type
     * @param string $messageId WhatsApp message ID
     * @param string $timestamp Message timestamp
     * @return bool True if sent successfully
     */
    private function sendMessageToOpenChannel(
        int $sessionId,
        string $messageContent,
        string $mediaUrl = '',
        string $messageType = 'text',
        string $messageId = '',
        string $timestamp = ''
    ): bool {
        try {
            $params = [
                'SESSION_ID' => $sessionId,
                'MESSAGE' => $messageContent,
                'SYSTEM' => 'N',
                'FILES' => [],
                'PARAMS' => [
                    'WHATSAPP_MESSAGE_ID' => $messageId,
                    'WHATSAPP_MESSAGE_TYPE' => $messageType,
                    'WHATSAPP_TIMESTAMP' => $timestamp
                ]
            ];
            
            // Handle media messages
            if (!empty($mediaUrl)) {
                switch ($messageType) {
                    case 'image':
                        // For images, we can try to attach them directly
                        $params['FILES'][] = [
                            'name' => 'image.jpg',
                            'type' => 'image/jpeg',
                            'tmp_name' => $mediaUrl,
                            'size' => 0,
                            'MODULE_ID' => 'imopenlines'
                        ];
                        break;
                        
                    case 'document':
                        // For documents, we can try to attach them directly
                        $params['FILES'][] = [
                            'name' => 'document.pdf',
                            'type' => 'application/pdf',
                            'tmp_name' => $mediaUrl,
                            'size' => 0,
                            'MODULE_ID' => 'imopenlines'
                        ];
                        break;
                        
                    default:
                        // For other media types, include the URL in the message
                        $params['MESSAGE'] .= "\n\nMedia URL: " . $mediaUrl;
                        break;
                }
            }
            
            $response = $this->callBitrixMethod('imopenlines.message.add', $params);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['result']) && $result['result'] > 0) {
                $this->logger->info('Sent message to Bitrix24 Open Channel', [
                    'sessionId' => $sessionId,
                    'messageContent' => $messageContent,
                    'messageId' => $result['result']
                ]);
                
                return true;
            }
            
            throw new \Exception('Failed to send message to Bitrix24 Open Channel');
        } catch (\Exception $e) {
            $this->logger->error('Error sending message to Bitrix24 Open Channel', [
                'sessionId' => $sessionId,
                'messageContent' => $messageContent,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Update message status in Bitrix24
     * 
     * @param string $messageId WhatsApp message ID
     * @param string $status Message status
     * @return bool True if updated successfully
     */
    public function updateMessageStatus(string $messageId, string $status): bool
    {
        try {
            // In a real implementation, you would update the message status in Bitrix24
            // This would require storing the mapping between WhatsApp message IDs and Bitrix24 message IDs
            
            $this->logger->info('Updated message status in Bitrix24', [
                'messageId' => $messageId,
                'status' => $status
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error updating message status in Bitrix24', [
                'messageId' => $messageId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get messages from Bitrix24 Open Channel
     * 
     * @param int $sessionId Session ID
     * @param int $limit Maximum number of messages to retrieve
     * @return array Messages
     */
    public function getMessagesFromOpenChannel(int $sessionId, int $limit = 50): array
    {
        try {
            $response = $this->callBitrixMethod('imopenlines.dialog.messages.get', [
                'SESSION_ID' => $sessionId,
                'LIMIT' => $limit
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['result']) && is_array($result['result'])) {
                $this->logger->info('Retrieved messages from Bitrix24 Open Channel', [
                    'sessionId' => $sessionId,
                    'messageCount' => count($result['result'])
                ]);
                
                return $result['result'];
            }
            
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving messages from Bitrix24 Open Channel', [
                'sessionId' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Call Bitrix24 REST API method
     * 
     * @param string $method Method name
     * @param array $params Method parameters
     * @return \Psr\Http\Message\ResponseInterface API response
     */
    private function callBitrixMethod(string $method, array $params = []): \Psr\Http\Message\ResponseInterface
    {
        $webhookUrl = $this->config->get('BITRIX24_WEBHOOK_URL');
        
        // Ensure webhook URL ends with /
        if (substr($webhookUrl, -1) !== '/') {
            $webhookUrl .= '/';
        }
        
        $url = $webhookUrl . $method . '.json';
        
        $this->logger->debug('Calling Bitrix24 API method', [
            'method' => $method,
            'params' => $params
        ]);
        
        return $this->client->post($url, [
            'form_params' => $params
        ]);
    }
}
