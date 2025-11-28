<?php
// PHPMailer Helper Functions
// Make sure PHPMailer is installed via Composer: composer require phpmailer/phpmailer

// Check if PHPMailer is installed
$phpmailerAvailable = false;
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';

if (file_exists($vendorAutoload)) {
    try {
        require_once $vendorAutoload;
        // Check if PHPMailer classes are available
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $phpmailerAvailable = true;
        }
    } catch (Exception $e) {
        error_log("Error loading Composer autoload: " . $e->getMessage());
    }
}

// Fallback: Try to load PHPMailer manually if not using Composer
if (!$phpmailerAvailable) {
    // Try vendor directory first
    $phpmailerPath = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
    if (file_exists($phpmailerPath . 'PHPMailer.php')) {
        try {
            require_once $phpmailerPath . 'Exception.php';
            require_once $phpmailerPath . 'PHPMailer.php';
            require_once $phpmailerPath . 'SMTP.php';
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $phpmailerAvailable = true;
            }
        } catch (Exception $e) {
            error_log("Error loading PHPMailer from vendor: " . $e->getMessage());
        }
    }

    // Try PHPMailer directory as fallback
    if (!$phpmailerAvailable) {
        $phpmailerPath = __DIR__ . '/../PHPMailer/src/';
        if (file_exists($phpmailerPath . 'PHPMailer.php')) {
            try {
                require_once $phpmailerPath . 'Exception.php';
                require_once $phpmailerPath . 'PHPMailer.php';
                require_once $phpmailerPath . 'SMTP.php';
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $phpmailerAvailable = true;
                }
            } catch (Exception $e) {
                error_log("Error loading PHPMailer from PHPMailer directory: " . $e->getMessage());
            }
        }
    }
}

require_once __DIR__ . '/email_config.php';

// Use statements must be at top level - we'll check class existence in functions
if ($phpmailerAvailable) {
    // PHPMailer is available
} else {
    error_log("PHPMailer not found. Email functionality will be disabled. Run: composer install");
}

