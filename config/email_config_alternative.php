<?php
// Alternative Email Configuration for PHPMailer
// Use this if port 587 (TLS) doesn't work - try port 465 (SSL) instead
//
// To use this configuration:
// 1. Rename this file to email_config.php (backup the original first)
// 2. Or copy the settings below to your email_config.php

return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465, // Alternative port - use SSL instead of TLS
    'smtp_username' => 'trancem260@gmail.com', // Your Gmail address
    'smtp_password' => 'wwhxqyiwuasoqjle', // Gmail App Password (no spaces, no hyphens)
    'smtp_from_email' => 'trancem260@gmail.com',
    'smtp_from_name' => 'AgriMarket - Agriculture Platform',
    'smtp_encryption' => 'ssl', // Use 'ssl' for port 465, or 'tls' for port 587
    'smtp_debug' => 2 // Set to 2 for verbose debugging (helpful for troubleshooting) - ENABLED FOR DEBUGGING
];






