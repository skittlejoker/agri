<?php
/**
 * Verify Email Delivery - Check if Gmail actually accepted the email
 * This will show the full SMTP conversation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Verify Email Delivery</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 1000px; margin: 0 auto; }
h1 { color: #2e8b57; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #004085; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; font-size: 11px; white-space: pre-wrap; font-family: 'Courier New', monospace; }
.code { background: #2e8b57; color: white; padding: 20px; text-align: center; border-radius: 8px; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔍 Verify Email Delivery - Full SMTP Conversation</h1>";

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/phpmailer_helper.php';
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<div class='error'>❌ PHPMailer not loaded</div>";
        exit;
    }
    
    $config = getEmailConfig();
    $testEmail = isset($_POST['email']) ? trim($_POST['email']) : $config['smtp_username'];
    $sendTest = isset($_POST['send_test']);
    
    if ($sendTest) {
        $testUsername = 'TestUser';
        $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        echo "<div class='info'>";
        echo "<strong>Test Configuration:</strong><br>";
        echo "To: " . htmlspecialchars($testEmail) . "<br>";
        echo "OTP Code: <div class='code'>" . $testOTP . "</div>";
        echo "</div>";
        
        // Capture ALL output including SMTP conversation
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
            echo "✅ <strong>PHPMailer reports: Email sent successfully</strong><br><br>";
            
            // Analyze the debug output
            if (isset($result['debug']) && !empty($result['debug'])) {
                echo "<strong>Full SMTP Conversation:</strong><br>";
                echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
                
                // Check for key indicators
                $debug = $result['debug'];
                $indicators = [
                    '250' => 'Gmail accepted the command',
                    '354' => 'Gmail ready to receive email data',
                    '221' => 'Gmail closing connection (normal)',
                    '535' => 'Authentication failed',
                    '534' => 'Authentication failed',
                    '550' => 'Mailbox unavailable or rejected',
                    '553' => 'Mailbox name not allowed',
                ];
                
                echo "<div class='info'>";
                echo "<strong>SMTP Response Analysis:</strong><br>";
                foreach ($indicators as $code => $meaning) {
                    if (strpos($debug, $code) !== false) {
                        if (in_array($code, ['250', '354', '221'])) {
                            echo "✅ Found code <strong>$code</strong>: $meaning<br>";
                        } else {
                            echo "❌ Found code <strong>$code</strong>: $meaning<br>";
                        }
                    }
                }
                echo "</div>";
                
                // Check if we see "QUIT" which means connection closed properly
                if (strpos($debug, 'QUIT') !== false && strpos($debug, '221') !== false) {
                    echo "<div class='success'>";
                    echo "✅ Connection closed properly - Gmail accepted the email<br>";
                    echo "</div>";
                } else {
                    echo "<div class='warning'>";
                    echo "⚠️ Connection may not have closed properly<br>";
                    echo "</div>";
                }
            } else {
                echo "<div class='warning'>";
                echo "⚠️ No debug output captured. Enable debug mode in email_config.php<br>";
                echo "</div>";
            }
            
            echo "<br><strong>Next Steps:</strong><br>";
            echo "1. Check your inbox at: <strong>" . htmlspecialchars($testEmail) . "</strong><br>";
            echo "2. Check spam/junk folder<br>";
            echo "3. Wait 2-3 minutes (Gmail can delay)<br>";
            echo "4. Check Gmail account activity: <a href='https://myaccount.google.com/security' target='_blank'>https://myaccount.google.com/security</a><br>";
            echo "5. Look for 'Blocked sign-in attempt' and click 'Yes, it was me'<br>";
            echo "</div>";
            
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>Email sending failed!</strong><br><br>";
            echo "<strong>Error:</strong> " . htmlspecialchars($result['message']) . "<br><br>";
            
            if (isset($result['debug']) && !empty($result['debug'])) {
                echo "<strong>SMTP Debug Output:</strong><br>";
                echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
            }
            echo "</div>";
        }
        
        if (!empty($output)) {
            echo "<div class='info'>";
            echo "<strong>Additional Output:</strong><br>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
            echo "</div>";
        }
        
    } else {
        // Show form
        echo "<form method='POST'>";
        echo "<div style='margin: 15px 0;'>";
        echo "<label for='email'><strong>Test Email Address:</strong></label><br>";
        echo "<input type='email' id='email' name='email' value='" . htmlspecialchars($testEmail) . "' style='width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;' required>";
        echo "</div>";
        echo "<button type='submit' name='send_test' style='background: #2e8b57; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📧 Send & Verify Email</button>";
        echo "</form>";
        
        echo "<div class='info' style='margin-top: 20px;'>";
        echo "<strong>What this test does:</strong><br>";
        echo "1. Sends an OTP email via Gmail SMTP<br>";
        echo "2. Captures the FULL SMTP conversation<br>";
        echo "3. Analyzes Gmail's responses<br>";
        echo "4. Verifies if Gmail actually accepted the email<br>";
        echo "5. Shows you exactly what happened";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Exception:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
?>






