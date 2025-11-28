<?php
/**
 * Direct Email Test - This will show you EXACTLY what's wrong
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Load PHPMailer directly
$phpmailerPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/';
if (!file_exists($phpmailerPath . 'PHPMailer.php')) {
    $phpmailerPath = __DIR__ . '/PHPMailer/src/';
}

if (file_exists($phpmailerPath . 'PHPMailer.php')) {
    require_once $phpmailerPath . 'Exception.php';
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Email config
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'trancem260@gmail.com';
$smtp_password = 'volqrkihttibtyko'; // Your App Password (16 characters, no spaces, no hyphens)
$smtp_from_email = 'trancem260@gmail.com';
$smtp_from_name = 'AgriMarket - Agriculture Platform';
$test_email = 'trancem260@gmail.com'; // Where to send test email

?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
        h1 { color: #333; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Direct Email Test - Step by Step</h1>

<?php
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<div class='error'>❌ PHPMailer not found! Please install it.</div>";
    exit;
}

echo "<div class='success'>✅ PHPMailer loaded successfully</div>";

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    echo "<h2>Step 1: SMTP Configuration</h2>";
    echo "<div class='info'>";
    echo "Host: $smtp_host<br>";
    echo "Port: $smtp_port<br>";
    echo "Username: $smtp_username<br>";
    echo "Password: " . substr($smtp_password, 0, 4) . "****<br>";
    echo "</div>";
    
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "<pre style='background:#fff3cd;padding:10px;margin:5px 0;'>[DEBUG] " . htmlspecialchars(trim($str)) . "</pre>";
    };
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_port;
    $mail->Timeout = 30;
    
    // Disable SSL verification (for testing)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Recipients
    $mail->setFrom($smtp_from_email, $smtp_from_name);
    $mail->addAddress($test_email);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test OTP Email from AgriMarket';
    $mail->Body = '<h2>Test OTP Email</h2><p>This is a test email to verify email sending is working.</p><p><strong>Test OTP Code: 123456</strong></p>';
    $mail->AltBody = 'Test OTP Email. Test OTP Code: 123456';
    
    echo "<h2>Step 2: Attempting to Send Email</h2>";
    echo "<div class='info'>Sending to: $test_email</div>";
    
    $mail->send();
    
    echo "<div class='success'>";
    echo "✅ SUCCESS! Email sent successfully!<br><br>";
    echo "Please check your inbox at: <strong>$test_email</strong><br>";
    echo "Also check your <strong>spam/junk folder</strong><br>";
    echo "Test OTP Code: <strong>123456</strong>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ FAILED to send email!<br><br>";
    echo "<strong>Error Message:</strong><br>";
    echo htmlspecialchars($mail->ErrorInfo) . "<br><br>";
    echo "<strong>Exception:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "<br><br>";
    
    // Common error solutions
    echo "<strong>Common Solutions:</strong><br>";
    if (strpos($mail->ErrorInfo, '535') !== false || strpos($mail->ErrorInfo, 'Authentication') !== false) {
        echo "1. ❌ <strong>Authentication Failed</strong> - Your App Password is incorrect<br>";
        echo "   → Go to: https://myaccount.google.com/apppasswords<br>";
        echo "   → Generate a NEW App Password for 'Mail'<br>";
        echo "   → Update the password in email_config.php<br><br>";
    }
    if (strpos($mail->ErrorInfo, 'connect') !== false) {
        echo "2. ❌ <strong>Connection Failed</strong> - Cannot connect to Gmail SMTP<br>";
        echo "   → Check your internet connection<br>";
        echo "   → Check firewall/antivirus settings<br>";
        echo "   → Try port 465 with SSL instead<br><br>";
    }
    if (strpos($mail->ErrorInfo, '2-Step Verification') !== false) {
        echo "3. ❌ <strong>2-Step Verification Required</strong><br>";
        echo "   → Enable 2-Step Verification on your Gmail account<br>";
        echo "   → Then generate an App Password<br><br>";
    }
    
    echo "</div>";
}
?>

    <h2>Next Steps</h2>
    <div class="info">
        <strong>If email sent successfully:</strong><br>
        1. Check your inbox and spam folder<br>
        2. If you received it, the OTP system should work<br>
        3. Try requesting an OTP from your website<br><br>
        
        <strong>If email failed:</strong><br>
        1. Follow the error solutions above<br>
        2. Regenerate your Gmail App Password<br>
        3. Make sure 2-Step Verification is enabled<br>
        4. Try this test again
    </div>
</div>
</body>
</html>

