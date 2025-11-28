<?php
/**
 * Send Verification Email API
 * Uses EXACT same email sending method as test_registration_direct.php (which works!)
 */

// Start output buffering to prevent any output before JSON
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['email']) || empty(trim($input['email']))) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

$email = trim($input['email']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

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
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, username, email, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal if email exists for security
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'If the email exists in our system, a verification code has been sent.'
        ]);
        exit;
    }
    
    // Check if already verified
    if (isset($user['is_verified']) && $user['is_verified'] == 1) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Email is already verified'
        ]);
        exit;
    }
    
    // Generate new secure 6-digit verification code
    $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Update verification code in database
    try {
        $stmt = $pdo->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
        $stmt->execute([$verificationCode, $email]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'verification_code') !== false) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Email verification not set up. Please run sql/add_email_verification.sql'
            ]);
            exit;
        }
        throw $e;
    }
    
    // Clear any existing output buffer before sending email
    // This prevents interference with PHPMailer's output buffering
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Send email using EXACT same method as test_registration_direct.php
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    // Capture debug output
    $debugOutput = '';
    $smtpResponses = [];
    
    // Method 1: Output buffering (start fresh after clearing)
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
    
    // Use registration email format
    $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Verify Your AgriMarket Account';
    
    // Registration email body (same format as sendVerificationEmail)
    $safeUsername = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
    $currentYear = date('Y');
    
    $mail->Body = "
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
    
    $mail->AltBody = "Welcome to AgriMarket!\n\nYour verification code is: " . $verificationCode . "\n\nEnter this code on the verification page to activate your account.\n\nIf you didn't create an account, please ignore this email.";
    
    try {
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
        
        // Clear any output before sending JSON
        ob_clean();
        
        if ($sent && $gmailQueued) {
            error_log("Verification email queued by Gmail for $email - Code: $verificationCode");
            echo json_encode([
                'success' => true,
                'message' => 'Verification code has been sent to your email. Please check your inbox.',
                'gmail_queued' => true,
                'verification_code' => $verificationCode // Include for testing if email fails
            ]);
        } elseif ($sent) {
            error_log("Verification email sent but Gmail confirmation unclear for $email - Code: $verificationCode");
            echo json_encode([
                'success' => true,
                'message' => 'Verification code has been sent. Please check your inbox and spam folder. If not received, check Gmail security settings.',
                'gmail_queued' => false,
                'verification_code' => $verificationCode // Include for testing if email fails
            ]);
        } else {
            $errorInfo = $mail->ErrorInfo ?? 'Unknown error';
            error_log("Failed to send verification email to $email: $errorInfo - Code: $verificationCode");
            error_log("SMTP Debug: " . $debugOutput);
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Failed to send email: ' . $errorInfo,
                'verification_code' => $verificationCode // Include for testing if email fails
            ]);
        }
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $buffered = ob_get_clean();
        error_log("PHPMailer Exception sending verification email to $email: " . $e->getMessage());
        error_log("SMTP Debug: " . $debugOutput);
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Email sending failed: ' . $e->getMessage(),
            'verification_code' => $verificationCode // Include for testing if email fails
        ]);
    }
    
} catch (PDOException $e) {
    ob_clean();
    error_log("Database error in send_verification_email.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
} catch (Exception $e) {
    ob_clean();
    error_log("Error in send_verification_email.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
?>

