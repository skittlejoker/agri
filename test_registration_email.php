<?php
/**
 * Test Registration Email - Direct Test
 * This tests the exact same email sending used in registration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Registration Email</title>";
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
echo "<h1>📧 Test Registration Email</h1>";

try {
    // Load PHPMailer helper (same as registration uses)
    require_once __DIR__ . '/config/phpmailer_helper.php';
    
    if (!function_exists('sendVerificationEmail')) {
        throw new Exception('sendVerificationEmail function not found');
    }
    
    // Get test email
    $config = require __DIR__ . '/config/email_config.php';
    $testEmail = isset($_POST['email']) ? trim($_POST['email']) : $config['smtp_username'];
    $sendTest = isset($_POST['send_test']);
    
    if ($sendTest) {
        $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $testUsername = 'TestUser';
        
        echo "<div class='info'>";
        echo "<strong>Test Configuration:</strong><br>";
        echo "Using: sendVerificationEmail() function (same as registration)<br>";
        echo "Port: " . $config['smtp_port'] . " (" . strtoupper($config['smtp_encryption']) . ")<br>";
        echo "To: " . htmlspecialchars($testEmail) . "<br>";
        echo "OTP Code: <div class='code'>" . $testOTP . "</div>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<strong>Sending email using registration function...</strong><br>";
        echo "</div>";
        
        // Use the exact same function as registration
        $result = sendVerificationEmail($testEmail, $testOTP, $testUsername);
        
        // Display results
        if ($result['success']) {
            $gmailQueued = isset($result['gmail_queued']) && $result['gmail_queued'];
            $deliveryConfirmed = isset($result['delivery_confirmed']) && $result['delivery_confirmed'];
            
            if ($gmailQueued || $deliveryConfirmed) {
                echo "<div class='success'>";
                echo "✅ <strong>SUCCESS! Email sent and queued by Gmail!</strong><br>";
                echo "📧 Check inbox at: " . htmlspecialchars($testEmail) . "<br>";
                echo "🔑 OTP Code: <div class='code'>" . $testOTP . "</div>";
                echo "<br><strong>If email not received:</strong><br>";
                echo "1. Check spam folder<br>";
                echo "2. Check Gmail security: <a href='https://myaccount.google.com/security' target='_blank'>Approve blocked sign-in</a><br>";
                echo "3. Wait 1-2 minutes (Gmail may delay delivery)";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "⚠️ <strong>Email sent but Gmail confirmation unclear</strong><br>";
                echo "📧 Check inbox at: " . htmlspecialchars($testEmail) . "<br>";
                echo "🔑 OTP Code: <div class='code'>" . $testOTP . "</div>";
                echo "<br><strong>Action Required:</strong><br>";
                echo "1. Check Gmail security: <a href='https://myaccount.google.com/security' target='_blank'>Approve blocked sign-in</a><br>";
                echo "2. Check spam folder<br>";
                echo "3. Check PHP error logs for SMTP details";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>Email sending failed!</strong><br>";
            echo htmlspecialchars($result['message']) . "<br><br>";
            echo "🔑 OTP Code (for manual testing): <div class='code'>" . $testOTP . "</div>";
            echo "</div>";
        }
        
        // Show debug output if available
        if (isset($result['debug']) && !empty($result['debug'])) {
            echo "<div class='info'>";
            echo "<strong>SMTP Debug Output:</strong><br>";
            echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
            echo "</div>";
        }
        
        // Show SMTP responses if available
        if (isset($result['smtp_responses']) && !empty($result['smtp_responses'])) {
            echo "<div class='info'>";
            echo "<strong>SMTP Response Codes:</strong><br>";
            foreach ($result['smtp_responses'] as $response) {
                $code = is_array($response) ? $response['code'] : (preg_match('/^(\d{3})/', $response, $m) ? $m[1] : '???');
                $icon = (in_array($code, ['250', '354', '221'])) ? '✅' : '❌';
                echo "$icon <strong>$code</strong>: " . htmlspecialchars(is_array($response) ? $response['text'] : $response) . "<br>";
            }
            echo "</div>";
        }
        
        // Show analysis if available
        if (isset($result['analysis']) && !empty($result['analysis'])) {
            echo "<div class='warning'>";
            echo "<strong>Analysis:</strong><br>";
            foreach ($result['analysis'] as $item) {
                echo "• " . htmlspecialchars($item) . "<br>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<form method='POST'>";
        echo "<div style='margin: 15px 0;'>";
        echo "<label><strong>Test Email Address:</strong></label><br>";
        echo "<input type='email' name='email' value='" . htmlspecialchars($testEmail) . "' style='width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;' required>";
        echo "<small style='color: #666;'>This will test the exact same email function used during registration.</small>";
        echo "</div>";
        echo "<button type='submit' name='send_test' style='background: #2e8b57; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📧 Send Test Registration Email</button>";
        echo "</form>";
        
        echo "<div class='info' style='margin-top: 20px;'>";
        echo "<strong>What this tests:</strong><br>";
        echo "• Uses the exact same sendVerificationEmail() function as registration<br>";
        echo "• Uses the same email configuration (port 465 SSL)<br>";
        echo "• Shows full SMTP debug output<br>";
        echo "• Verifies Gmail queuing status<br>";
        echo "<br><strong>If this works, registration emails will work too!</strong>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div></body></html>";
?>






