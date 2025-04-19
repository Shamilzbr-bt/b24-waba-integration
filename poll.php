<?php
/**
 * WhatsApp Bitrix24 Integration - Polling Script
 * 
 * This script polls Bitrix24 for new messages and relays them to WhatsApp.
 * It should be run as a cron job or daemon process.
 */

// Require the Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Use the MessageRelayService class
use App\Utils\MessageRelayService;

// Create message relay service
$relayService = new MessageRelayService();

// Poll and relay messages
$messagesRelayed = $relayService->pollAndRelayMessages();

// Output result
echo "Messages relayed: {$messagesRelayed}" . PHP_EOL;