/**
 * Send email using PHPMailer
 * 
 * @param string $to Email address of recipient
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $altBody Plain text alternative
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($to, $subject, $body, $altBody = '')
{
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return [
            'success' => false,
            'message' => 'PHPMailer is not installed. Please run: composer install'
        ];
    }

    $config = getEmailConfig();

    // Use fully qualified class name since we can't conditionally use 'use'
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    // Capture debug output (always capture, even if debug mode is off)
    $debugOutput = '';
    $smtpResponses = [];

    try {
        // Validate config before using
        if (empty($config['smtp_host']) || empty($config['smtp_username']) || empty($config['smtp_password'])) {
            return [
                'success' => false,
                'message' => 'Email configuration is incomplete. Please check email_config.php'
            ];
        }

        // Check if password is still the placeholder
        if ($config['smtp_password'] === 'your-app-password') {
            return [
                'success' => false,
                'message' => 'Email password not configured. Please update email_config.php with your Gmail App Password. See: https://myaccount.google.com/apppasswords'
            ];
        }

        // Verify credentials are not empty
        if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
            throw new Exception('SMTP username or password is empty');
        }

        // Server settings - Use EXACT same configuration as test_email_direct_capture.php (which works!)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Force Gmail host (same as working test)
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = str_replace(' ', '', $config['smtp_password']); // Remove spaces when setting (same as working test)

        // Use port 465 with SSL (most reliable configuration - same as test_email_direct_capture.php)
        // Force port 465 SSL for maximum reliability
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port = 465; // Force port 465

        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 2; // Maximum debug (same as working test)
        @$mail->Timeout = 60; // Increased timeout for Windows/XAMPP

        // Additional SMTP options for better compatibility with Windows/XAMPP
        // Same configuration as test_email_direct_capture.php (which works)
        @$mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Set up debug output capture - Use EXACT same method as test_email_direct_capture.php (which works!)
        // Method 1: Output buffering (same as working test)
        ob_start();

        // Method 2: Debug callback (same as working test)
        $mail->Debugoutput = function ($str, $level) use (&$debugOutput, &$smtpResponses) {
            $message = trim($str);
            if (empty($message)) return;

            $debugOutput .= "[$level] $message\n";

            // Capture response codes (same as working test)
            if (preg_match('/^(\d{3})\s+(.+)/', $message, $matches)) {
                $smtpResponses[] = ['code' => $matches[1], 'text' => $matches[2], 'full' => $message];
            }
        };

        // Recipients
        $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        // Add message ID for better tracking (only if property exists)
        // Note: Custom headers removed to avoid compatibility issues
        // The email will still send successfully without them

        // Actually send the email and verify
        $sent = false;
        $lastError = '';

        try {
            // Send with error handling - Use EXACT same method as test_email_direct_capture.php
            $sent = $mail->send();
            $bufferedOutput = ob_get_clean();

            // Process buffered output (same as working test)
            if (!empty($bufferedOutput)) {
                $debugOutput .= "\n--- Buffered Output ---\n" . $bufferedOutput;
            }

            $lastError = $mail->ErrorInfo ?? '';

            // If debug output is still empty, create a basic one
            if (empty($debugOutput)) {
                $debugOutput = "SMTP Debug Output:\n";
                $debugOutput .= "SMTPDebug Level: " . $mail->SMTPDebug . "\n";
                $debugOutput .= "ErrorInfo: " . ($lastError ?: 'None') . "\n";
                $debugOutput .= "Send Result: " . ($sent ? 'true' : 'false') . "\n";
                $debugOutput .= "Note: Debug output callback may not be working. Check PHP error logs for SMTP messages.\n";
            }

            // Double-check: if send() returned true but ErrorInfo is not empty, something might be wrong
            if ($sent && !empty($lastError) && stripos($lastError, 'error') !== false) {
                error_log("Warning: send() returned true but ErrorInfo contains: " . $lastError);
            }
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $lastError = $e->getMessage();
            $bufferedOutput = ob_get_clean();
            if (!empty($bufferedOutput)) {
                $debugOutput .= "\n--- Buffered Output (Exception) ---\n" . $bufferedOutput;
            }
            error_log("PHPMailer Exception: " . $lastError);
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            $bufferedOutput = ob_get_clean();
            if (!empty($bufferedOutput)) {
                $debugOutput .= "\n--- Buffered Output (General Exception) ---\n" . $bufferedOutput;
            }
            error_log("General Exception in sendEmail: " . $lastError);
        }

        // Ensure we always have debug output - if still empty, create a basic one
        if (empty($debugOutput)) {
            $debugOutput = "Warning: No debug output captured. SMTPDebug was set to: " . $mail->SMTPDebug . "\n";
            $debugOutput .= "ErrorInfo: " . ($lastError ?: 'None') . "\n";
            $debugOutput .= "Send result: " . ($sent ? 'true' : 'false') . "\n";
        }

        // Verify the email was actually sent
        if (!$sent) {
            $errorInfo = $lastError ?: 'Unknown error - send() returned false';

            // Analyze SMTP responses to understand the failure
            $analysis = [];
            if (!empty($smtpResponses)) {
                foreach ($smtpResponses as $response) {
                    // Handle both array format (from callback) and string format
                    $responseStr = is_array($response) ? ($response['code'] ?? $response['full'] ?? '') : $response;
                    if (preg_match('/^535/', $responseStr)) {
                        $analysis[] = 'Authentication failed - App password may be incorrect';
                    } elseif (preg_match('/^534/', $responseStr)) {
                        $analysis[] = 'Authentication failed - Check 2-Step Verification';
                    } elseif (preg_match('/^550/', $responseStr)) {
                        $analysis[] = 'Mailbox unavailable - Email address may be invalid';
                    } elseif (preg_match('/^553/', $responseStr)) {
                        $analysis[] = 'Mailbox name not allowed';
                    }
                }
            }

            return [
                'success' => false,
                'message' => 'Email sending failed: ' . $errorInfo,
                'debug' => $debugOutput ?: 'No debug output captured',
                'smtp_responses' => $smtpResponses,
                'analysis' => $analysis,
                'error_info' => $errorInfo,
                'debug_length' => strlen($debugOutput)
            ];
        }

        // CRITICAL: Analyze debug output to verify Gmail actually accepted and queued the email
        // Use EXACT same analysis as test_email_direct_capture.php (which works!)
        $gmailAccepted = false;
        $gmailQueued = false;
        $hasErrorCodes = false;
        $criticalErrors = [];

        if (!empty($debugOutput)) {
            // Analyze - Use EXACT same pattern as test_email_direct_capture.php
            if (preg_match('/DATA.*?250\s+(2\.0\.0\s+)?OK/si', $debugOutput)) {
                $gmailQueued = true;
            }

            // Check for error codes (5xx = server errors, 4xx = temporary errors)
            if (preg_match_all('/\b([45][0-9]{2})\s+(.+)/i', $debugOutput, $errorMatches)) {
                $hasErrorCodes = true;
                foreach ($errorMatches[1] as $index => $code) {
                    $message = $errorMatches[2][$index] ?? '';
                    if (preg_match('/^5/', $code)) {
                        $criticalErrors[] = "Error $code: $message";
                    }
                }
                error_log("Warning: SMTP error codes detected: " . implode(', ', $errorMatches[1]));
            }

            // Check for authentication issues
            if (preg_match('/535|534|Authentication failed|Invalid login/i', $debugOutput)) {
                $criticalErrors[] = "Authentication failed - Check app password and 2-Step Verification";
            }
        }

        // If we have critical errors, treat as failure even if send() returned true
        if (!empty($criticalErrors)) {
            return [
                'success' => false,
                'message' => 'Email sending failed: ' . implode('; ', $criticalErrors),
                'debug' => $debugOutput ?: 'No debug output captured',
                'smtp_responses' => $smtpResponses,
                'analysis' => $criticalErrors,
                'error_info' => 'Critical SMTP errors detected',
                'debug_length' => strlen($debugOutput)
            ];
        }

        // Log success with details
        $logMessage = "Email sent to {$to}";
        if ($gmailQueued) {
            $logMessage .= " - Gmail queued for delivery (250 after DATA)";
        } elseif ($gmailAccepted) {
            $logMessage .= " - Gmail accepted but delivery status unclear";
        } else {
            $logMessage .= " - WARNING: No clear acceptance confirmation";
        }
        error_log($logMessage);

        // If Gmail didn't clearly queue it, warn the user
        $userMessage = 'Email sent successfully';
        if (!$gmailQueued) {
            $userMessage .= ' - However, Gmail delivery confirmation is unclear. ';
            $userMessage .= 'Please check your inbox and spam folder. ';
            $userMessage .= 'If email is not received, check Gmail security settings for blocked sign-in attempts.';
        } else {
            $userMessage .= ' and queued by Gmail for delivery. Check your inbox (and spam folder).';
        }

        // ALWAYS return debug output, even if empty (so we can diagnose why it's empty)
        return [
            'success' => true,
            'message' => $userMessage,
            'debug' => $debugOutput ?: 'No debug output captured - check SMTPDebug setting',
            'smtp_responses' => $smtpResponses,
            'gmail_accepted' => $gmailAccepted,
            'gmail_queued' => $gmailQueued,
            'has_errors' => $hasErrorCodes,
            'delivery_confirmed' => $gmailQueued,
            'debug_length' => strlen($debugOutput),
            'smtp_debug_level' => $mail->SMTPDebug
        ];
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $errorInfo = $mail->ErrorInfo ?? $e->getMessage();

        // Log full error with debug output
        $fullError = "PHPMailer Error sending to {$to}: " . $errorInfo;
        if (!empty($debugOutput)) {
            $fullError .= "\nDebug Output:\n" . $debugOutput;
        }
        error_log($fullError);

        // Provide user-friendly error messages
        $userMessage = 'Email could not be sent.';
        if (strpos($errorInfo, 'SMTP connect() failed') !== false || strpos($errorInfo, 'Connection refused') !== false) {
            $userMessage = 'Could not connect to Gmail SMTP server. This might be due to:<br>1. Firewall blocking port 587<br>2. Antivirus blocking the connection<br>3. Network issues<br><strong>Try:</strong> Switch to port 465 with SSL encryption in email_config.php';
        } elseif (strpos($errorInfo, 'Authentication failed') !== false || strpos($errorInfo, 'Invalid login') !== false || strpos($errorInfo, '535') !== false || strpos($errorInfo, '534') !== false) {
            $userMessage = 'Email authentication failed. Possible causes:<br>1. Gmail App Password is incorrect<br>2. 2-Step Verification not enabled<br>3. App password was revoked<br><strong>Fix:</strong> Generate a new app password at https://myaccount.google.com/apppasswords';
        } elseif (strpos($errorInfo, 'password') !== false) {
            $userMessage = 'Email password is incorrect. Please update email_config.php with your Gmail App Password (not your regular password).';
        } elseif (strpos($errorInfo, 'your-app-password') !== false) {
            $userMessage = 'Gmail App Password not configured. Please update email_config.php with your Gmail App Password from https://myaccount.google.com/apppasswords';
        } elseif (strpos($errorInfo, 'timeout') !== false || strpos($errorInfo, 'timed out') !== false) {
            $userMessage = 'Connection timeout. Gmail server did not respond in time. This might be due to:<br>1. Slow internet connection<br>2. Firewall blocking the connection<br>3. Gmail server issues<br><strong>Try:</strong> Check your internet connection and firewall settings';
        }

        return [
            'success' => false,
            'message' => $userMessage . ' (Error: ' . $errorInfo . ')',
            'debug' => $config['smtp_debug'] > 0 ? $debugOutput : null
        ];
    } catch (Exception $e) {
        error_log("General Exception in sendEmail: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Email service error: ' . $e->getMessage()
        ];
    }
}

/**
 * Get email configuration
 * 
 * @return array
 */
