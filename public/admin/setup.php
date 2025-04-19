<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Meta API - Bitrix24 Integration Setup</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>WhatsApp Meta API Integration</h1>
            <p>Connect your WhatsApp Business Account to Bitrix24 Open Channels</p>
        </header>

        <div class="setup-container">
            <div class="setup-steps">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-title">WhatsApp Business Account</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-title">API Configuration</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-title">Bitrix24 Settings</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-title">Confirmation</div>
                </div>
            </div>

            <div class="setup-content">
                <form id="setup-form">
                    <!-- Step 1: WhatsApp Business Account -->
                    <div class="step-content active" data-step="1">
                        <h2>WhatsApp Business Account</h2>
                        <p>Enter your WhatsApp Business Account information.</p>
                        
                        <div class="form-group">
                            <label for="business_account_id">WhatsApp Business Account ID</label>
                            <input type="text" id="business_account_id" name="business_account_id" required>
                            <small>You can find this in your Meta Business Manager</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_number_id">WhatsApp Phone Number ID</label>
                            <input type="text" id="phone_number_id" name="phone_number_id" required>
                            <small>The ID of your WhatsApp Business phone number</small>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" class="next-btn">Next</button>
                        </div>
                    </div>
                    
                    <!-- Step 2: API Configuration -->
                    <div class="step-content" data-step="2">
                        <h2>API Configuration</h2>
                        <p>Configure your WhatsApp API connection.</p>
                        
                        <div class="form-group">
                            <label for="api_token">WhatsApp API Token</label>
                            <input type="text" id="api_token" name="api_token" required>
                            <small>Your permanent access token from Meta Business Manager</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="webhook_verify_token">Webhook Verify Token</label>
                            <input type="text" id="webhook_verify_token" name="webhook_verify_token" required>
                            <small>Create a unique token for webhook verification</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="webhook_url">Webhook URL</label>
                            <input type="text" id="webhook_url" name="webhook_url" readonly>
                            <small>Configure this URL in your Meta Business Manager</small>
                            <button type="button" id="copy-webhook-url" class="btn-small">Copy</button>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" class="prev-btn">Previous</button>
                            <button type="button" class="next-btn">Next</button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Bitrix24 Settings -->
                    <div class="step-content" data-step="3">
                        <h2>Bitrix24 Settings</h2>
                        <p>Configure your Bitrix24 Open Channel settings.</p>
                        
                        <div class="form-group">
                            <label for="bitrix_domain">Bitrix24 Domain</label>
                            <input type="text" id="bitrix_domain" name="bitrix_domain" required>
                            <small>Your Bitrix24 domain (e.g., company.bitrix24.com)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="bitrix_webhook_url">Bitrix24 Webhook URL</label>
                            <input type="text" id="bitrix_webhook_url" name="bitrix_webhook_url" required>
                            <small>Your Bitrix24 webhook URL with access to required scopes</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="bitrix_user_id">Bitrix24 User ID</label>
                            <input type="text" id="bitrix_user_id" name="bitrix_user_id" required>
                            <small>User ID that will be used for Open Channel operations</small>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" class="prev-btn">Previous</button>
                            <button type="button" class="next-btn">Next</button>
                        </div>
                    </div>
                    
                    <!-- Step 4: Confirmation -->
                    <div class="step-content" data-step="4">
                        <h2>Confirmation</h2>
                        <p>Review your settings and confirm the integration.</p>
                        
                        <div class="summary">
                            <h3>WhatsApp Business Account</h3>
                            <div class="summary-item">
                                <span class="label">Business Account ID:</span>
                                <span class="value" id="summary-business-account-id"></span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Phone Number ID:</span>
                                <span class="value" id="summary-phone-number-id"></span>
                            </div>
                            
                            <h3>API Configuration</h3>
                            <div class="summary-item">
                                <span class="label">API Token:</span>
                                <span class="value">●●●●●●●●●●●●●●●●</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Webhook URL:</span>
                                <span class="value" id="summary-webhook-url"></span>
                            </div>
                            
                            <h3>Bitrix24 Settings</h3>
                            <div class="summary-item">
                                <span class="label">Bitrix24 Domain:</span>
                                <span class="value" id="summary-bitrix-domain"></span>
                            </div>
                            <div class="summary-item">
                                <span class="label">User ID:</span>
                                <span class="value" id="summary-bitrix-user-id"></span>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" class="prev-btn">Previous</button>
                            <button type="submit" class="submit-btn">Confirm & Connect</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/setup.js"></script>
</body>
</html>
