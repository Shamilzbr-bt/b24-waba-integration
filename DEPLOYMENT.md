# WhatsApp Bitrix24 Integration - Deployment Guide

This document provides instructions for deploying the WhatsApp Bitrix24 Integration on an AWS EC2 t2.micro instance.

## Prerequisites

Before deploying, ensure you have:

1. An AWS EC2 t2.micro instance running Ubuntu 22.04
2. A domain name pointing to your EC2 instance
3. A WhatsApp Business Account with API access
4. A Bitrix24 account with admin access

## Deployment Steps

### 1. Prepare EC2 Instance

SSH into your EC2 instance:

```bash
ssh -i your-key.pem ubuntu@your-instance-ip
```

Update the system:

```bash
sudo apt-get update
sudo apt-get upgrade -y
```

Install required packages:

```bash
sudo apt-get install -y php php-cli php-fpm php-json php-common php-mysql php-zip php-gd php-mbstring php-curl php-xml php-pear php-bcmath nginx certbot python3-certbot-nginx git
```

### 2. Set Up SSL Certificate

Obtain an SSL certificate using Certbot:

```bash
sudo certbot --nginx -d your-domain.com
```

Follow the prompts to complete the SSL setup.

### 3. Clone Repository

Clone the WhatsApp Bitrix24 Integration repository:

```bash
cd ~
git clone https://github.com/your-repo/whatsapp-bitrix-integration.git
cd whatsapp-bitrix-integration
```

Alternatively, upload your code to the server using SCP or SFTP.

### 4. Install Dependencies

Install Composer:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Install PHP dependencies:

```bash
composer install --no-dev
```

### 5. Configure Web Server

Create Nginx configuration:

```bash
sudo cp config/nginx.conf /etc/nginx/sites-available/whatsapp-bitrix.conf
```

Edit the configuration to match your domain:

```bash
sudo nano /etc/nginx/sites-available/whatsapp-bitrix.conf
```

Update the `server_name` directive with your domain name and adjust paths if necessary.

Enable the site:

```bash
sudo ln -sf /etc/nginx/sites-available/whatsapp-bitrix.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 6. Configure Application

Create the environment configuration file:

```bash
cp config/.env.example config/.env
nano config/.env
```

Update the following settings:

```
# WhatsApp Meta API Configuration
WHATSAPP_API_VERSION=v18.0
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_BUSINESS_ACCOUNT_ID=your_business_account_id
WHATSAPP_API_TOKEN=your_api_token
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_verify_token

# Bitrix24 Configuration
BITRIX24_DOMAIN=your_bitrix24_domain.bitrix24.com
BITRIX24_WEBHOOK_URL=https://your_bitrix24_domain.bitrix24.com/rest/your_webhook_code
BITRIX24_USER_ID=your_user_id
BITRIX24_OPEN_CHANNEL_ID=your_open_channel_id

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_LOG_LEVEL=info
```

### 7. Set Up Permissions

Set proper permissions for the application:

```bash
sudo chown -R www-data:www-data /home/ubuntu/whatsapp-bitrix-integration
sudo chmod -R 755 /home/ubuntu/whatsapp-bitrix-integration
sudo chmod -R 775 /home/ubuntu/whatsapp-bitrix-integration/logs
```

### 8. Configure WhatsApp Webhook

1. Log in to your Meta Business Manager
2. Navigate to your WhatsApp Business Account
3. Go to API Setup
4. Configure the webhook URL: `https://your-domain.com/webhook/`
5. Use the same verify token you set in your .env file
6. Subscribe to the following webhook fields:
   - messages
   - message_status_updates

### 9. Install Bitrix24 App

1. Log in to your Bitrix24 account as an administrator
2. Go to Applications > Developer Resources
3. Click "Create New Application"
4. Enter the app details:
   - Name: WhatsApp Meta API Connector
   - Description: Connect WhatsApp Business API to Bitrix24 Open Channels
   - URL: https://your-domain.com/admin/
   - Scope: imopenlines, crm, im
5. In the "Placement" section, add a Contact Center placement
6. Set the handler URL to: https://your-domain.com/admin/setup.php
7. Save the application

### 10. Set Up Message Polling

Create a cron job to poll for messages from Bitrix24:

```bash
sudo crontab -e
```

Add the following line to run the polling script every minute:

```
* * * * * php /home/ubuntu/whatsapp-bitrix-integration/poll.php >> /home/ubuntu/whatsapp-bitrix-integration/logs/cron.log 2>&1
```

### 11. Test the Integration

Run the test script to verify the integration:

```bash
cd /home/ubuntu/whatsapp-bitrix-integration
chmod +x test.sh
./test.sh
```

If all tests pass, your integration is working correctly.

## Troubleshooting

### Check Logs

If you encounter issues, check the application logs:

```bash
tail -f /home/ubuntu/whatsapp-bitrix-integration/logs/*.log
```

### Verify Webhook

Test the webhook manually:

```bash
curl -X GET "https://your-domain.com/webhook/?hub.mode=subscribe&hub.challenge=challenge_code&hub.verify_token=your_webhook_verify_token"
```

You should receive the challenge code as the response.

### Check Connection Status

Visit the status page to check the connection status:

```
https://your-domain.com/api/status
```

## Maintenance

### Updating the Application

To update the application:

```bash
cd /home/ubuntu/whatsapp-bitrix-integration
git pull
composer install --no-dev
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

### Backup

Regularly backup your configuration:

```bash
cp /home/ubuntu/whatsapp-bitrix-integration/config/.env /home/ubuntu/whatsapp-bitrix-backup.env
```

## Security Considerations

1. Keep your API tokens and webhook verify tokens secure
2. Regularly update your server and dependencies
3. Monitor your logs for suspicious activity
4. Consider implementing IP restrictions for admin access
5. Use strong passwords for all accounts

## Support

If you encounter any issues with the integration, please contact support at support@example.com.
