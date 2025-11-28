<?php
/**
 * Direct Email Test - Bypasses API and tests email sending directly
 * This will show you EXACTLY what's happening
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Direct Email Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 1000px; margin: 0 auto; }
h1 { color: #2e8b57; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #004085; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; font-size: 12px; white-space: pre-wrap; }
.code { background: #2e8b57; color: white; padding: 20px; text-align: center; border-radius: 8px; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; }
.form-group { margin: 15px 0; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
input[type='email'], input[type='text'] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
button { background: #2e8b57; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
button:hover { background: #228b22; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>📧 Direct Email Test - Find the Problem</h1>";

// Get test email from form or use default
$testEmail = isset($_POST['email']) ? trim($_POST['email']) : 'trancem260@gmail.com';
$sendTest = isset($_POST['send_test']);

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/phpmailer_helper.php';
    
    echo "<div class='info'>";
    echo "<strong>Testing email to:</strong> " . htmlspecialchars($testEmail) . "<br>";
    echo "</div>";
    
    if ($sendTest) {
        $testUsername = 'TestUser';
        $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        echo "<div class='info'>";
        echo "<strong>Generated OTP Code:</strong> <div class='code'>" . $testOTP . "</div>";
        echo "Attempting to send email...<br>";
        echo "</div>";
        
        // Capture all output
        ob_start();
        $startTime = microtime(true);
        
        $result = sendOTPEmail($testEmail, $testUsername, $testOTP);
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        $output = ob_get_clean();
        
        echo "<div class='info'>";
        echo "<strong>Execution Time:</strong> " . $executionTime . " ms<br>";
        echo "</div>";
        
        if ($result['success']) {
            echo "<div class='success'>";
            echo "✅ <strong>SUCCESS! Email sent successfully!</strong><br><br>";
            echo "📧 Email should arrive at: <strong>" . htmlspecialchars($testEmail) . "</strong><br>";
            echo "📁 <strong>Check both inbox AND spam/junk folder</strong><br><br>";
            echo "🔑 The OTP code in the email should be: <div class='code'>" . $testOTP . "</div>";
            echo "<br><strong>If you still don't receive the email:</strong><br>";
            echo "1. Wait 2-3 minutes (Gmail can delay)<br>";
            echo "2. Check Gmail account activity: <a href='https://myaccount.google.com/security' target='_blank'>https://myaccount.google.com/security</a><br>";
            echo "3. Look for blocked login attempts<br>";
            echo "4. Try a different recipient email address";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>FAILED to send email!</strong><br><br>";
            echo "<strong>Error Message:</strong><br>";
            echo htmlspecialchars($result['message']) . "<br><br>";
            
            if (isset($result['debug']) && !empty($result['debug'])) {
                echo "<strong>SMTP Debug Output (This shows the exact conversation with Gmail):</strong><br>";
                echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
            }
            
            if (!empty($output)) {
                echo "<strong>Additional Output:</strong><br>";
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            }
            
            echo "<br><strong>Common Solutions:</strong><br>";
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>1. Authentication Failed (535/534 errors):</strong><br>";
            echo "   - Verify app password is correct (no spaces)<br>";
            echo "   - Check 2-Step Verification is enabled<br>";
            echo "   - Generate a NEW app password<br><br>";
            
            echo "<strong>2. Connection Failed (SMTP connect failed):</strong><br>";
            echo "   - Check Windows Firewall allows port 587<br>";
            echo "   - Try port 465 with SSL (see alternative config below)<br>";
            echo "   - Check antivirus isn't blocking<br><br>";
            
            echo "<strong>3. Timeout:</strong><br>";
            echo "   - Check internet connection<br>";
            echo "   - Try again in a few minutes<br>";
            echo "</div>";
            
            // Show alternative configuration
            echo "<div class='info'>";
            echo "<strong>Try Alternative Configuration:</strong><br>";
            echo "If port 587 (TLS) doesn't work, try port 465 (SSL). Update email_config.php:<br>";
            echo "<pre>'smtp_port' => 465,\n'smtp_encryption' => 'ssl',</pre>";
            echo "</div>";
            
            echo "</div>";
        }
        
        // Show PHP error log location
        $errorLog = ini_get('error_log');
        if ($errorLog) {
            echo "<div class='info'>";
            echo "<strong>Check PHP Error Log:</strong> " . htmlspecialchars($errorLog) . "<br>";
            echo "Or check: C:\\xampp\\php\\logs\\php_error_log or C:\\xampp\\apache\\logs\\error.log";
            echo "</div>";
        }
    } else {
        // Show form
        echo "<form method='POST'>";
        echo "<div class='form-group'>";
        echo "<label for='email'>Test Email Address:</label>";
        echo "<input type='email' id='email' name='email' value='" . htmlspecialchars($testEmail) . "' required>";
        echo "</div>";
        echo "<button type='submit' name='send_test'>📧 Send Test OTP Email</button>";
        echo "</form>";
        
        echo "<div class='info' style='margin-top: 20px;'>";
        echo "<strong>What this test does:</strong><br>";
        echo "1. Loads your email configuration<br>";
        echo "2. Generates a test OTP code<br>";
        echo "3. Attempts to send email via Gmail SMTP<br>";
        echo "4. Shows detailed error messages if it fails<br>";
        echo "5. Displays SMTP debug output (the actual conversation with Gmail)";
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






