<?php
/**
 * Complete OTP Email System Test
 * This will test the entire OTP sending system and show detailed diagnostics
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Complete OTP Email Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 1000px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #2e8b57; border-bottom: 3px solid #2e8b57; padding-bottom: 10px; }
h2 { color: #333; margin-top: 30px; border-left: 4px solid #2e8b57; padding-left: 10px; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #004085; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; font-size: 12px; }
.code { background: #2e8b57; color: white; padding: 20px; text-align: center; border-radius: 8px; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; }
.step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #6c757d; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔍 Complete OTP Email System Diagnostic Test</h1>";

$allChecksPassed = true;

try {
    // Step 1: Check PHP Version
    echo "<h2>Step 1: PHP Environment</h2>";
    echo "<div class='info'>";
    echo "PHP Version: " . PHP_VERSION . "<br>";
    echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
    echo "OS: " . PHP_OS . "<br>";
    echo "</div>";
    
    // Step 2: Check Database Connection
    echo "<h2>Step 2: Database Connection</h2>";
    try {
        require_once __DIR__ . '/config/database.php';
        echo "<div class='success'>✅ Database connection successful</div>";
        
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ Users table exists</div>";
            
            // Check if password_reset_otp table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_otp'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✅ password_reset_otp table exists</div>";
            } else {
                echo "<div class='warning'>⚠️ password_reset_otp table does not exist (will be created automatically)</div>";
            }
        } else {
            echo "<div class='error'>❌ Users table does not exist</div>";
            $allChecksPassed = false;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $allChecksPassed = false;
    }
    
    // Step 3: Check PHPMailer
    echo "<h2>Step 3: PHPMailer Installation</h2>";
    $phpmailerLoaded = false;
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<div class='success'>✅ PHPMailer is already loaded</div>";
        $phpmailerLoaded = true;
    } else {
        echo "<div class='warning'>⚠️ PHPMailer not loaded, attempting to load...</div>";
        
        // Try Composer autoload
        $vendorAutoload = __DIR__ . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                echo "<div class='success'>✅ PHPMailer loaded via Composer</div>";
                $phpmailerLoaded = true;
            }
        }
        
        // Try manual loading
        if (!$phpmailerLoaded) {
            $paths = [
                __DIR__ . '/vendor/phpmailer/phpmailer/src/',
                __DIR__ . '/PHPMailer/src/'
            ];
            
            foreach ($paths as $path) {
                if (file_exists($path . 'PHPMailer.php')) {
                    require_once $path . 'Exception.php';
                    require_once $path . 'PHPMailer.php';
                    require_once $path . 'SMTP.php';
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        echo "<div class='success'>✅ PHPMailer loaded from: " . htmlspecialchars($path) . "</div>";
                        $phpmailerLoaded = true;
                        break;
                    }
                }
            }
        }
        
        if (!$phpmailerLoaded) {
            echo "<div class='error'>❌ Could not load PHPMailer. Please install it:</div>";
            echo "<div class='step'>";
            echo "<strong>Option 1 (Recommended):</strong> Run: <code>composer install</code> or <code>composer require phpmailer/phpmailer</code><br>";
            echo "<strong>Option 2:</strong> Download PHPMailer and place it in the PHPMailer/ directory";
            echo "</div>";
            $allChecksPassed = false;
        }
    }
    
    // Step 4: Check Email Configuration
    echo "<h2>Step 4: Email Configuration</h2>";
    require_once __DIR__ . '/config/phpmailer_helper.php';
    $config = getEmailConfig();
    
    echo "<div class='info'>";
    echo "<strong>Current Configuration:</strong><br>";
    echo "SMTP Host: " . htmlspecialchars($config['smtp_host']) . "<br>";
    echo "SMTP Port: " . htmlspecialchars($config['smtp_port']) . "<br>";
    echo "SMTP Username: " . htmlspecialchars($config['smtp_username']) . "<br>";
    
    $passwordLength = strlen($config['smtp_password']);
    $passwordPreview = substr($config['smtp_password'], 0, 4) . str_repeat('*', max(0, $passwordLength - 4));
    $hasSpaces = strpos($config['smtp_password'], ' ') !== false;
    
    echo "SMTP Password: " . htmlspecialchars($passwordPreview) . " (" . $passwordLength . " characters)";
    if ($hasSpaces) {
        echo " <span style='color:red;'><strong>⚠️ WARNING: Password contains spaces! Gmail app passwords should NOT have spaces.</strong></span>";
    }
    echo "<br>";
    echo "Encryption: " . htmlspecialchars($config['smtp_encryption']) . "<br>";
    echo "Debug Mode: " . ($config['smtp_debug'] > 0 ? 'ON (Level ' . $config['smtp_debug'] . ')' : 'OFF') . "<br>";
    echo "</div>";
    
    if (empty($config['smtp_password']) || $config['smtp_password'] === 'your-app-password') {
        echo "<div class='error'>❌ Gmail App Password is not configured!</div>";
        echo "<div class='step'>";
        echo "<strong>How to fix:</strong><br>";
        echo "1. Go to: <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a><br>";
        echo "2. Generate a new app password for 'Mail'<br>";
        echo "3. Copy the 16-character password (it will look like: xxxx xxxx xxxx xxxx)<br>";
        echo "4. Remove ALL spaces when pasting into email_config.php<br>";
        echo "5. Update the 'smtp_password' value in config/email_config.php";
        echo "</div>";
        $allChecksPassed = false;
    } else {
        echo "<div class='success'>✅ Email configuration appears to be set</div>";
    }
    
    // Step 5: Test Email Sending
    if ($allChecksPassed && $phpmailerLoaded) {
        echo "<h2>Step 5: Test Email Sending</h2>";
        
        // Get test email from config or use default
        $testEmail = $config['smtp_username']; // Send to the same email for testing
        $testUsername = 'TestUser';
        $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        echo "<div class='info'>";
        echo "<strong>Test Details:</strong><br>";
        echo "Sending to: <strong>" . htmlspecialchars($testEmail) . "</strong><br>";
        echo "Test OTP Code: <div class='code'>" . $testOTP . "</div>";
        echo "This code will be sent via email. Check your inbox after clicking 'Send Test Email' below.";
        echo "</div>";
        
        // Check if form was submitted
        if (isset($_POST['send_test'])) {
            echo "<div class='info'>Attempting to send email...</div>";
            
            // Capture any output
            ob_start();
            $result = sendOTPEmail($testEmail, $testUsername, $testOTP);
            $output = ob_get_clean();
            
            if ($result['success']) {
                echo "<div class='success'>";
                echo "✅ <strong>SUCCESS! Email sent successfully!</strong><br><br>";
                echo "📧 Please check your inbox at: <strong>" . htmlspecialchars($testEmail) . "</strong><br>";
                echo "📁 Also check your <strong>spam/junk folder</strong><br><br>";
                echo "🔑 The OTP code in the email should be: <div class='code'>" . $testOTP . "</div>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "❌ <strong>FAILED to send email!</strong><br><br>";
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
                
                echo "<br><strong>Common Solutions:</strong><br>";
                echo "<div class='step'>";
                echo "1. <strong>Verify App Password:</strong> Make sure you copied the app password correctly (no spaces)<br>";
                echo "2. <strong>Check 2-Step Verification:</strong> Ensure 2-Step Verification is enabled on your Gmail account<br>";
                echo "3. <strong>Regenerate App Password:</strong> Try deleting and creating a new app password<br>";
                echo "4. <strong>Check Firewall:</strong> Ensure port 587 (SMTP) is not blocked<br>";
                echo "5. <strong>Check PHP Error Logs:</strong> Look in your PHP error log for more details<br>";
                echo "6. <strong>Test Connection:</strong> Try using a different email client to verify SMTP works";
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<form method='POST' style='margin-top: 20px;'>";
            echo "<button type='submit' name='send_test' style='background: #2e8b57; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📧 Send Test OTP Email</button>";
            echo "</form>";
        }
    } else {
        echo "<div class='warning'>⚠️ Cannot test email sending. Please fix the issues above first.</div>";
    }
    
    // Step 6: Summary
    echo "<h2>Step 6: Summary</h2>";
    if ($allChecksPassed && $phpmailerLoaded) {
        echo "<div class='success'>";
        echo "✅ <strong>All basic checks passed!</strong><br>";
        echo "Your system appears to be configured correctly. Try sending a test email above.";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "❌ <strong>Some checks failed.</strong><br>";
        echo "Please fix the issues above before testing email sending.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Exception occurred:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
?>






