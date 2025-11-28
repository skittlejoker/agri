<?php
// Prevent any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/database.php';
require_once '../config/phpmailer_helper.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['email']) || empty(trim($input['email']))) {
    sendResponse(['error' => 'Email is required'], 400);
}

$email = trim($input['email']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(['error' => 'Invalid email format'], 400);
}

try {
    // Check if user exists and is not verified
    $stmt = $pdo->prepare("SELECT id, username, email, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Don't reveal if email exists for security
        sendResponse([
            'success' => true,
            'message' => 'If the email exists in our system, a verification code has been sent.'
        ]);
    }

    // Check if already verified
    if (isset($user['is_verified']) && $user['is_verified'] == 1) {
        sendResponse([
            'success' => false,
            'error' => 'Email is already verified'
        ]);
    }

    // Generate new secure 6-digit verification code
    $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Update verification code in database
    try {
        $stmt = $pdo->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
        $stmt->execute([$verificationCode, $email]);
    } catch (PDOException $e) {
        // If verification_code column doesn't exist, try to add it
        if (strpos($e->getMessage(), 'verification_code') !== false) {
            sendResponse([
                'success' => false,
                'error' => 'Email verification not set up. Please run sql/add_email_verification.sql'
            ]);
        }
        throw $e;
    }

    // Send verification email using PHPMailer
    try {
        error_log("Resending verification email to $email");
        $emailResult = sendVerificationEmail($email, $verificationCode, $user['username']);

        if ($emailResult['success']) {
            $gmailQueued = isset($emailResult['gmail_queued']) && $emailResult['gmail_queued'];
            $message = 'Verification code has been resent to your email address. Please check your inbox and spam folder.';

            if (!$gmailQueued) {
                $message .= ' If not received, check Gmail security settings for blocked sign-in attempts.';
            }

            sendResponse([
                'success' => true,
                'message' => $message,
                'gmail_queued' => $gmailQueued,
                'verification_code' => $verificationCode // Include for testing if email fails
            ]);
        } else {
            error_log("Resend verification email failed for {$email}: " . $emailResult['message']);
            if (isset($emailResult['debug']) && !empty($emailResult['debug'])) {
                error_log("SMTP Debug: " . $emailResult['debug']);
            }

            sendResponse([
                'success' => false,
                'error' => 'Failed to send email. Please try again later.',
                'message' => $emailResult['message'],
                'verification_code' => $verificationCode // Include for testing
            ]);
        }
    } catch (Exception $e) {
        error_log("Exception resending verification email: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'error' => 'Failed to send email. Please try again later.',
            'message' => 'Email service error. Please contact support if the problem persists.',
            'verification_code' => $verificationCode // Include for testing
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in resend_verification_code.php: " . $e->getMessage());
    sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("General error in resend_verification_code.php: " . $e->getMessage());
    sendResponse(['error' => 'An error occurred. Please try again.'], 500);
}
