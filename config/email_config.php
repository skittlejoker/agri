<?php
// Email Configuration for PHPMailer
// Using Gmail SMTP
//
// IMPORTANT: To use Gmail SMTP, you need to:
// 1. Enable 2-Step Verification on your Gmail account
// 2. Generate an App Password: https://myaccount.google.com/apppasswords
// 3. Replace 'your-app-password' below with your generated app password
// 4. Do NOT use your regular Gmail password - it won't work!

return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465, // Using port 465 (SSL) - more reliable than 587
    'smtp_username' => 'trancem260@gmail.com', // Your Gmail address
    'smtp_password' => 'wwhxqyiwuasoqjle', // Gmail App Password (no spaces, no hyphens)
    'smtp_from_email' => 'trancem260@gmail.com',
    'smtp_from_name' => 'AgriMarket - Agriculture Platform',
    'smtp_encryption' => 'ssl', // Use 'ssl' for port 465, or 'tls' for port 587
    'smtp_debug' => 2 // Set to 2 for verbose debugging (helpful for troubleshooting) - ENABLED FOR DEBUGGING
];
