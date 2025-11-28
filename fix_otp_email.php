<?php
/**
 * Quick Fix Script for OTP Email Issues
 * This script will attempt to fix common configuration issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Fix OTP Email Issues</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
h1 { color: #2e8b57; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔧 OTP Email Quick Fix</h1>";

$fixes = [];

// Fix 1: Check and remove spaces from password
try {
    $configFile = __DIR__ . '/config/email_config.php';
    if (file_exists($configFile)) {
        $configContent = file_get_contents($configFile);
        $config = require $configFile;
        
        // Check if password has spaces
        if (isset($config['smtp_password']) && strpos($config['smtp_password'], ' ') !== false) {
            $oldPassword = $config['smtp_password'];
            $newPassword = str_replace(' ', '', $config['smtp_password']);
            $configContent = str_replace("'smtp_password' => '" . addslashes($oldPassword) . "'", "'smtp_password' => '" . $newPassword . "'", $configContent);
            file_put_contents($configFile, $configContent);
            $fixes[] = "✅ Removed spaces from app password";
        } else {
            $fixes[] = "✅ App password format is correct (no spaces)";
        }
    }
} catch (Exception $e) {
    $fixes[] = "⚠️ Could not check password format: " . $e->getMessage();
}

// Fix 2: Test PHPMailer loading
try {
    require_once __DIR__ . '/config/phpmailer_helper.php';
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $fixes[] = "✅ PHPMailer is loaded correctly";
    } else {
        $fixes[] = "❌ PHPMailer is not loaded - run: composer install";
    }
} catch (Exception $e) {
    $fixes[] = "❌ Error loading PHPMailer: " . $e->getMessage();
}

// Fix 3: Test database connection
try {
    require_once __DIR__ . '/config/database.php';
    $stmt = $pdo->query("SELECT 1");
    $fixes[] = "✅ Database connection working";
    
    // Check if tables exist
    $tables = ['users', 'password_reset_otp'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $fixes[] = "✅ Table '$table' exists";
        } else {
            $fixes[] = "⚠️ Table '$table' does not exist (will be created automatically)";
        }
    }
} catch (Exception $e) {
    $fixes[] = "❌ Database error: " . $e->getMessage();
}

// Fix 4: Test email configuration
try {
    $config = getEmailConfig();
    if (empty($config['smtp_password']) || $config['smtp_password'] === 'your-app-password') {
        $fixes[] = "❌ App password not configured - update email_config.php";
    } else {
        $fixes[] = "✅ Email configuration appears valid";
        $fixes[] = "✅ App password length: " . strlen(str_replace(' ', '', $config['smtp_password'])) . " characters";
        $fixes[] = "✅ SMTP Host: " . $config['smtp_host'];
        $fixes[] = "✅ SMTP Port: " . $config['smtp_port'];
        $fixes[] = "✅ Encryption: " . $config['smtp_encryption'];
        $fixes[] = "ℹ️ To test actual email sending, use: <a href='test_email_otp_complete.php'>test_email_otp_complete.php</a>";
    }
} catch (Exception $e) {
    $fixes[] = "⚠️ Email config test: " . $e->getMessage();
}

// Display results
echo "<h2>Fix Results:</h2>";
foreach ($fixes as $fix) {
    if (strpos($fix, '✅') !== false) {
        echo "<div class='success'>" . htmlspecialchars($fix) . "</div>";
    } elseif (strpos($fix, '❌') !== false) {
        echo "<div class='error'>" . htmlspecialchars($fix) . "</div>";
    } else {
        echo "<div class='info'>" . htmlspecialchars($fix) . "</div>";
    }
}

echo "<h2>Next Steps:</h2>";
echo "<div class='info'>";
echo "<strong>1. Test Email Sending:</strong><br>";
echo "Go to: <a href='test_email_otp_complete.php'>test_email_otp_complete.php</a> and click 'Send Test OTP Email'<br><br>";

echo "<strong>2. If Port 587 Doesn't Work:</strong><br>";
echo "Try using port 465 with SSL instead. Update email_config.php:<br>";
echo "<pre>'smtp_port' => 465,\n'smtp_encryption' => 'ssl',</pre><br>";

echo "<strong>3. Check Firewall:</strong><br>";
echo "Ensure Windows Firewall allows outbound connections on port 587 (or 465)<br><br>";

echo "<strong>4. Check Gmail Settings:</strong><br>";
echo "Verify 2-Step Verification is enabled and the app password is correct<br>";
echo "</div>";

echo "</div></body></html>";
?>

