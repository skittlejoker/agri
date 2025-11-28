<?php
/**
 * Test Email with Full Verification
 * This will show you EXACTLY what Gmail said
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Email Test with Verification</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 1200px; margin: 0 auto; }
h1 { color: #2e8b57; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #004085; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; font-size: 11px; white-space: pre-wrap; font-family: 'Courier New', monospace; }
.code { background: #2e8b57; color: white; padding: 20px; text-align: center; border-radius: 8px; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; }
.step { background: #f8f9fa; padding: 10px; margin: 5px 0; border-left: 3px solid #6c757d; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>📧 Email Test with Full Gmail Verification</h1>";

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/phpmailer_helper.php';
    
    $config = getEmailConfig();
    $testEmail = isset($_POST['email']) ? trim($_POST['email']) : $config['smtp_username'];
    $sendTest = isset($_POST['send_test']);
    
    if ($sendTest) {
        $testUsername = 'TestUser';
        $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        echo "<div class='info'>";
        echo "<strong>Test Details:</strong><br>";
        echo "To: " . htmlspecialchars($testEmail) . "<br>";
        echo "OTP Code: <div class='code'>" . $testOTP . "</div>";
        echo "</div>";
        
        ob_start();
        $startTime = microtime(true);
        $result = sendOTPEmail($testEmail, $testUsername, $testOTP);
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        $output = ob_get_clean();
        
        echo "<div class='info'>Execution Time: $executionTime ms</div>";
        
        if ($result['success']) {
            // Check delivery confirmation
            $deliveryConfirmed = isset($result['delivery_confirmed']) && $result['delivery_confirmed'];
            $gmailQueued = isset($result['gmail_queued']) && $result['gmail_queued'];
            
            if ($deliveryConfirmed || $gmailQueued) {
                echo "<div class='success'>";
                echo "✅ <strong>SUCCESS! Gmail confirmed email is queued for delivery!</strong><br><br>";
                echo "📧 Email should arrive at: <strong>" . htmlspecialchars($testEmail) . "</strong><br>";
                echo "📁 Check both inbox AND spam/junk folder<br>";
                echo "🔑 OTP Code: <div class='code'>" . $testOTP . "</div>";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "⚠️ <strong>Email sent but Gmail delivery confirmation unclear</strong><br><br>";
                echo "This means:<br>";
                echo "• PHPMailer says email was sent<br>";
                echo "• But we can't confirm Gmail actually queued it<br>";
                echo "• Email might be blocked, filtered, or delayed<br><br>";
                echo "<strong>IMPORTANT: Check the SMTP Debug Output below to see what Gmail actually said!</strong><br><br>";
                echo "<strong>What to do:</strong><br>";
                echo "1. <strong>Scroll down</strong> to see the full SMTP conversation<br>";
                echo "2. Look for error codes (535, 534, 550, etc.) in the debug output<br>";
                echo "3. Check Gmail security: <a href='https://myaccount.google.com/security' target='_blank'>https://myaccount.google.com/security</a><br>";
                echo "4. Look for 'Blocked sign-in attempt' and approve it<br>";
                echo "5. Check spam folder and wait 2-3 minutes<br>";
                echo "6. If you see authentication errors, regenerate your app password<br>";
                echo "7. Try using port 465 with SSL (update email_config.php)<br>";
                echo "</div>";
            }
            
            // Show SMTP conversation analysis
            if (isset($result['debug']) && !empty($result['debug'])) {
                echo "<div class='info'>";
                echo "<strong>SMTP Conversation Analysis:</strong><br>";
                
                $debug = $result['debug'];
                
                // Improved checks
                $has250 = preg_match('/\b250\b/', $debug);
                $has354 = preg_match('/\b354\b/', $debug);
                $has221 = preg_match('/\b221\b/', $debug);
                $hasMailFrom = preg_match('/MAIL FROM/i', $debug);
                $hasRcptTo = preg_match('/RCPT TO/i', $debug);
                $hasData = preg_match('/\bDATA\b/i', $debug);
                $hasQuit = preg_match('/QUIT/i', $debug);
                
                // Check for 250 after DATA
                $hasDataAccepted = false;
                if ($hasData) {
                    $dataPosition = stripos($debug, 'DATA');
                    if ($dataPosition !== false) {
                        $afterData = substr($debug, $dataPosition);
                        $hasDataAccepted = preg_match('/250\s+(2\.0\.0\s+)?OK/i', $afterData) || 
                                          preg_match('/250.*(queued|accepted|Ok)/i', $afterData);
                    }
                }
                
                $checks = [
                    '250 OK responses' => $has250,
                    'MAIL FROM command' => $hasMailFrom,
                    'RCPT TO command' => $hasRcptTo,
                    'DATA command' => $hasData,
                    '354 Ready for data' => $has354,
                    '250 after DATA (queued)' => $hasDataAccepted,
                    'QUIT command' => $hasQuit,
                    '221 Connection closed' => $has221,
                ];
                
                foreach ($checks as $checkName => $found) {
                    $icon = $found ? '✅' : '❌';
                    echo "<div class='step'>$icon <strong>$checkName:</strong> " . ($found ? 'Found' : 'NOT Found') . "</div>";
                }
                
                // Determine status
                if ($hasDataAccepted) {
                    echo "<div class='success'>✅ <strong>Gmail confirmed email is queued for delivery!</strong></div>";
                } elseif ($hasMailFrom && $hasRcptTo && $hasData && $has354 && $has221) {
                    echo "<div class='info'>ℹ️ <strong>Full SMTP sequence completed - Email likely accepted by Gmail</strong></div>";
                } else {
                    echo "<div class='warning'>⚠️ <strong>SMTP sequence incomplete - Delivery status unclear</strong></div>";
                }
                
                echo "</div>";
                
                echo "<div class='info'>";
                echo "<strong>Full SMTP Debug Output (This shows exactly what Gmail said):</strong><br>";
                echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "⚠️ <strong>No debug output captured</strong><br><br>";
                if (isset($result['debug_length'])) {
                    echo "Debug output length: " . $result['debug_length'] . " bytes<br>";
                }
                if (isset($result['smtp_debug_level'])) {
                    echo "SMTP Debug Level: " . $result['smtp_debug_level'] . "<br>";
                }
                echo "This might mean:<br>";
                echo "• SMTPDebug is not set correctly<br>";
                echo "• Debug output callback is not working<br>";
                echo "• Check PHP error logs for SMTP messages<br>";
                echo "</div>";
            }
            
            // Always show debug output if it exists, even if empty
            if (isset($result['debug'])) {
                echo "<div class='info'>";
                echo "<strong>SMTP Debug Output (Raw):</strong><br>";
                if (empty($result['debug'])) {
                    echo "<pre>Debug output is empty. This indicates the SMTP conversation was not captured.</pre>";
                } else {
                    echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
                }
                echo "</div>";
            }
            
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>Email sending failed!</strong><br><br>";
            echo "<strong>Error:</strong> " . htmlspecialchars($result['message']) . "<br><br>";
            
            if (isset($result['analysis']) && !empty($result['analysis'])) {
                echo "<strong>Analysis:</strong><br>";
                foreach ($result['analysis'] as $analysis) {
                    echo "• $analysis<br>";
                }
                echo "<br>";
            }
            
            if (isset($result['debug']) && !empty($result['debug'])) {
                echo "<strong>SMTP Debug Output:</strong><br>";
                echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<form method='POST'>";
        echo "<div style='margin: 15px 0;'>";
        echo "<label><strong>Test Email Address:</strong></label><br>";
        echo "<input type='email' name='email' value='" . htmlspecialchars($testEmail) . "' style='width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;' required>";
        echo "</div>";
        echo "<button type='submit' name='send_test' style='background: #2e8b57; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📧 Send & Verify Email</button>";
        echo "</form>";
        
        echo "<div class='info'>";
        echo "<strong>What this test does:</strong><br>";
        echo "1. Sends email via Gmail SMTP<br>";
        echo "2. Analyzes the FULL SMTP conversation<br>";
        echo "3. Verifies Gmail actually queued the email (250 after DATA)<br>";
        echo "4. Shows you exactly what Gmail said<br>";
        echo "5. Confirms if email is actually being delivered";
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

