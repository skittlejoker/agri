<?php
/**
 * Test Verify Email API
 * Tests the send_verification_email.php endpoint
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Verify Email API</title>";
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
echo "<h1>📧 Test Verify Email API</h1>";
echo "<p><strong>This tests the send_verification_email.php API endpoint</strong></p>";

try {
    require_once __DIR__ . '/config/database.php';
    $config = require __DIR__ . '/config/email_config.php';
    
    $testEmail = isset($_POST['email']) ? trim($_POST['email']) : $config['smtp_username'];
    $sendTest = isset($_POST['send_test']);
    
    if ($sendTest) {
        echo "<div class='info'>";
        echo "<strong>Testing API Endpoint:</strong><br>";
        echo "URL: api/send_verification_email.php<br>";
        echo "Email: " . htmlspecialchars($testEmail) . "<br>";
        echo "</div>";
        
        // Call the API endpoint
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/E-commerce/agriculture-marketplace/api/send_verification_email.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $testEmail]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        echo "<div class='info'>";
        echo "<strong>API Response:</strong><br>";
        echo "HTTP Code: $httpCode<br>";
        if ($curlError) {
            echo "CURL Error: " . htmlspecialchars($curlError) . "<br>";
        }
        echo "</div>";
        
        if ($response) {
            $result = json_decode($response, true);
            
            if ($result) {
                if ($result['success']) {
                    echo "<div class='success'>";
                    echo "✅ <strong>SUCCESS!</strong><br>";
                    echo htmlspecialchars($result['message']) . "<br>";
                    if (isset($result['gmail_queued']) && $result['gmail_queued']) {
                        echo "✅ Gmail queued email for delivery<br>";
                    }
                    if (isset($result['verification_code'])) {
                        echo "🔑 Verification Code: <div class='code'>" . htmlspecialchars($result['verification_code']) . "</div>";
                    }
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "❌ <strong>FAILED!</strong><br>";
                    echo htmlspecialchars($result['error'] ?? 'Unknown error') . "<br>";
                    if (isset($result['verification_code'])) {
                        echo "🔑 Verification Code (for testing): <div class='code'>" . htmlspecialchars($result['verification_code']) . "</div>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<div class='error'>";
                echo "❌ <strong>Invalid JSON Response:</strong><br>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>No response from API</strong><br>";
            echo "Check if the API endpoint exists and is accessible.";
            echo "</div>";
        }
        
    } else {
        echo "<form method='POST'>";
        echo "<div style='margin: 15px 0;'>";
        echo "<label><strong>Test Email Address:</strong></label><br>";
        echo "<input type='email' name='email' value='" . htmlspecialchars($testEmail) . "' style='width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;' required>";
        echo "<small style='color: #666;'>Enter an email address that exists in the users table</small>";
        echo "</div>";
        echo "<button type='submit' name='send_test' style='background: #2e8b57; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📧 Test Send Verification Email</button>";
        echo "</form>";
        
        echo "<div class='info' style='margin-top: 20px;'>";
        echo "<strong>What this tests:</strong><br>";
        echo "• Calls the send_verification_email.php API endpoint<br>";
        echo "• Uses the same reliable email method as test_registration_direct.php<br>";
        echo "• Shows the API response and verification code<br>";
        echo "• If this works, the verify page should work too!<br>";
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






