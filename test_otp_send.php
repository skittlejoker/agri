<?php
/**
 * Direct OTP Email Test
 * This will test sending an OTP email and show you the exact error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>OTP Email Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
h1 { color: #333; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔍 OTP Email Sending Test</h1>";

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/phpmailer_helper.php';
    
    // Check PHPMailer
    echo "<h2>Step 1: PHPMailer Check</h2>";
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<div class='error'>❌ PHPMailer is NOT loaded!</div>";
        echo "<div class='info'>Trying to load manually...</div>";
        
        // Try to load manually
        $paths = [
            __DIR__ . '/vendor/phpmailer/phpmailer/src/',
            __DIR__ . '/PHPMailer/src/'
        ];
        
        $loaded = false;
        foreach ($paths as $path) {
            if (file_exists($path . 'PHPMailer.php')) {
                require_once $path . 'Exception.php';
                require_once $path . 'PHPMailer.php';
                require_once $path . 'SMTP.php';
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    echo "<div class='success'>✅ PHPMailer loaded from: " . htmlspecialchars($path) . "</div>";
                    $loaded = true;
                    break;
                }
            }
        }
        
        if (!$loaded) {
            echo "<div class='error'>❌ Could not load PHPMailer. Please install it via Composer or manually.</div>";
            echo "</div></body></html>";
            exit;
        }
    } else {
        echo "<div class='success'>✅ PHPMailer is loaded</div>";
    }
    
    // Check config
    echo "<h2>Step 2: Email Configuration</h2>";
    $config = getEmailConfig();
    echo "<div class='info'>";
    echo "Host: " . htmlspecialchars($config['smtp_host']) . "<br>";
    echo "Port: " . htmlspecialchars($config['smtp_port']) . "<br>";
    echo "Username: " . htmlspecialchars($config['smtp_username']) . "<br>";
    echo "Password: " . (empty($config['smtp_password']) ? '<span style="color:red;">NOT SET</span>' : '<span style="color:green;">SET (' . substr($config['smtp_password'], 0, 4) . '****)</span>') . "<br>";
    echo "Debug: " . ($config['smtp_debug'] > 0 ? 'ON' : 'OFF') . "<br>";
    echo "</div>";
    
    if (empty($config['smtp_password']) || $config['smtp_password'] === 'your-app-password') {
        echo "<div class='error'>❌ Gmail App Password is not configured!</div>";
        echo "</div></body></html>";
        exit;
    }
    
    // Test sending
    echo "<h2>Step 3: Sending Test OTP Email</h2>";
    $testEmail = 'trancem260@gmail.com';
    $testUsername = 'TestUser';
    $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    echo "<div class='info'>";
    echo "To: " . htmlspecialchars($testEmail) . "<br>";
    echo "OTP Code: <strong>" . $testOTP . "</strong><br>";
    echo "</div>";
    
    echo "<div class='info'>Attempting to send email...</div>";
    
    // Capture output
    ob_start();
    $result = sendOTPEmail($testEmail, $testUsername, $testOTP);
    $output = ob_get_clean();
    
    if ($result['success']) {
        echo "<div class='success'>";
        echo "✅ SUCCESS! Email sent successfully!<br><br>";
        echo "Please check your inbox at: <strong>" . htmlspecialchars($testEmail) . "</strong><br>";
        echo "Also check your <strong>spam/junk folder</strong><br><br>";
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
        
        if (!empty($output)) {
            echo "<strong>Output Buffer:</strong><br>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
        
        echo "<br><strong>Common Fixes:</strong><br>";
        echo "1. Verify Gmail App Password is correct (no spaces)<br>";
        echo "2. Check if 2-Step Verification is enabled<br>";
        echo "3. Try regenerating the App Password<br>";
        echo "4. Check firewall/antivirus settings<br>";
        echo "5. Check PHP error logs<br>";
        echo "</div>";
    }
    
    // Show PHP errors if any
    $errors = error_get_last();
    if ($errors && $errors['type'] === E_ERROR) {
        echo "<h2>PHP Errors</h2>";
        echo "<div class='error'>";
        echo "<pre>" . htmlspecialchars(print_r($errors, true)) . "</pre>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ Exception: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
?>








