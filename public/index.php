<?php
/**
 * WhatsApp Bitrix24 Integration - Main Entry Point
 * 
 * This file serves as the main entry point for the WhatsApp Bitrix24 Integration application.
 * It will display a simple status page and provide links to the admin interface.
 */

// Display a simple status page
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Bitrix24 Integration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4CAF50;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .status {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .status.online {
            border-left: 5px solid #4CAF50;
        }
        .status.offline {
            border-left: 5px solid #F44336;
            background: #ffebee;
        }
        .links a {
            display: inline-block;
            margin-right: 15px;
            text-decoration: none;
            color: #2196F3;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>WhatsApp Bitrix24 Integration</h1>
        
        <div class="status online">
            <h3>Status: Online</h3>
            <p>The WhatsApp Bitrix24 Integration middleware is running correctly.</p>
        </div>
        
        <div class="info">
            <h3>System Information</h3>
            <p>PHP Version: <?php echo phpversion(); ?></p>
            <p>Server Time: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <div class="links">
            <h3>Quick Links</h3>
            <a href="/admin">Admin Panel</a>
            <a href="/webhook">Webhook Endpoint</a>
        </div>
    </div>
</body>
</html>
