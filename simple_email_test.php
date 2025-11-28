<?php
/**
 * Simple Email Test - Minimal code to test email sending
 * This bypasses all the complex logic and just sends an email
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Simple Email Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 1000px; margin: 0 auto; }
h1 { color: #2e8b57; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 11px; white-space: pre-wrap; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>📧 Simple Email Test - Direct PHPMailer</h1>";

try {
    require_once __DIR__ . '/config/phpmailer_helper.php';
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<div class='error'>❌ PHPMailer not loaded</div>";
        exit;
    }
    
    $config = getEmailConfig();
    $testEmail = isset($_POST['email']) ? trim($_POST['email']) : $config['smtp_username'];
    $sendTest = isset($_POST['send_test']);
    
    if ($sendTest) {
        $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        echo "<div class='info'>";
        echo "<strong>Test Configuration:</strong><br>";
        echo "To: " . htmlspecialchars($testEmail) . "<br>";
        echo "OTP Code: <strong style='font-size: 24px;'>" . $testOTP . "</strong><br>";
        echo "</div>";
        
        // Create PHPMailer instance directly
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Capture ALL output
        ob_start();
        $debugOutput = '';
        $smtpResponses = [];
        
        // Set up debug callback to capture everything
        $mail->Debugoutput = function ($str, $level) use (&$debugOutput, &$smtpResponses) {
            $message = trim($str);
            $debugOutput .= "[Level $level] $message\n";
            
            // Capture SMTP response codes
            if (preg_match('/^\d{3}\s/', $message)) {
                $smtpResponses[] = $message;
            }
            
            // Show in real-time with color coding
            $color = 'black';
            if (preg_match('/^250/', $message)) $color = 'green';
            elseif (preg_match('/^354/', $message)) $color = 'blue';
            elseif (preg_match('/^221/', $message)) $color = 'green';
            elseif (preg_match('/^5[0-9]{2}/', $message)) $color = 'red';
            elseif (preg_match('/^4[0-9]{2}/', $message)) $color = 'orange';
            
            echo "<span style='color: $color;'>" . htmlspecialchars($message) . "</span><br>";
        };
        
        // Configure - Use port 465 with SSL (more reliable)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = str_replace(' ', '', $config['smtp_password']);
        
        // Use SSL with port 465 (more reliable than TLS with 587)
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port = 465;
        $mail->SMTPDebug = 2; // Maximum debug
        $mail->Timeout = 60;
        
        @$mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
        $mail->addAddress($testEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Test OTP Email';
        $mail->Body = "<h2>Test OTP Code</h2><p>Your code is: <strong style='font-size: 32px;'>" . $testOTP . "</strong></p>";
        $mail->AltBody = "Your test code is: " . $testOTP;
        
        echo "<div class='info'>Sending email... (SMTP conversation will appear below)</div>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 11px;'>";
        
        try {
            $sent = $mail->send();
            $output = ob_get_clean();
            
            echo "</div>";
            
            // Analyze if Gmail actually accepted the email
            $gmailQueued = false;
            $hasDataAccepted = false;
            $hasQuit = false;
            
            if (!empty($debugOutput)) {
                // Look for 250 after DATA (means Gmail queued it)
                $dataPosition = stripos($debugOutput, 'DATA');
                if ($dataPosition !== false) {
                    $afterData = substr($debugOutput, $dataPosition);
                    $hasDataAccepted = preg_match('/250\s+(2\.0\.0\s+)?OK/i', $afterData);
                }
                
                // Look for QUIT and 221
                $hasQuit = preg_match('/QUIT/i', $debugOutput) && preg_match('/221/i', $debugOutput);
                
                // Gmail queued if we see 250 after DATA
                $gmailQueued = $hasDataAccepted;
            }
            
            if ($sent) {
                if ($gmailQueued) {
                    echo "<div class='success'>";
                    echo "✅ <strong>SUCCESS! Gmail confirmed email is queued for delivery!</strong><br>";
                    echo "📧 Email should arrive at: " . htmlspecialchars($testEmail) . "<br>";
                    echo "📁 Check both inbox AND spam/junk folder<br>";
                    echo "🔑 OTP Code: <strong>" . $testOTP . "</strong><br>";
                    echo "</div>";
                } else {
                    echo "<div class='info' style='background: #fff3cd; border-left: 4px solid #ffc107;'>";
                    echo "⚠️ <strong>Email sent but Gmail delivery confirmation unclear</strong><br><br>";
                    echo "PHPMailer says email was sent, but we can't confirm Gmail queued it.<br><br>";
                    echo "<strong>This usually means:</strong><br>";
                    echo "1. Gmail is blocking the email (check security settings)<br>";
                    echo "2. Email is being filtered to spam<br>";
                    echo "3. There's a delay in delivery<br><br>";
                    echo "<strong>What to do:</strong><br>";
                    echo "1. Check Gmail security: <a href='https://myaccount.google.com/security' target='_blank'>https://myaccount.google.com/security</a><br>";
                    echo "2. Look for 'Blocked sign-in attempt' and click 'Yes, it was me'<br>";
                    echo "3. Check spam folder<br>";
                    echo "4. Wait 2-3 minutes<br>";
                    echo "5. Try port 465 with SSL (see below)<br>";
                    echo "</div>";
                }
            } else {
                echo "<div class='error'>";
                echo "❌ Email send() returned false<br>";
                echo "Error: " . htmlspecialchars($mail->ErrorInfo) . "<br>";
                echo "</div>";
            }
            
            // Show SMTP response analysis
            if (!empty($smtpResponses)) {
                echo "<div class='info'>";
                echo "<strong>SMTP Response Codes Found:</strong><br>";
                foreach ($smtpResponses as $response) {
                    $code = substr($response, 0, 3);
                    $icon = '✅';
                    $meaning = 'OK';
                    if ($code === '250') $meaning = 'Command accepted';
                    elseif ($code === '354') $meaning = 'Ready for email data';
                    elseif ($code === '221') $meaning = 'Connection closed';
                    elseif (preg_match('/^5/', $code)) { $icon = '❌'; $meaning = 'Server error'; }
                    elseif (preg_match('/^4/', $code)) { $icon = '⚠️'; $meaning = 'Temporary error'; }
                    echo "$icon <strong>$code</strong>: $meaning - " . htmlspecialchars($response) . "<br>";
                }
                echo "</div>";
            }
            
            if (!empty($debugOutput)) {
                echo "<div class='info'>";
                echo "<strong>Full SMTP Conversation (This shows what Gmail said):</strong><br>";
                echo "<pre style='max-height: 400px; overflow-y: auto;'>" . htmlspecialchars($debugOutput) . "</pre>";
                echo "</div>";
            }
            
            // Show alternative configuration option
            if (!$gmailQueued) {
                echo "<div class='info'>";
                echo "<strong>Try Alternative Configuration (Port 465 with SSL):</strong><br>";
                echo "If port 587 doesn't work, try port 465. Update <code>email_config.php</code>:<br>";
                echo "<pre>'smtp_port' => 465,\n'smtp_encryption' => 'ssl',</pre>";
                echo "</div>";
            }
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $output = ob_get_clean();
            echo "</div>";
            echo "<div class='error'>";
            echo "❌ <strong>Exception:</strong><br>";
            echo htmlspecialchars($e->getMessage()) . "<br><br>";
            if (!empty($debugOutput)) {
                echo "<strong>Debug Output:</strong><br>";
                echo "<pre>" . htmlspecialchars($debugOutput) . "</pre>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<form method='POST'>";
        echo "<div style='margin: 15px 0;'>";
        echo "<label><strong>Test Email Address:</strong></label><br>";
        echo "<input type='email' name='email' value='" . htmlspecialchars($testEmail) . "' style='width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;' required>";
        echo "</div>";
        echo "<button type='submit' name='send_test' style='background: #2e8b57; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📧 Send Test Email</button>";
        echo "</form>";
        
        echo "<div class='info'>";
        echo "<strong>This test:</strong><br>";
        echo "• Uses PHPMailer directly (no wrapper functions)<br>";
        echo "• Shows SMTP conversation in real-time<br>";
        echo "• Captures all debug output<br>";
        echo "• Helps identify the exact issue";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Exception:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div></body></html>";
?>

