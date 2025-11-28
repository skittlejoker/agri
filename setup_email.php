<?php
/**
 * Email Configuration Setup Wizard
 * Interactive setup page to configure Gmail App Password
 * Access via: http://localhost/E-commerce/agriculture-marketplace/setup_email.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$configFile = __DIR__ . '/config/email_config.php';
$message = '';
$messageType = '';
$currentConfig = [];

// Load current config
if (file_exists($configFile)) {
    $currentConfig = require $configFile;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_password'])) {
    $appPassword = trim($_POST['app_password']);
    $gmailEmail = trim($_POST['gmail_email'] ?? 'trancem260@gmail.com');
    
    if (empty($appPassword)) {
        $message = 'App Password cannot be empty!';
        $messageType = 'error';
    } elseif (strlen(str_replace(' ', '', $appPassword)) < 16) {
        $message = 'App Password should be 16 characters (spaces will be removed)';
        $messageType = 'error';
    } else {
        // Remove spaces from app password
        $appPassword = str_replace(' ', '', $appPassword);
        
        // Update config file
        $configContent = "<?php
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
    'smtp_port' => 587,
    'smtp_username' => '{$gmailEmail}', // Your Gmail address
    'smtp_password' => '{$appPassword}', // Gmail App Password
    'smtp_from_email' => '{$gmailEmail}',
    'smtp_from_name' => 'AgriMarket - Agriculture Platform',
    'smtp_encryption' => 'tls', // Use 'tls' for port 587, or 'ssl' for port 465
    'smtp_debug' => 0 // Set to 2 for verbose debugging (helpful for troubleshooting)
];
";
        
        if (file_put_contents($configFile, $configContent)) {
            $message = 'Email configuration updated successfully!';
            $messageType = 'success';
            
            // Reload config
            $currentConfig = require $configFile;
            
            // Test email sending
            require_once __DIR__ . '/config/database.php';
            require_once __DIR__ . '/config/phpmailer_helper.php';
            
            $testResult = sendOTPEmail($gmailEmail, 'TestUser', '123456');
            if ($testResult['success']) {
                $message .= ' Test email sent successfully! Check your inbox.';
            } else {
                $message .= ' Configuration saved, but test email failed: ' . $testResult['message'];
                $messageType = 'warning';
            }
        } else {
            $message = 'Failed to save configuration file. Please check file permissions.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration Setup - AgriMarket</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .step h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .step ol {
            margin-left: 20px;
            color: #555;
            line-height: 1.8;
        }
        .step a {
            color: #667eea;
            text-decoration: none;
        }
        .step a:hover {
            text-decoration: underline;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        input[type="email"],
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .status {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-label {
            color: #666;
        }
        .status-value {
            font-weight: 500;
        }
        .status-value.success {
            color: #28a745;
        }
        .status-value.error {
            color: #dc3545;
        }
        .test-link {
            margin-top: 20px;
            text-align: center;
        }
        .test-link a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Configuration Setup</h1>
        <p class="subtitle">Configure Gmail App Password for OTP Email Sending</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="step">
            <h3>Step 1: Get Your Gmail App Password</h3>
            <ol>
                <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">Google App Passwords</a></li>
                <li>Select "Mail" as the app</li>
                <li>Select "Other (Custom name)" as device</li>
                <li>Enter name: <strong>AgriMarket</strong></li>
                <li>Click "Generate"</li>
                <li><strong>Copy the 16-character password</strong> (it looks like: abcd efgh ijkl mnop)</li>
            </ol>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="gmail_email">Gmail Address:</label>
                <input type="email" id="gmail_email" name="gmail_email" 
                       value="<?php echo htmlspecialchars($currentConfig['smtp_username'] ?? 'trancem260@gmail.com'); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="app_password">Gmail App Password:</label>
                <input type="text" id="app_password" name="app_password" 
                       placeholder="Enter 16-character App Password (spaces will be removed)"
                       value="<?php echo htmlspecialchars($currentConfig['smtp_password'] === 'your-app-password' ? '' : $currentConfig['smtp_password'] ?? ''); ?>"
                       required>
                <small style="color: #666; display: block; margin-top: 5px;">
                    Paste your App Password here (spaces will be automatically removed)
                </small>
            </div>
            
            <button type="submit" class="btn">💾 Save Configuration & Test</button>
        </form>
        
        <div class="status">
            <h3 style="margin-bottom: 15px;">Current Configuration Status:</h3>
            <div class="status-item">
                <span class="status-label">Gmail Email:</span>
                <span class="status-value <?php echo !empty($currentConfig['smtp_username']) ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($currentConfig['smtp_username'] ?? 'Not set'); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">App Password:</span>
                <span class="status-value <?php echo (!empty($currentConfig['smtp_password']) && $currentConfig['smtp_password'] !== 'your-app-password') ? 'success' : 'error'; ?>">
                    <?php 
                    if (empty($currentConfig['smtp_password']) || $currentConfig['smtp_password'] === 'your-app-password') {
                        echo 'Not configured';
                    } else {
                        echo '✅ Configured (' . substr($currentConfig['smtp_password'], 0, 4) . '****)';
                    }
                    ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">SMTP Host:</span>
                <span class="status-value success"><?php echo htmlspecialchars($currentConfig['smtp_host'] ?? 'smtp.gmail.com'); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">Port:</span>
                <span class="status-value success"><?php echo htmlspecialchars($currentConfig['smtp_port'] ?? '587'); ?></span>
            </div>
        </div>
        
        <div class="test-link">
            <a href="test_email.php">🧪 Test Email Sending</a> | 
            <a href="EMAIL_SETUP_INSTRUCTIONS.md" target="_blank">📖 View Full Instructions</a>
        </div>
    </div>
</body>
</html>








