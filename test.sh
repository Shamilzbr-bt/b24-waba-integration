#!/bin/bash

# WhatsApp Bitrix24 Integration - Test Script
# This script tests the end-to-end integration between WhatsApp and Bitrix24

echo "Starting WhatsApp-Bitrix24 Integration Test..."

# Check if the configuration file exists
if [ ! -f "config/.env" ]; then
    echo "Error: Configuration file not found. Please run setup first."
    exit 1
fi

# Test API status endpoint
echo "Testing API status endpoint..."
STATUS_RESPONSE=$(curl -s http://localhost/api/status)
echo "Status response: $STATUS_RESPONSE"

# Check if WhatsApp is connected
WHATSAPP_CONNECTED=$(echo $STATUS_RESPONSE | grep -o '"whatsapp":true')
if [ -z "$WHATSAPP_CONNECTED" ]; then
    echo "Error: WhatsApp API is not connected."
    echo "Please check your WhatsApp API configuration."
    exit 1
fi

# Check if Bitrix24 is connected
BITRIX_CONNECTED=$(echo $STATUS_RESPONSE | grep -o '"bitrix24":true')
if [ -z "$BITRIX_CONNECTED" ]; then
    echo "Error: Bitrix24 is not connected."
    echo "Please check your Bitrix24 configuration."
    exit 1
fi

echo "Connection status check passed."

# Test sending a message to WhatsApp
echo "Testing message sending to WhatsApp..."
TEST_PHONE="1234567890" # Replace with a real test phone number in production
TEST_MESSAGE="This is a test message from the WhatsApp-Bitrix24 Integration."

SEND_RESPONSE=$(curl -s -X POST http://localhost/api/test-send \
    -H "Content-Type: application/json" \
    -d "{\"to\":\"$TEST_PHONE\",\"message\":\"$TEST_MESSAGE\"}")

echo "Send response: $SEND_RESPONSE"

# Check if message was sent successfully
MESSAGE_SENT=$(echo $SEND_RESPONSE | grep -o '"success":true')
if [ -z "$MESSAGE_SENT" ]; then
    echo "Error: Failed to send test message."
    echo "Please check your WhatsApp API configuration and logs."
    exit 1
fi

echo "Message sending test passed."

# Test webhook processing
echo "Testing webhook processing..."
WEBHOOK_PAYLOAD='{
    "object": "whatsapp_business_account",
    "entry": [{
        "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
        "changes": [{
            "field": "messages",
            "value": {
                "messaging_product": "whatsapp",
                "metadata": {
                    "display_phone_number": "PHONE_NUMBER",
                    "phone_number_id": "PHONE_NUMBER_ID"
                },
                "contacts": [{
                    "profile": {
                        "name": "Test User"
                    },
                    "wa_id": "1234567890"
                }],
                "messages": [{
                    "from": "1234567890",
                    "id": "wamid.test123",
                    "timestamp": "1650000000",
                    "type": "text",
                    "text": {
                        "body": "This is a test response"
                    }
                }]
            }
        }]
    }]
}'

WEBHOOK_RESPONSE=$(curl -s -X POST http://localhost/api/test-webhook \
    -H "Content-Type: application/json" \
    -d "$WEBHOOK_PAYLOAD")

echo "Webhook response: $WEBHOOK_RESPONSE"

# Check if webhook was processed successfully
WEBHOOK_PROCESSED=$(echo $WEBHOOK_RESPONSE | grep -o '"success":true')
if [ -z "$WEBHOOK_PROCESSED" ]; then
    echo "Error: Failed to process test webhook."
    echo "Please check your webhook configuration and logs."
    exit 1
fi

echo "Webhook processing test passed."

echo "All tests passed successfully!"
echo "The WhatsApp-Bitrix24 Integration is working correctly."
