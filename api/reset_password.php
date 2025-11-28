<?php
// Start output buffering
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Get JSON input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Log received data for debugging
error_log("reset_password.php: Received request - Raw input length: " . strlen($rawInput));
if ($input && isset($input['token'])) {
    error_log("reset_password.php: Token received - Length: " . strlen($input['token']) . ", Preview: " . substr($input['token'], 0, 10) . "...");
} else {
    error_log("reset_password.php: No token in request or failed to parse JSON");
}

// Validate required fields
if (!isset($input['token']) || empty(trim($input['token']))) {
    ob_clean();
    error_log("reset_password.php: Missing reset token");
    sendResponse(['error' => 'Reset token is required'], 400);
}

if (!isset($input['newPassword']) || empty(trim($input['newPassword']))) {
    ob_clean();
    error_log("reset_password.php: Missing new password");
    sendResponse(['error' => 'New password is required'], 400);
}

if (!isset($input['confirmPassword']) || empty(trim($input['confirmPassword']))) {
    ob_clean();
    error_log("reset_password.php: Missing password confirmation");
    sendResponse(['error' => 'Password confirmation is required'], 400);
}

$token = trim($input['token']);
$newPassword = $input['newPassword'];
$confirmPassword = $input['confirmPassword'];

// Validate token format (should be 64 character hex string)
if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    ob_clean();
    error_log("reset_password.php: Invalid token format - {$token}");
    sendResponse(['error' => 'Invalid reset token format'], 400);
}

// Validate password match
if ($newPassword !== $confirmPassword) {
    ob_clean();
    error_log("reset_password.php: Passwords do not match");
    sendResponse(['error' => 'Passwords do not match'], 400);
}

// Validate password length
if (strlen($newPassword) < 6) {
    ob_clean();
    error_log("reset_password.php: Password too short");
    sendResponse(['error' => 'Password must be at least 6 characters long'], 400);
}

// Validate password strength (optional but recommended)
if (strlen($newPassword) > 72) {
    ob_clean();
    error_log("reset_password.php: Password too long");
    sendResponse(['error' => 'Password is too long (maximum 72 characters)'], 400);
}

try {
    // Log the token being checked
    error_log("reset_password.php: Checking token - Length: " . strlen($token) . ", Preview: " . substr($token, 0, 10) . "...");
    
    // First, check if token exists (without JOIN to avoid issues)
    $checkStmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ?");
    $checkStmt->execute([$token]);
    $tokenCheck = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenCheck) {
        // Token doesn't exist at all
        $allTokensStmt = $pdo->prepare("SELECT token, expires_at, used, created_at FROM password_reset_tokens ORDER BY created_at DESC LIMIT 5");
        $allTokensStmt->execute();
        $allTokens = $allTokensStmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("reset_password.php: Token not found. Recent tokens: " . json_encode($allTokens));
        
        ob_clean();
        error_log("reset_password.php: ❌ Invalid token - Length: " . strlen($token) . ", Preview: " . substr($token, 0, 10) . "...");
        sendResponse(['error' => 'Invalid reset token. Please verify your code again to get a new token.'], 400);
    }
    
    // Check if token is already used
    if ($tokenCheck['used'] == 1) {
        ob_clean();
        error_log("reset_password.php: Token already used - " . substr($token, 0, 10) . "...");
        sendResponse(['error' => 'This reset link has already been used. Please verify your code again to get a new token.'], 400);
    }
    
    // Check if token is expired
    $expiresAt = strtotime($tokenCheck['expires_at']);
    $currentTime = time();
    if ($expiresAt <= $currentTime) {
        ob_clean();
        $expiredMinutes = round(($currentTime - $expiresAt) / 60);
        error_log("reset_password.php: Token expired - " . substr($token, 0, 10) . "... expired {$expiredMinutes} minutes ago. Expires: {$tokenCheck['expires_at']}, Now: " . date('Y-m-d H:i:s'));
        sendResponse(['error' => 'Reset token has expired. Please verify your code again to get a new token.'], 400);
    }
    
    // Token is valid! Now verify user exists
    $userId = $tokenCheck['user_id'];
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        ob_clean();
        error_log("reset_password.php: User not found for token - user_id: {$userId}");
        sendResponse(['error' => 'User account not found'], 400);
    }
    
    // All checks passed - token is valid
    error_log("reset_password.php: ✅ Valid token found for user_id: {$userId}, Expires: {$tokenCheck['expires_at']}");
    $tokenData = $tokenCheck;
    $tokenData['user_id'] = $userId; // Ensure user_id is set

    // Token validation passed above, user validation also done
    $userId = $tokenData['user_id'];
    error_log("reset_password.php: Processing password creation for user_id: {$userId}");

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    if (!$hashedPassword) {
        ob_clean();
        error_log("reset_password.php: Failed to hash password");
        sendResponse(['error' => 'Failed to process password. Please try again.'], 500);
    }

    // Update user password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $success = $stmt->execute([$hashedPassword, $userId]);

    if (!$success) {
        ob_clean();
        error_log("reset_password.php: Failed to update password for user_id: {$userId}");
        sendResponse(['error' => 'Failed to reset password. Please try again.'], 500);
    }

    // Mark token as used
    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
    $stmt->execute([$token]);

    // Log successful password creation
    error_log("reset_password.php: Password created successfully for user_id: {$userId}");
    
    ob_clean();
    sendResponse([
        'success' => true,
        'message' => 'Password has been created successfully. You can now login with your new password.'
    ]);
} catch (PDOException $e) {
    ob_clean();
    error_log("reset_password.php: Database error: " . $e->getMessage());
    error_log("reset_password.php: Stack trace: " . $e->getTraceAsString());
    sendResponse(['error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    ob_clean();
    error_log("reset_password.php: General error: " . $e->getMessage());
    error_log("reset_password.php: Stack trace: " . $e->getTraceAsString());
    sendResponse(['error' => 'An error occurred. Please try again.'], 500);
}
