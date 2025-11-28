<?php

/**
 * Direct Email Test with Guaranteed Debug Capture
 * This bypasses all wrappers and captures SMTP output directly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Direct Email Test - Guaranteed Capture</title>";
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
echo "<h1>📧 Direct Email Test - Guaranteed Debug Capture</h1>";

try {
    // Load PHPMailer directly
    $vendorAutoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($vendorAutoload)) {
        require_once $vendorAutoload;
    } else {
        $phpmailerPath = __DIR__ . '/PHPMailer/src/';
        if (file_exists($phpmailerPath . 'PHPMailer.php')) {
            require_once $phpmailerPath . 'Exception.php';
            require_once $phpmailerPath . 'PHPMailer.php';
            require_once $phpmailerPath . 'SMTP.php';
        } else {
            throw new Exception('PHPMailer not found');
        }
    }

    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        throw new Exception('PHPMailer class not available');
    }

    // Load config
    $config = require __DIR__ . '/config/email_config.php';
    $testEmail = isset($_POST['email']) ? trim($_POST['email']) : $config['smtp_username'];
    $sendTest = isset($_POST['send_test']);

    if ($sendTest) {
        $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        echo "<div class='info'>";
        echo "<strong>Test Configuration:</strong><br>";
        echo "Port: 465 (SSL)<br>";
        echo "To: " . htmlspecialchars($testEmail) . "<br>";
        echo "OTP Code: <div class='code'>" . $testOTP . "</div>";
        echo "</div>";

        // Create mailer
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Capture debug output using multiple methods
        $debugOutput = '';
        $smtpResponses = [];

        // Method 1: Output buffering
        ob_start();

        // Method 2: Debug callback
        $mail->Debugoutput = function ($str, $level) use (&$debugOutput, &$smtpResponses) {
            $message = trim($str);
            if (empty($message)) return;

            $debugOutput .= "[$level] $message\n";

            // Capture response codes
            if (preg_match('/^(\d{3})\s+(.+)/', $message, $matches)) {
                $smtpResponses[] = ['code' => $matches[1], 'text' => $matches[2], 'full' => $message];
            }
        };

        // Configure
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = str_replace(' ', '', $config['smtp_password']);
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
        $mail->Subject = 'AgriMarket - OTP Test';
        $mail->Body = "<h2>OTP Code</h2><p>Your code: <strong style='font-size: 32px;'>" . $testOTP . "</strong></p>";
        $mail->AltBody = "Your OTP code is: " . $testOTP;

        echo "<div class='info'>";
        echo "<strong>SMTP Conversation (Real-time):</strong><br>";
        echo "<div style='background: #000; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 11px; max-height: 400px; overflow-y: auto;'>";

        try {
            $sent = $mail->send();
            $buffered = ob_get_clean();
            echo "</div></div>";

            // Process buffered output
            if (!empty($buffered)) {
                $debugOutput .= "\n--- Buffered Output ---\n" . $buffered;
            }

            // Analyze
            $gmailQueued = false;
            if (preg_match('/DATA.*?250\s+(2\.0\.0\s+)?OK/si', $debugOutput)) {
                $gmailQueued = true;
            }

            if ($sent && $gmailQueued) {
                echo "<div class='success'>";
                echo "✅ <strong>SUCCESS! Gmail queued email for delivery!</strong><br>";
                echo "📧 Check inbox at: " . htmlspecialchars($testEmail) . "<br>";
                echo "🔑 OTP: <div class='code'>" . $testOTP . "</div>";
                echo "</div>";
            } elseif ($sent) {
                echo "<div class='warning'>";
                echo "⚠️ Email sent but Gmail confirmation unclear<br>";
                echo "Check Gmail security: <a href='https://myaccount.google.com/security' target='_blank'>Approve blocked sign-in</a><br>";
                echo "🔑 OTP: <div class='code'>" . $testOTP . "</div>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "❌ Send failed: " . htmlspecialchars($mail->ErrorInfo);
                echo "</div>";
            }

            // Show debug output
            if (!empty($debugOutput)) {
                echo "<div class='info'>";
                echo "<strong>Full SMTP Debug Output:</strong><br>";
                echo "<pre>" . htmlspecialchars($debugOutput) . "</pre>";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "⚠️ No debug output captured. Check PHP error logs.";
                echo "</div>";
            }

            // Show SMTP responses
            if (!empty($smtpResponses)) {
                echo "<div class='info'>";
                echo "<strong>SMTP Response Codes:</strong><br>";
                foreach ($smtpResponses as $response) {
                    $icon = ($response['code'] === '250' || $response['code'] === '354' || $response['code'] === '221') ? '✅' : '❌';
                    echo "$icon <strong>{$response['code']}</strong>: {$response['text']}<br>";
                }
                echo "</div>";
            }
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $buffered = ob_get_clean();
            echo "</div></div>";
            echo "<div class='error'>";
            echo "❌ <strong>Exception:</strong><br>";
            echo htmlspecialchars($e->getMessage()) . "<br><br>";
            if (!empty($debugOutput)) {
                echo "<strong>Debug Output:</strong><br>";
                echo "<pre>" . htmlspecialchars($debugOutput) . "</pre>";
            }
            if (!empty($buffered)) {
                echo "<strong>Buffered Output:</strong><br>";
                echo "<pre>" . htmlspecialchars($buffered) . "</pre>";
            }
            echo "</div>";
        }
    } else {
        echo "<form method='POST'>";
        echo "<div style='margin: 15px 0;'>";
        echo "<label><strong>Test Email:</strong></label><br>";
        echo "<input type='email' name='email' value='" . htmlspecialchars($testEmail) . "' style='width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;' required>";
        echo "</div>";
        echo "<button type='submit' name='send_test' style='background: #2e8b57; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📧 Send Test Email</button>";
        echo "</form>";
    }
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div></body></html>";





