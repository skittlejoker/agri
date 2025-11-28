<?php
/**
 * Test Email with Alternative Port (465 SSL)
 * Use this if port 587 doesn't work
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Email - Port 465</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
h1 { color: #2e8b57; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>📧 Test Email with Port 465 (SSL)</h1>";

try {
    require_once __DIR__ . '/config/phpmailer_helper.php';
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<div class='error'>❌ PHPMailer not loaded</div>";
        exit;
    }
    
    $config = getEmailConfig();
    $testEmail = $config['smtp_username']; // Send to yourself
    $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    echo "<div class='info'>";
    echo "<strong>Testing with:</strong><br>";
    echo "Port: 465<br>";
    echo "Encryption: SSL<br>";
    echo "To: " . htmlspecialchars($testEmail) . "<br>";
    echo "OTP: <strong style='font-size: 24px;'>" . $testOTP . "</strong><br>";
    echo "</div>";
    
    // Create mailer with alternative settings
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    $debugOutput = '';
    $mail->Debugoutput = function ($str, $level) use (&$debugOutput) {
        $debugOutput .= date('H:i:s') . " [Level $level] " . trim($str) . "\n";
    };
    
    // Use port 465 with SSL
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = str_replace(' ', '', $config['smtp_password']);
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port = 465;
    $mail->SMTPDebug = 2;
    $mail->Timeout = 60;
    
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
    $mail->addAddress($testEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Test OTP Email - Port 465';
    $mail->Body = "<h2>Test OTP Code</h2><p>Your test code is: <strong style='font-size: 32px;'>" . $testOTP . "</strong></p>";
    $mail->AltBody = "Your test code is: " . $testOTP;
    
    echo "<div class='info'>Sending email...</div>";
    
    ob_start();
    $mail->send();
    $output = ob_get_clean();
    
    echo "<div class='success'>";
    echo "✅ Email sent successfully!<br>";
    echo "Check your inbox at: " . htmlspecialchars($testEmail) . "<br>";
    echo "OTP Code: <strong>" . $testOTP . "</strong>";
    echo "</div>";
    
    if (!empty($debugOutput)) {
        echo "<div class='info'>";
        echo "<strong>SMTP Debug Output:</strong><br>";
        echo "<pre>" . htmlspecialchars($debugOutput) . "</pre>";
        echo "</div>";
    }
    
    echo "<div class='info'>";
    echo "<strong>If this worked, update your email_config.php:</strong><br>";
    echo "<pre>'smtp_port' => 465,\n'smtp_encryption' => 'ssl',</pre>";
    echo "</div>";
    
} catch (\PHPMailer\PHPMailer\Exception $e) {
    echo "<div class='error'>";
    echo "❌ Failed: " . htmlspecialchars($e->getMessage()) . "<br>";
    if (!empty($debugOutput)) {
        echo "<strong>Debug Output:</strong><br>";
        echo "<pre>" . htmlspecialchars($debugOutput) . "</pre>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div></body></html>";
?>






