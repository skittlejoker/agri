<?php
// Start output buffering
ob_start();

// Suppress error output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Try to load PHPMailer helper, but don't fail if it's not available
$phpmailerHelper = __DIR__ . '/../config/phpmailer_helper.php';
if (file_exists($phpmailerHelper)) {
    require_once $phpmailerHelper;
    $phpmailerAvailable = true;
} else {
    $phpmailerAvailable = false;
    error_log("PHPMailer helper not found. Email verification will be disabled.");
}

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
$required_fields = ['fullName', 'email', 'username', 'password', 'confirmPassword', 'userType'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => ucfirst($field) . ' is required']);
        exit;
    }
}

$fullName = trim($input['fullName']);
$email = trim($input['email']);
$username = trim($input['username']);
$password = $input['password'];
$confirmPassword = $input['confirmPassword'];
$userType = $input['userType'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// Validate user type
if (!in_array($userType, ['farmer', 'buyer'])) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user type']);
    exit;
}

// Validate password match
if ($password !== $confirmPassword) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Passwords do not match']);
    exit;
}

// Validate password length
if (strlen($password) < 6) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit;
}

try {
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username already taken']);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }

    // Generate secure verification code (6-digit random number)
    // Use random_int for cryptographically secure random number
    $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Hash password
    $hashedPassword = hashPassword($password);

    // Check if verification columns exist, if not use default values
    // Insert new user with is_verified = 0
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password, user_type, verification_code, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$fullName, $email, $username, $hashedPassword, $userType, $verificationCode]);
    } catch (PDOException $e) {
        // If columns don't exist, try without them (backward compatibility)
        if (strpos($e->getMessage(), 'verification_code') !== false || strpos($e->getMessage(), 'is_verified') !== false) {
            error_log("Verification columns not found. Please run: sql/add_email_verification.sql");
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password, user_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $username, $hashedPassword, $userType]);
            // Set verification code in session or return it directly
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! Database needs update for email verification.',
                'verification_code' => $verificationCode, // Return code directly if DB not updated
                'user' => [
                    'id' => $pdo->lastInsertId(),
                    'username' => $username,
                    'userType' => $userType,
                    'email' => $email
                ],
                'email_sent' => false,
                'warning' => 'Please run sql/add_email_verification.sql to enable email verification'
            ]);
            exit;
        } else {
            throw $e; // Re-throw if it's a different error
        }
    }

    $userId = $pdo->lastInsertId();

    // Send verification email if PHPMailer is available
    $emailResult = ['success' => false, 'message' => 'PHPMailer not available'];
    if ($phpmailerAvailable && function_exists('sendVerificationEmail')) {
        try {
            // Clear any existing output buffer before sending email
            // This prevents interference with PHPMailer's output buffering
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            error_log("Sending verification email to $email for user $userId");
            $emailResult = sendVerificationEmail($email, $verificationCode, $username);

            // Restart output buffering after email is sent
            ob_start();

            if ($emailResult['success']) {
                // Check if Gmail actually queued the email
                $gmailQueued = isset($emailResult['gmail_queued']) && $emailResult['gmail_queued'];
                $deliveryConfirmed = isset($emailResult['delivery_confirmed']) && $emailResult['delivery_confirmed'];

                if ($gmailQueued || $deliveryConfirmed) {
                    error_log("Verification email queued by Gmail for $email");
                } else {
                    error_log("Warning: Verification email sent but Gmail confirmation unclear for $email");
                }
            } else {
                // Log detailed error with full context
                $errorLog = "Failed to send verification email to $email for user $userId\n";
                $errorLog .= "Error: " . $emailResult['message'] . "\n";

                if (isset($emailResult['debug']) && !empty($emailResult['debug'])) {
                    $errorLog .= "SMTP Debug Output:\n" . $emailResult['debug'] . "\n";
                }

                if (isset($emailResult['smtp_responses']) && !empty($emailResult['smtp_responses'])) {
                    $errorLog .= "SMTP Responses: " . json_encode($emailResult['smtp_responses']) . "\n";
                }

                if (isset($emailResult['analysis']) && !empty($emailResult['analysis'])) {
                    $errorLog .= "Analysis: " . implode('; ', $emailResult['analysis']) . "\n";
                }

                error_log($errorLog);
            }
        } catch (Exception $e) {
            error_log("Exception sending verification email: " . $e->getMessage());
            $emailResult = ['success' => false, 'message' => $e->getMessage()];
        }
    } else {
        error_log("Email verification disabled: PHPMailer not available");
    }

    ob_clean();

    // Prepare response message
    $message = 'Registration successful! ';
    if ($emailResult['success']) {
        $gmailQueued = isset($emailResult['gmail_queued']) && $emailResult['gmail_queued'];
        if ($gmailQueued) {
            $message .= 'Verification code has been sent to your email. Please check your inbox.';
        } else {
            $message .= 'Verification code has been sent. Please check your inbox and spam folder. If not received, check Gmail security settings.';
        }
    } else {
        $message .= 'However, we could not send the verification email. Please use the resend code feature or contact support.';
        // Include verification code temporarily for testing (remove in production)
        $message .= ' Verification code: ' . $verificationCode;
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'user' => [
            'id' => $userId,
            'username' => $username,
            'userType' => $userType,
            'email' => $email
        ],
        'email_sent' => $emailResult['success'],
        'gmail_queued' => isset($emailResult['gmail_queued']) && $emailResult['gmail_queued'],
        'verification_code' => $verificationCode // Include for testing if email fails
    ]);
    exit;
} catch (PDOException $e) {
    error_log("Database error in register.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    exit;
} catch (Exception $e) {
    error_log("Error in register.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
    exit;
}
