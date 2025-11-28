<?php
/**
 * Email Test Script
 * Use this to test if email configuration is working
 * Access via: http://localhost/E-commerce/agriculture-marketplace/test_email.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/phpmailer_helper.php';

// Test email - CHANGE THIS TO YOUR EMAIL
$testEmail = 'trancem260@gmail.com'; // Change to your email for testing

echo "<h2>Email Configuration Test</h2>";
echo "<pre>";

// Check PHPMailer
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "❌ ERROR: PHPMailer is not installed!\n";
    echo "Please run: composer install\n";
    exit;
}
echo "✅ PHPMailer is installed\n\n";

// Check email config
$config = getEmailConfig();
echo "📧 Email Configuration:\n";
echo "   SMTP Host: " . $config['smtp_host'] . "\n";
echo "   SMTP Port: " . $config['smtp_port'] . "\n";
echo "   SMTP Username: " . $config['smtp_username'] . "\n";
echo "   SMTP Password: " . (empty($config['smtp_password']) ? '❌ NOT SET' : (($config['smtp_password'] === 'your-app-password') ? '❌ STILL PLACEHOLDER' : '✅ SET')) . "\n";
echo "   Encryption: " . $config['smtp_encryption'] . "\n";
echo "   Debug Mode: " . $config['smtp_debug'] . "\n\n";

// Check if password is configured
if (empty($config['smtp_password']) || $config['smtp_password'] === 'your-app-password') {
    echo "❌ ERROR: Gmail App Password is not configured!\n";
    echo "\n📝 To fix this:\n";
    echo "1. Go to: https://myaccount.google.com/apppasswords\n";
    echo "2. Generate an App Password for 'Mail'\n";
    echo "3. Copy the 16-character password\n";
    echo "4. Update config/email_config.php line 15 with your App Password\n";
    echo "5. Replace: 'smtp_password' => 'your-app-password',\n";
    echo "   With:    'smtp_password' => 'your-16-char-app-password',\n\n";
    exit;
}

echo "🧪 Testing email send to: {$testEmail}\n";
echo "   (Change \$testEmail in this file to test with your email)\n\n";

// Test sending OTP email
echo "Sending test OTP email...\n";
$testOTP = '123456';
$testUsername = 'TestUser';

$result = sendOTPEmail($testEmail, $testUsername, $testOTP);

if ($result['success']) {
    echo "✅ SUCCESS! Email sent successfully!\n";
    echo "   Check your inbox at: {$testEmail}\n";
    echo "   Also check spam/junk folder\n\n";
    echo "📧 Test OTP Code: {$testOTP}\n";
} else {
    echo "❌ FAILED to send email!\n";
    echo "   Error: " . $result['message'] . "\n\n";
    echo "🔍 Troubleshooting:\n";
    echo "1. Verify Gmail App Password is correct\n";
    echo "2. Check if 2-Step Verification is enabled on Gmail\n";
    echo "3. Check PHP error logs for detailed errors\n";
    echo "4. Try setting smtp_debug to 2 in email_config.php\n";
    echo "5. Verify firewall/antivirus isn't blocking SMTP\n";
}

echo "</pre>";
?>








