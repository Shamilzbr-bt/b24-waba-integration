<?php

namespace App\Utils;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Config\ConfigManager;

/**
 * Message Relay Service
 * 
 * Handles relaying messages between Bitrix24 and WhatsApp
 */
class MessageRelayService
{
    private $config;
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        
        // Set up logger
        $this->logger = new Logger('relay');
        $this->logger->pushHandler(new StreamHandler(
            dirname(__DIR__, 2) . '/logs/relay.log',
            $this->config->get('APP_DEBUG') ? Logger::DEBUG : Logger::INFO
        ));
    }
    
    /**
     * Poll Bitrix24 for new messages and relay to WhatsApp
     * 
     * @return int Number of messages relayed
     */
    public function pollAndRelayMessages(): int
    {
        $this->logger->info('Starting message polling');
        
        try {
            // Get active sessions from Bitrix24
            $bitrixClient = new \App\Bitrix\BitrixClient();
            $sessions = $this->getActiveSessions($bitrixClient);
            
            $messagesRelayed = 0;
            
            foreach ($sessions as $session) {
                $sessionId = $session['ID'];
                $userCode = $session['USER_CODE'] ?? '';
                
                // Skip sessions that are not WhatsApp sessions
                if (!$this->isWhatsAppSession($userCode)) {
                    continue;
                }
                
                // Get phone number from user code
                $phoneNumber = $this->extractPhoneNumber($userCode);
                if (empty($phoneNumber)) {
                    continue;
                }
                
                // Get messages from this session
                $messages = $bitrixClient->getMessagesFromOpenChannel($sessionId);
                
                // Filter for agent messages that haven't been sent to WhatsApp yet
                $newAgentMessages = $this->filterNewAgentMessages($messages);
                
                // Send each message to WhatsApp
                foreach ($newAgentMessages as $message) {
                    $this->relayMessageToWhatsApp($phoneNumber, $message);
                    $messagesRelayed++;
                }
            }
            
            $this->logger->info('Completed message polling', [
                'messagesRelayed' => $messagesRelayed
            ]);
            
            return $messagesRelayed;
        } catch (\Exception $e) {
            $this->logger->error('Error in message polling', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
    
    /**
     * Get active sessions from Bitrix24
     * 
     * @param \App\Bitrix\BitrixClient $bitrixClient Bitrix24 client
     * @return array Active sessions
     */
    private function getActiveSessions(\App\Bitrix\BitrixClient $bitrixClient): array
    {
        // In a real implementation, you would call the Bitrix24 API to get active sessions
        // For now, we'll return an empty array
        return [];
    }
    
    /**
     * Check if session is a WhatsApp session
     * 
     * @param string $userCode User code
     * @return bool True if WhatsApp session
     */
    private function isWhatsAppSession(string $userCode): bool
    {
        return strpos($userCode, 'whatsapp_') === 0;
    }
    
    /**
     * Extract phone number from user code
     * 
     * @param string $userCode User code
     * @return string Phone number
     */
    private function extractPhoneNumber(string $userCode): string
    {
        // User code format: whatsapp_1234567890
        $parts = explode('_', $userCode);
        return $parts[1] ?? '';
    }
    
    /**
     * Filter for new agent messages
     * 
     * @param array $messages Messages
     * @return array New agent messages
     */
    private function filterNewAgentMessages(array $messages): array
    {
        $newAgentMessages = [];
        
        foreach ($messages as $message) {
            // Check if message is from agent and hasn't been sent to WhatsApp yet
            if (
                isset($message['AUTHOR_ID']) && 
                $message['AUTHOR_ID'] > 0 && // Agent messages have positive author IDs
                empty($message['PARAMS']['WHATSAPP_SENT']) // Message hasn't been sent to WhatsApp yet
            ) {
                $newAgentMessages[] = $message;
            }
        }
        
        return $newAgentMessages;
    }
    
    /**
     * Relay message to WhatsApp
     * 
     * @param string $phoneNumber Phone number
     * @param array $message Message
     * @return bool True if relayed successfully
     */
    private function relayMessageToWhatsApp(string $phoneNumber, array $message): bool
    {
        try {
            $whatsAppClient = new \App\WhatsApp\WhatsAppClient();
            
            // Get message content
            $messageContent = $message['MESSAGE'] ?? '';
            
            // Check for files
            $files = $message['FILES'] ?? [];
            
            if (!empty($files)) {
                // Handle file messages
                foreach ($files as $file) {
                    // Determine media type based on file extension
                    $fileUrl = $file['URL'] ?? '';
                    $fileName = $file['NAME'] ?? '';
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    $mediaType = 'document'; // Default
                    
                    // Determine media type based on extension
                    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $mediaType = 'image';
                    } elseif (in_array($fileExt, ['mp4', 'mov', 'avi'])) {
                        $mediaType = 'video';
                    } elseif (in_array($fileExt, ['mp3', 'wav', 'ogg'])) {
                        $mediaType = 'audio';
                    }
                    
                    // Send media message
                    $result = $whatsAppClient->sendMediaMessage(
                        $phoneNumber,
                        $mediaType,
                        $fileUrl,
                        $messageContent
                    );
                    
                    // Mark message as sent to WhatsApp
                    $this->markMessageAsSent($message['ID']);
                    
                    return !isset($result['error']);
                }
            } else {
                // Send text message
                $result = $whatsAppClient->sendTextMessage($phoneNumber, $messageContent);
                
                // Mark message as sent to WhatsApp
                $this->markMessageAsSent($message['ID']);
                
                return !isset($result['error']);
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error relaying message to WhatsApp', [
                'phoneNumber' => $phoneNumber,
                'messageId' => $message['ID'] ?? '',
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Mark message as sent to WhatsApp
     * 
     * @param int $messageId Message ID
     * @return bool True if marked successfully
     */
    private function markMessageAsSent(int $messageId): bool
    {
        try {
            // In a real implementation, you would update the message in Bitrix24
            // to mark it as sent to WhatsApp
            
            $this->logger->info('Marked message as sent to WhatsApp', [
                'messageId' => $messageId
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error marking message as sent to WhatsApp', [
                'messageId' => $messageId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
