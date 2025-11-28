<?php
/**
 * OTP Email Debugging Script
 * This script will test OTP email sending and show detailed error information
 * Access via: http://localhost/E-commerce/agriculture-marketplace/debug_otp.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/phpmailer_helper.php';

// Test email - CHANGE THIS
$testEmail = 'trancem260@gmail.com';
$testUsername = 'TestUser';

echo "<!DOCTYPE html><html><head><title>OTP Email Debug</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #333; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔍 OTP Email Debugging Tool</h1>";

// Check PHPMailer
echo "<h2>1. PHPMailer Check</h2>";
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<div class='error'>❌ ERROR: PHPMailer is not installed!<br>Please run: composer install</div>";
    echo "</div></body></html>";
    exit;
}
echo "<div class='success'>✅ PHPMailer is installed</div>";

// Check email config
echo "<h2>2. Email Configuration</h2>";
$config = getEmailConfig();
echo "<div class='info'>";
echo "<strong>SMTP Host:</strong> " . htmlspecialchars($config['smtp_host']) . "<br>";
echo "<strong>SMTP Port:</strong> " . htmlspecialchars($config['smtp_port']) . "<br>";
echo "<strong>SMTP Username:</strong> " . htmlspecialchars($config['smtp_username']) . "<br>";
echo "<strong>SMTP Password:</strong> " . (empty($config['smtp_password']) || $config['smtp_password'] === 'your-app-password' ? 
    '<span style="color:red;">❌ NOT CONFIGURED</span>' : 
    '<span style="color:green;">✅ Configured (' . substr($config['smtp_password'], 0, 4) . '****)</span>') . "<br>";
echo "<strong>Encryption:</strong> " . htmlspecialchars($config['smtp_encryption']) . "<br>";
echo "<strong>Debug Mode:</strong> " . ($config['smtp_debug'] > 0 ? '✅ Enabled' : '❌ Disabled') . "<br>";
echo "</div>";

if (empty($config['smtp_password']) || $config['smtp_password'] === 'your-app-password') {
    echo "<div class='error'>❌ Gmail App Password is not configured! Please update email_config.php</div>";
    echo "</div></body></html>";
    exit;
}

// Test sending OTP email
echo "<h2>3. Testing OTP Email Sending</h2>";
echo "<div class='info'>Sending test OTP email to: <strong>" . htmlspecialchars($testEmail) . "</strong></div>";

$testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
echo "<div class='info'>Generated Test OTP: <strong>" . $testOTP . "</strong></div>";

$result = sendOTPEmail($testEmail, $testUsername, $testOTP);

if ($result['success']) {
    echo "<div class='success'>";
    echo "✅ SUCCESS! Email sent successfully!<br>";
    echo "Check your inbox at: <strong>" . htmlspecialchars($testEmail) . "</strong><br>";
    echo "Also check your <strong>spam/junk folder</strong><br>";
    echo "Test OTP Code: <strong>" . $testOTP . "</strong>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "❌ FAILED to send email!<br><br>";
    echo "<strong>Error Message:</strong><br>";
    echo htmlspecialchars($result['message']) . "<br><br>";
    
    if (isset($result['debug']) && !empty($result['debug'])) {
        echo "<strong>SMTP Debug Output:</strong><br>";
        echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
    }
    
    echo "<br><strong>Troubleshooting Steps:</strong><br>";
    echo "1. Verify Gmail App Password is correct<br>";
    echo "2. Check if 2-Step Verification is enabled on Gmail<br>";
    echo "3. Verify firewall/antivirus isn't blocking SMTP (port 587)<br>";
    echo "4. Check PHP error logs: C:\\xampp\\php\\logs\\php_error_log<br>";
    echo "5. Try regenerating the App Password<br>";
    echo "</div>";
}

// Check PHP error log location
echo "<h2>4. PHP Error Log Location</h2>";
$errorLogPath = ini_get('error_log');
if (empty($errorLogPath)) {
    $errorLogPath = 'C:\\xampp\\php\\logs\\php_error_log';
}
echo "<div class='info'>";
echo "Check error logs at: <strong>" . htmlspecialchars($errorLogPath) . "</strong><br>";
echo "Or check: <code>C:\\xampp\\apache\\logs\\error.log</code>";
echo "</div>";

echo "<h2>5. Quick Actions</h2>";
echo "<div class='info'>";
echo "<a href='test_email.php' style='margin-right: 10px;'>🧪 Test Email</a>";
echo "<a href='setup_email.php' style='margin-right: 10px;'>⚙️ Setup Email</a>";
echo "<a href='api/send_otp_code.php' target='_blank'>📧 Test OTP API</a>";
echo "</div>";

echo "</div></body></html>";
?>








