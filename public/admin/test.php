<?php
/**
 * WhatsApp Bitrix24 Integration - Connection Test
 * 
 * This file provides a simple UI for testing the WhatsApp-Bitrix24 integration.
 */

// Require the Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Use the IntegrationService class
use App\Utils\IntegrationService;
use App\Config\ConfigManager;

// Create integration service
$integrationService = new IntegrationService();

// Check connection status
$status = $integrationService->checkConnectionStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Bitrix24 Integration - Test</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>WhatsApp Bitrix24 Integration Test</h1>
            <p>Use this page to test the WhatsApp-Bitrix24 integration</p>
        </header>

        <div class="setup-container">
            <div class="setup-content">
                <h2>Connection Status</h2>
                
                <div class="status-container">
                    <div class="status-item <?php echo $status['whatsapp'] ? 'success' : 'error'; ?>">
                        <h3>WhatsApp API</h3>
                        <p><?php echo $status['whatsapp'] ? 'Connected' : 'Not Connected'; ?></p>
                        <?php if ($status['whatsapp'] && isset($status['whatsapp_info'])): ?>
                            <div class="status-details">
                                <p><strong>Phone Number:</strong> <?php echo $status['whatsapp_info']['display_phone_number'] ?? 'N/A'; ?></p>
                                <p><strong>Phone ID:</strong> <?php echo $status['whatsapp_info']['id'] ?? 'N/A'; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="status-item <?php echo $status['bitrix24'] ? 'success' : 'error'; ?>">
                        <h3>Bitrix24</h3>
                        <p><?php echo $status['bitrix24'] ? 'Connected' : 'Not Connected'; ?></p>
                        <?php if ($status['bitrix24'] && isset($status['bitrix24_info'])): ?>
                            <div class="status-details">
                                <p><strong>Domain:</strong> <?php echo $status['bitrix24_info']['domain'] ?? 'N/A'; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($status['errors'])): ?>
                    <div class="error-container">
                        <h3>Errors</h3>
                        <ul>
                            <?php foreach ($status['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <h2>Send Test Message</h2>
                
                <form id="test-form">
                    <div class="form-group">
                        <label for="to">Recipient Phone Number</label>
                        <input type="text" id="to" name="to" required placeholder="e.g., 1234567890">
                        <small>Include country code without + or 00 (e.g., 1234567890)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="media_type">Media Type (Optional)</label>
                        <select id="media_type" name="media_type">
                            <option value="">None</option>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                            <option value="audio">Audio</option>
                            <option value="document">Document</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="media-url-group" style="display: none;">
                        <label for="media_url">Media URL</label>
                        <input type="text" id="media_url" name="media_url" placeholder="https://example.com/image.jpg">
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="submit-btn">Send Test Message</button>
                    </div>
                </form>
                
                <div id="result" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mediaTypeSelect = document.getElementById('media_type');
            const mediaUrlGroup = document.getElementById('media-url-group');
            const mediaUrlInput = document.getElementById('media_url');
            const testForm = document.getElementById('test-form');
            const resultDiv = document.getElementById('result');
            
            // Show/hide media URL field based on media type selection
            mediaTypeSelect.addEventListener('change', function() {
                if (this.value) {
                    mediaUrlGroup.style.display = 'block';
                    mediaUrlInput.required = true;
                } else {
                    mediaUrlGroup.style.display = 'none';
                    mediaUrlInput.required = false;
                }
            });
            
            // Handle form submission
            testForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const to = document.getElementById('to').value;
                const message = document.getElementById('message').value;
                const mediaType = document.getElementById('media_type').value;
                const mediaUrl = document.getElementById('media_url').value;
                
                // Create request payload
                const payload = {
                    to: to,
                    message: message
                };
                
                if (mediaType && mediaUrl) {
                    payload.media_type = mediaType;
                    payload.media_url = mediaUrl;
                }
                
                // Show loading state
                const submitButton = document.querySelector('.submit-btn');
                const originalText = submitButton.textContent;
                submitButton.textContent = 'Sending...';
                submitButton.disabled = true;
                
                // Send request to API
                fetch('/api/test-send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    // Show result
                    resultDiv.style.display = 'block';
                    
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="success-message">Message sent successfully!</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="error-message">Failed to send message: ' + (data.error || 'Unknown error') + '</div>';
                    }
                    
                    // Reset form state
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                })
                .catch(error => {
                    // Show error
                    resultDiv.style.display = 'block';
                    resultDiv.innerHTML = '<div class="error-message">Error: ' + error.message + '</div>';
                    
                    // Reset form state
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                });
            });
        });
    </script>
</body>
</html>
