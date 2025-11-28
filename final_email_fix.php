<?php
/**
 * FINAL EMAIL FIX - This will make OTP emails work!
 * Uses port 465 with SSL and comprehensive error checking
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Final Email Fix</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 1200px; margin: 0 auto; }
h1 { color: #2e8b57; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #004085; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 11px; white-space: pre-wrap; font-family: monospace; }
.code { background: #2e8b57; color: white; padding: 20px; text-align: center; border-radius: 8px; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔧 FINAL EMAIL FIX - Making OTP Emails Work</h1>";

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
        echo "<strong>Configuration:</strong><br>";
        echo "Using: Port 465 with SSL (most reliable)<br>";
        echo "To: " . htmlspecialchars($testEmail) . "<br>";
        echo "OTP Code: <div class='code'>" . $testOTP . "</div>";
        echo "</div>";
        
        // Create PHPMailer with port 465 SSL
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        $debugOutput = '';
        $smtpResponses = [];
        $criticalSequence = [];
        
        // Capture ALL SMTP conversation
        $mail->Debugoutput = function ($str, $level) use (&$debugOutput, &$smtpResponses, &$criticalSequence) {
            $message = trim($str);
            $debugOutput .= "$message\n";
            
            // Track critical SMTP commands and responses
            if (preg_match('/MAIL FROM/i', $message)) $criticalSequence[] = 'MAIL FROM';
            if (preg_match('/RCPT TO/i', $message)) $criticalSequence[] = 'RCPT TO';
            if (preg_match('/\bDATA\b/i', $message)) $criticalSequence[] = 'DATA';
            if (preg_match('/QUIT/i', $message)) $criticalSequence[] = 'QUIT';
            
            // Capture response codes
            if (preg_match('/^(\d{3})\s+(.+)/', $message, $matches)) {
                $code = $matches[1];
                $text = $matches[2];
                $smtpResponses[] = ['code' => $code, 'text' => $text, 'full' => $message];
                $criticalSequence[] = "Response $code";
            }
            
            // Show in real-time with colors
            $color = 'black';
            if (preg_match('/^250/', $message)) $color = 'green';
            elseif (preg_match('/^354/', $message)) $color = 'blue';
            elseif (preg_match('/^221/', $message)) $color = 'green';
            elseif (preg_match('/^5[0-9]{2}/', $message)) $color = 'red';
            elseif (preg_match('/^4[0-9]{2}/', $message)) $color = 'orange';
            
            echo "<span style='color: $color; font-family: monospace; font-size: 11px;'>" . htmlspecialchars($message) . "</span><br>";
        };
        
        // Configure for port 465 SSL
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = str_replace(' ', '', $config['smtp_password']);
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port = 465;
        $mail->SMTPDebug = 2;
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
        $mail->Subject = 'AgriMarket - OTP Verification Code';
        $mail->Body = "<h2>Your OTP Code</h2><p>Your verification code is: <strong style='font-size: 32px;'>" . $testOTP . "</strong></p>";
        $mail->AltBody = "Your OTP code is: " . $testOTP;
        
        echo "<div class='info'>";
        echo "<strong>SMTP Conversation (Real-time):</strong><br>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; max-height: 400px; overflow-y: auto;'>";
        
        try {
            $sent = $mail->send();
            echo "</div></div>";
            
            // Analyze the SMTP conversation
            $gmailQueued = false;
            $has250AfterData = false;
            $dataIndex = -1;
            
            // Find DATA command position
            for ($i = 0; $i < count($criticalSequence); $i++) {
                if ($criticalSequence[$i] === 'DATA') {
                    $dataIndex = $i;
                    break;
                }
            }
            
            // Check for 250 after DATA
            if ($dataIndex >= 0) {
                for ($i = $dataIndex + 1; $i < count($criticalSequence); $i++) {
                    if (preg_match('/Response 250/', $criticalSequence[$i])) {
                        $has250AfterData = true;
                        $gmailQueued = true;
                        break;
                    }
                }
            }
            
            // Also check debug output for 250 after DATA
            if (!$has250AfterData && preg_match('/DATA.*?250\s+(2\.0\.0\s+)?OK/si', $debugOutput)) {
                $has250AfterData = true;
                $gmailQueued = true;
            }
            
            if ($sent && $gmailQueued) {
                echo "<div class='success'>";
                echo "✅ <strong>SUCCESS! Gmail confirmed email is queued!</strong><br><br>";
                echo "📧 Email should arrive at: <strong>" . htmlspecialchars($testEmail) . "</strong><br>";
                echo "📁 Check inbox AND spam folder<br>";
                echo "⏰ Wait 1-2 minutes if not immediately visible<br>";
                echo "🔑 OTP Code: <div class='code'>" . $testOTP . "</div>";
                echo "</div>";
            } elseif ($sent) {
                echo "<div class='warning'>";
                echo "⚠️ <strong>Email sent but Gmail confirmation unclear</strong><br><br>";
                echo "<strong>CRITICAL: Check Gmail Security Settings NOW!</strong><br><br>";
                echo "1. Go to: <a href='https://myaccount.google.com/security' target='_blank' style='background: #2e8b57; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px 0;'>Open Gmail Security</a><br>";
                echo "2. Scroll to 'Recent security activity'<br>";
                echo "3. Look for 'Blocked sign-in attempt'<br>";
                echo "4. Click 'Yes, it was me' to approve<br>";
                echo "5. Then try sending email again<br><br>";
                echo "🔑 OTP Code (for testing): <div class='code'>" . $testOTP . "</div>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "❌ Email sending failed<br>";
                echo "Error: " . htmlspecialchars($mail->ErrorInfo) . "<br>";
                echo "</div>";
            }
            
            // Show SMTP response analysis
            if (!empty($smtpResponses)) {
                echo "<div class='info'>";
                echo "<strong>SMTP Response Analysis:</strong><br>";
                foreach ($smtpResponses as $response) {
                    $code = $response['code'];
                    $icon = '✅';
                    $meaning = 'OK';
                    if ($code === '250') $meaning = 'Command accepted';
                    elseif ($code === '354') $meaning = 'Ready for email data';
                    elseif ($code === '221') $meaning = 'Connection closed';
                    elseif (preg_match('/^5/', $code)) { $icon = '❌'; $meaning = 'Server error'; }
                    elseif (preg_match('/^4/', $code)) { $icon = '⚠️'; $meaning = 'Temporary error'; }
                    echo "$icon <strong>$code</strong>: $meaning<br>";
                }
                
                // Check for critical sequence
                if ($has250AfterData) {
                    echo "<br>✅ <strong>Critical sequence found: 250 OK after DATA = Gmail queued email!</strong>";
                } else {
                    echo "<br>⚠️ <strong>Critical sequence missing: No 250 OK after DATA found</strong>";
                }
                echo "</div>";
            }
            
            // Show full debug output
            if (!empty($debugOutput)) {
                echo "<div class='info'>";
                echo "<strong>Full SMTP Conversation:</strong><br>";
                echo "<pre>" . htmlspecialchars($debugOutput) . "</pre>";
                echo "</div>";
            }
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            echo "</div></div>";
            echo "<div class='error'>";
            echo "❌ <strong>Exception:</strong><br>";
            echo htmlspecialchars($e->getMessage()) . "<br><br>";
            if (!empty($debugOutput)) {
                echo "<strong>SMTP Conversation:</strong><br>";
                echo "<pre>" . htmlspecialchars($debugOutput) . "</pre>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<div class='info'>";
        echo "<strong>This test uses port 465 with SSL (most reliable configuration)</strong><br>";
        echo "It will show you the complete SMTP conversation and verify if Gmail actually queued the email.";
        echo "</div>";
        
        echo "<form method='POST'>";
        echo "<div style='margin: 15px 0;'>";
        echo "<label><strong>Test Email Address:</strong></label><br>";
        echo "<input type='email' name='email' value='" . htmlspecialchars($testEmail) . "' style='width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;' required>";
        echo "</div>";
        echo "<button type='submit' name='send_test' style='background: #2e8b57; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; font-weight: bold;'>📧 Send Test Email (Port 465 SSL)</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Exception:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div></body></html>";
?>






