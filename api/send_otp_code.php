<?php
// Start output buffering to prevent any output before JSON
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['email']) || empty(trim($input['email']))) {
    ob_clean();
    sendResponse(['error' => 'Email is required'], 400);
}

if (!isset($input['username']) || empty(trim($input['username']))) {
    ob_clean();
    sendResponse(['error' => 'Username is required'], 400);
}

$email = trim($input['email']);
$username = trim($input['username']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(['error' => 'Invalid email format'], 400);
}

try {
    // Check if user exists with matching email and username
    $stmt = $pdo->prepare("SELECT id, email, username FROM users WHERE email = ? AND username = ?");
    $stmt->execute([$email, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Don't reveal if email/username exists for security
        ob_clean();
        sendResponse([
            'success' => true,
            'message' => 'If the email exists in our system, a verification code has been sent.'
        ]);
        return; // Exit early
    }

    // Generate secure 6-digit OTP code using cryptographically secure random number
    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes')); // 10 minutes expiry

    // Create password_reset_otp table if it doesn't exist
    // Use VARCHAR(6) with binary collation to ensure exact string matching
    $createTableSQL = "CREATE TABLE IF NOT EXISTS password_reset_otp (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        otp_code VARCHAR(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
        expires_at DATETIME NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_otp (user_id, otp_code),
        INDEX idx_expires (expires_at)
    )";

    try {
        $pdo->exec($createTableSQL);
        
        // Check if column type is correct, alter if needed
        $checkColumnStmt = $pdo->query("SHOW COLUMNS FROM password_reset_otp WHERE Field = 'otp_code'");
        $colInfo = $checkColumnStmt->fetch(PDO::FETCH_ASSOC);
        if ($colInfo) {
            if (stripos($colInfo['Type'], 'int') !== false) {
                error_log("send_otp_code.php: ⚠️ Column is INT, altering to VARCHAR(6)");
                $pdo->exec("ALTER TABLE password_reset_otp MODIFY COLUMN otp_code VARCHAR(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL");
                error_log("send_otp_code.php: ✅ Column altered to VARCHAR(6)");
            } elseif (stripos($colInfo['Type'], 'varchar') === false) {
                error_log("send_otp_code.php: ⚠️ Column type is {$colInfo['Type']}, may cause issues");
            }
        }
    } catch (PDOException $e) {
        // Table might already exist, ignore
        error_log("send_otp_code.php: Table creation/alteration: " . $e->getMessage());
    }

    // Delete ALL old OTP codes for this user (including unverified ones) when resending
    // This ensures only the latest OTP is valid and prevents confusion
    $stmt = $pdo->prepare("DELETE FROM password_reset_otp WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $deletedCount = $stmt->rowCount();
    error_log("send_otp_code.php: Deleted {$deletedCount} old OTP records for user {$user['id']} (including unverified)");

    // Ensure OTP code is exactly 6 digits with leading zeros (normalize it)
    // Remove any whitespace and non-digit characters, then pad to 6 digits
    $otpCode = preg_replace('/\D/', '', $otpCode); // Remove all non-digits
    $otpCode = str_pad($otpCode, 6, '0', STR_PAD_LEFT); // Pad to exactly 6 digits
    
    // Final validation - ensure it's exactly 6 digits
    if (!preg_match('/^\d{6}$/', $otpCode) || strlen($otpCode) !== 6) {
        error_log("send_otp_code.php: ERROR - Invalid OTP format after normalization: '{$otpCode}' (length: " . strlen($otpCode) . ")");
        ob_clean();
        sendResponse(['error' => 'Failed to generate verification code. Please try again.'], 500);
    }

    // Log before storing
    error_log("send_otp_code.php: About to store OTP for user {$user['id']}: Code='{$otpCode}' (length: " . strlen($otpCode) . ", type: " . gettype($otpCode) . ", hex: " . bin2hex($otpCode) . ")");

    // Insert new OTP code - ensure it's stored as exactly 6 characters (VARCHAR)
    // Use explicit string casting and ensure it's exactly 6 digits
    $otpCodeString = (string)$otpCode;
    if (strlen($otpCodeString) !== 6) {
        $otpCodeString = str_pad($otpCodeString, 6, '0', STR_PAD_LEFT);
    }
    
    // Final validation before storing
    if (!preg_match('/^\d{6}$/', $otpCodeString)) {
        error_log("send_otp_code.php: ERROR - Invalid OTP format before storage: '{$otpCodeString}'");
        ob_clean();
        sendResponse(['error' => 'Failed to generate verification code. Please try again.'], 500);
    }
    
    $stmt = $pdo->prepare("INSERT INTO password_reset_otp (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $otpCodeString, $expiresAt]);
    
    $insertId = $pdo->lastInsertId();
    error_log("send_otp_code.php: ✅ Stored OTP - ID:{$insertId}, Code:'{$otpCodeString}' (length:" . strlen($otpCodeString) . ", hex:" . bin2hex($otpCodeString) . ")");

    // Verify the OTP was stored correctly
    $verifyStmt = $pdo->prepare("SELECT otp_code, LENGTH(otp_code) as code_length FROM password_reset_otp WHERE id = ?");
    $verifyStmt->execute([$insertId]);
    $storedOtp = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if ($storedOtp) {
        error_log("send_otp_code.php: ✅ OTP stored successfully - User: {$user['id']}, Generated: '{$otpCode}', Stored: '{$storedOtp['otp_code']}' (length: {$storedOtp['code_length']})");

        // Double-check they match
        if ($storedOtp['otp_code'] !== $otpCode) {
            error_log("send_otp_code.php: ⚠️ WARNING - Code mismatch! Generated: '{$otpCode}', Stored: '{$storedOtp['otp_code']}'");
        }
    } else {
        error_log("send_otp_code.php: ⚠️ WARNING - Could not verify stored OTP");
    }

    // Log OTP creation for debugging
    error_log("OTP created for user {$user['id']} ({$email}): Code={$otpCode}, Expires={$expiresAt}");

    // Send email using EXACT same method as test_registration_direct.php (which works!)
    try {
        // Load PHPMailer directly (same as test_registration_direct.php)
        $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        } else {
            $phpmailerPath = __DIR__ . '/../PHPMailer/src/';
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
        $config = require __DIR__ . '/../config/email_config.php';

        // Clear any existing output buffer before sending email
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        error_log("Attempting to send OTP email to {$email} for user {$user['id']} using direct method");

        // Create mailer (EXACT same as test_registration_direct.php)
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Capture debug output
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

        // Configure (EXACT same as test_registration_direct.php)
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

        // Use password reset email format
        $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'AgriMarket - Password Reset Verification Code';

        // Password reset email body
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
        $currentYear = date('Y');

        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2e8b57, #3cb371); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-box { background: #2e8b57; color: white; font-size: 32px; font-weight: bold; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 Password Reset Request</h1>
                    <p>AgriMarket - Agriculture Platform</p>
                </div>
                <div class='content'>
                    <p>Hello <strong>" . $safeUsername . "</strong>,</p>
                    
                    <p>You requested to reset your password for your AgriMarket account.</p>
                    
                    <p>Your verification code is:</p>
                    
                    <div class='otp-box'>" . $safeCode . "</div>
                    
                    <p>This code will expire in <strong>10 minutes</strong>.</p>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong><br>
                        If you didn't request this password reset, please ignore this email or contact our support team immediately.
                    </div>
                    
                    <p>Thank you for using AgriMarket!</p>
                    
                    <p>Best regards,<br>
                    <strong>AgriMarket Team</strong></p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>&copy; " . $currentYear . " AgriMarket. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "Password Reset Request\n\nHello " . $username . ",\n\nYou requested to reset your password for your AgriMarket account.\n\nYour verification code is: " . $otpCode . "\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this password reset, please ignore this email.\n\nThank you for using AgriMarket!\n\nBest regards,\nAgriMarket Team";

        $sent = $mail->send();
        $buffered = ob_get_clean();

        // Process buffered output
        if (!empty($buffered)) {
            $debugOutput .= "\n--- Buffered Output ---\n" . $buffered;
        }

        // Analyze (EXACT same as test_registration_direct.php)
        $gmailQueued = false;
        if (preg_match('/DATA.*?250\s+(2\.0\.0\s+)?OK/si', $debugOutput)) {
            $gmailQueued = true;
        }

        // Restart output buffering after email is sent
        ob_start();

        // Clear output before sending JSON
        ob_clean();

        if ($sent && $gmailQueued) {
            error_log("OTP email queued by Gmail for $email - Code: $otpCode");
            sendResponse([
                'success' => true,
                'message' => 'Verification code has been sent to your email address. Please check your inbox.',
                'user_id' => $user['id'],
                'gmail_queued' => true,
                'delivery_confirmed' => true,
                'otp_code' => $otpCode // Include for testing if email fails
            ]);
        } elseif ($sent) {
            error_log("OTP email sent but Gmail confirmation unclear for $email - Code: $otpCode");
            sendResponse([
                'success' => true,
                'message' => 'Verification code has been sent. Please check your inbox and spam folder. If not received, check Gmail security settings.',
                'user_id' => $user['id'],
                'gmail_queued' => false,
                'delivery_confirmed' => false,
                'otp_code' => $otpCode // Include for testing if email fails
            ]);
        } else {
            $errorInfo = $mail->ErrorInfo ?? 'Unknown error';
            error_log("Failed to send OTP email to $email: $errorInfo - Code: $otpCode");
            error_log("SMTP Debug: " . $debugOutput);
            ob_clean();
            sendResponse([
                'success' => false,
                'error' => 'Failed to send email.',
                'message' => 'Failed to send email: ' . $errorInfo,
                'user_id' => $user['id'],
                'otp_code' => $otpCode, // Include OTP for testing - user can manually enter if email fails
                'note' => 'Email sending failed, but OTP code is shown above for testing.'
            ]);
        }
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $buffered = ob_get_clean();
        error_log("PHPMailer Exception sending OTP email to $email: " . $e->getMessage());
        ob_start();
        ob_clean();
        sendResponse([
            'success' => false,
            'error' => 'Failed to send email. Please try again later.',
            'message' => 'Email sending failed: ' . $e->getMessage(),
            'user_id' => $user['id'],
            'otp_code' => $otpCode // Include for testing
        ]);
    } catch (Exception $e) {
        error_log("Exception sending OTP email: " . $e->getMessage());
        ob_clean();
        sendResponse([
            'success' => false,
            'error' => 'Failed to send email. Please try again later.',
            'message' => 'Email service error. Please contact support if the problem persists.',
            'user_id' => $user['id'],
            'otp_code' => $otpCode // Include for testing
        ]);
    }
} catch (PDOException $e) {
    ob_clean();
    error_log("Database error in send_otp_code.php: " . $e->getMessage());
    sendResponse(['error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    ob_clean();
    error_log("General error in send_otp_code.php: " . $e->getMessage());
    sendResponse(['error' => 'An error occurred. Please try again.'], 500);
}