function getEmailConfig()
{
    static $config = null;

    // Cache the config to avoid re-reading the file
    if ($config === null) {
        $configFile = __DIR__ . '/email_config.php';

        if (!file_exists($configFile)) {
            error_log("Email config file not found: " . $configFile);
            return [
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_from_email' => '',
                'smtp_from_name' => 'AgriMarket',
                'smtp_encryption' => 'tls',
                'smtp_debug' => 0
            ];
        }

        try {
            $config = require $configFile;

            // Validate required config keys
            $requiredKeys = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_from_email'];
            foreach ($requiredKeys as $key) {
                if (!isset($config[$key]) || empty($config[$key])) {
                    error_log("Email config missing or empty key: " . $key);
                }
            }

            // Check if password is still the placeholder
            if (isset($config['smtp_password']) && $config['smtp_password'] === 'your-app-password') {
                error_log("WARNING: Email config still has placeholder password. Please update email_config.php with your Gmail app password.");
            }
        } catch (Exception $e) {
            error_log("Error loading email config: " . $e->getMessage());
            $config = [];
        }
    }

    return $config;
}

/**
 * Send verification email
 * 
 * @param string $email Recipient email
 * @param string $verificationCode Verification code
 * @param string $username Username
 * @return array
 */
function sendVerificationEmail($email, $verificationCode, $username)
{
    $subject = 'Verify Your AgriMarket Account';

    // Sanitize username for HTML output to prevent XSS
    $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
    $currentYear = date('Y');

    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .code-box { background: white; border: 2px dashed #28a745; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
            .code { font-size: 32px; font-weight: bold; color: #28a745; letter-spacing: 5px; }
            .button { display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌾 AgriMarket</h1>
            </div>
            <div class='content'>
                <h2>Welcome, " . $safeUsername . "!</h2>
                <p>Thank you for registering with AgriMarket. To complete your registration, please verify your email address.</p>
                
                <p>Your verification code is:</p>
                <div class='code-box'>
                    <div class='code'>" . $safeCode . "</div>
                </div>
                
                <p>Enter this code on the verification page to activate your account.</p>
                
                <p style='margin-top: 30px;'>If you didn't create an account with AgriMarket, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>© " . $currentYear . " AgriMarket. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $altBody = "Welcome to AgriMarket!\n\nYour verification code is: " . $verificationCode . "\n\nEnter this code on the verification page to activate your account.\n\nIf you didn't create an account, please ignore this email.";

    return sendEmail($email, $subject, $body, $altBody);
}

/**
 * Send OTP email for password reset
 * 
 * @param string $email Recipient email
 * @param string $username Username
 * @param string $otpCode 6-digit OTP code
 * @return array ['success' => bool, 'message' => string]
 */
function sendOTPEmail($email, $username, $otpCode)
{
    $subject = 'AgriMarket - Password Reset Verification Code';

    // Sanitize inputs for HTML output to prevent XSS
    $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
    $currentYear = date('Y');

    $body = "
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

    $altBody = "Password Reset Request\n\nHello " . $username . ",\n\nYou requested to reset your password for your AgriMarket account.\n\nYour verification code is: " . $otpCode . "\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this password reset, please ignore this email.\n\nThank you for using AgriMarket!\n\nBest regards,\nAgriMarket Team";

    return sendEmail($email, $subject, $body, $altBody);
}
