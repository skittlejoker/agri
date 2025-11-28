<?php
// Start output buffering
ob_start();

// Suppress error output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');

require_once '../config/database.php';

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

if (!isset($input['verification_code']) || empty(trim($input['verification_code']))) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Verification code is required']);
    exit;
}

$email = trim($input['email']);
$verificationCode = trim($input['verification_code']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

try {
    // Check if verification columns exist
    $columnsExist = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
        $columnsExist = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist or error checking
        error_log("Error checking columns: " . $e->getMessage());
    }
    
    if (!$columnsExist) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Email verification not set up. Please run sql/add_email_verification.sql']);
        exit;
    }
    
    // Find user by email and verification code
    $stmt = $pdo->prepare("SELECT id, username, user_type, is_verified FROM users WHERE email = ? AND verification_code = ?");
    $stmt->execute([$email, $verificationCode]);
    $user = $stmt->fetch();

    if (!$user) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email or verification code']);
        exit;
    }

    // Check if already verified
    if ($user['is_verified'] == 1) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already verified']);
        exit;
    }

    // Update user to verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
    $success = $stmt->execute([$user['id']]);

    if ($success) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully! You can now log in.',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'userType' => $user['user_type']
            ]
        ]);
        exit;
    } else {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to verify email']);
        exit;
    }

} catch (PDOException $e) {
    error_log("Database error in verify.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    exit;
} catch (Exception $e) {
    error_log("Error in verify.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
    exit;
}
?>

