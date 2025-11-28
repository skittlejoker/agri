<?php
/**
 * Complete OTP System Debug - Checks Everything
 * Database, Email, Configuration, and Full Flow
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Complete OTP Debug</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 1200px; margin: 0 auto; }
h1 { color: #2e8b57; border-bottom: 3px solid #2e8b57; padding-bottom: 10px; }
h2 { color: #333; margin-top: 30px; border-left: 4px solid #2e8b57; padding-left: 10px; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #004085; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; font-size: 11px; white-space: pre-wrap; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
th { background: #2e8b57; color: white; }
.code { background: #2e8b57; color: white; padding: 20px; text-align: center; border-radius: 8px; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔍 Complete OTP System Diagnostic</h1>";

$allChecks = [];

try {
    // Step 1: Database Connection
    echo "<h2>Step 1: Database Connection & Tables</h2>";
    require_once __DIR__ . '/config/database.php';
    
    $allChecks[] = ['status' => 'success', 'message' => 'Database connection successful'];
    
    // Check users table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        $allChecks[] = ['status' => 'success', 'message' => "Users table exists with $userCount users"];
        
        // Show sample users
        $stmt = $pdo->query("SELECT id, email, username, user_type FROM users LIMIT 5");
        $users = $stmt->fetchAll();
        if (!empty($users)) {
            echo "<div class='info'>";
            echo "<strong>Sample Users (for testing):</strong><br>";
            echo "<table><tr><th>ID</th><th>Email</th><th>Username</th><th>Type</th></tr>";
            foreach ($users as $user) {
                echo "<tr><td>{$user['id']}</td><td>{$user['email']}</td><td>{$user['username']}</td><td>{$user['user_type']}</td></tr>";
            }
            echo "</table>";
            echo "</div>";
        }
    } catch (Exception $e) {
        $allChecks[] = ['status' => 'error', 'message' => 'Users table error: ' . $e->getMessage()];
    }
    
    // Check password_reset_otp table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_otp'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM password_reset_otp");
            $otpCount = $stmt->fetch()['count'];
            $allChecks[] = ['status' => 'success', 'message' => "password_reset_otp table exists with $otpCount records"];
            
            // Show recent OTPs
            $stmt = $pdo->query("SELECT o.*, u.email, u.username FROM password_reset_otp o 
                                JOIN users u ON o.user_id = u.id 
                                ORDER BY o.created_at DESC LIMIT 5");
            $otps = $stmt->fetchAll();
            if (!empty($otps)) {
                echo "<div class='info'>";
                echo "<strong>Recent OTP Codes:</strong><br>";
                echo "<table><tr><th>User</th><th>Email</th><th>OTP Code</th><th>Expires</th><th>Verified</th><th>Created</th></tr>";
                foreach ($otps as $otp) {
                    $expired = strtotime($otp['expires_at']) < time() ? ' (EXPIRED)' : '';
                    $verified = $otp['verified'] ? 'Yes' : 'No';
                    echo "<tr>";
                    echo "<td>{$otp['username']}</td>";
                    echo "<td>{$otp['email']}</td>";
                    echo "<td><strong>{$otp['otp_code']}</strong></td>";
                    echo "<td>{$otp['expires_at']}$expired</td>";
                    echo "<td>$verified</td>";
                    echo "<td>{$otp['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
            }
        } else {
            $allChecks[] = ['status' => 'warning', 'message' => 'password_reset_otp table does not exist (will be created automatically)'];
        }
    } catch (Exception $e) {
        $allChecks[] = ['status' => 'error', 'message' => 'OTP table check error: ' . $e->getMessage()];
    }
    
    // Step 2: Email Configuration
    echo "<h2>Step 2: Email Configuration</h2>";
    require_once __DIR__ . '/config/phpmailer_helper.php';
    $config = getEmailConfig();
    
    $allChecks[] = ['status' => 'success', 'message' => 'Email config loaded'];
    $allChecks[] = ['status' => $config['smtp_host'] === 'smtp.gmail.com' ? 'success' : 'warning', 
                   'message' => "SMTP Host: {$config['smtp_host']}"];
    $allChecks[] = ['status' => 'success', 'message' => "SMTP Port: {$config['smtp_port']}"];
    $allChecks[] = ['status' => 'success', 'message' => "Encryption: {$config['smtp_encryption']}"];
    $allChecks[] = ['status' => !empty($config['smtp_username']) ? 'success' : 'error', 
                   'message' => "SMTP Username: {$config['smtp_username']}"];
    
    $passLength = strlen($config['smtp_password']);
    $passStatus = ($passLength === 16 && $config['smtp_password'] !== 'your-app-password') ? 'success' : 'error';
    $allChecks[] = ['status' => $passStatus, 
                   'message' => "SMTP Password: " . ($passLength === 16 ? "Set (16 characters)" : "Invalid length: $passLength")];
    
    // Step 3: PHPMailer
    echo "<h2>Step 3: PHPMailer Installation</h2>";
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $allChecks[] = ['status' => 'success', 'message' => 'PHPMailer is loaded'];
    } else {
        $allChecks[] = ['status' => 'error', 'message' => 'PHPMailer is NOT loaded'];
    }
    
    // Step 4: Test Email Sending
    echo "<h2>Step 4: Test Email Sending</h2>";
    
    if (isset($_POST['test_email']) && !empty($_POST['test_email'])) {
        $testEmail = trim($_POST['test_email']);
        $testUsername = 'TestUser';
        $testOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        echo "<div class='info'>";
        echo "<strong>Testing email to:</strong> " . htmlspecialchars($testEmail) . "<br>";
        echo "<strong>OTP Code:</strong> <div class='code'>" . $testOTP . "</div>";
        echo "</div>";
        
        ob_start();
        $startTime = microtime(true);
        $result = sendOTPEmail($testEmail, $testUsername, $testOTP);
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        $output = ob_get_clean();
        
        echo "<div class='info'>Execution Time: $executionTime ms</div>";
        
        if ($result['success']) {
            echo "<div class='success'>";
            echo "✅ <strong>Email sent successfully!</strong><br>";
            if (isset($result['gmail_accepted']) && $result['gmail_accepted']) {
                echo "✅ Gmail accepted the email (success codes found)<br>";
            } else {
                echo "⚠️ No clear acceptance codes - check spam folder<br>";
            }
            echo "</div>";
            
            if (isset($result['debug']) && !empty($result['debug'])) {
                echo "<div class='info'>";
                echo "<strong>SMTP Debug Output:</strong><br>";
                echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
                echo "</div>";
            }
            
            if (isset($result['smtp_responses']) && !empty($result['smtp_responses'])) {
                echo "<div class='info'>";
                echo "<strong>SMTP Responses:</strong><br>";
                echo "<pre>" . htmlspecialchars(implode("\n", $result['smtp_responses'])) . "</pre>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "❌ <strong>Email sending failed!</strong><br>";
            echo htmlspecialchars($result['message']) . "<br><br>";
            
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
        echo "<input type='email' name='test_email' value='{$config['smtp_username']}' style='width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;' required>";
        echo "</div>";
        echo "<button type='submit' style='background: #2e8b57; color: white; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>📧 Send Test Email</button>";
        echo "</form>";
    }
    
    // Display all checks
    echo "<h2>Summary of All Checks</h2>";
    foreach ($allChecks as $check) {
        $class = $check['status'] === 'success' ? 'success' : ($check['status'] === 'error' ? 'error' : 'warning');
        $icon = $check['status'] === 'success' ? '✅' : ($check['status'] === 'error' ? '❌' : '⚠️');
        echo "<div class='$class'>$icon " . htmlspecialchars($check['message']) . "</div>";
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






