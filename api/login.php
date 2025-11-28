<?php
require_once '../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['username', 'password', 'userType'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        sendResponse(['error' => ucfirst($field) . ' is required'], 400);
    }
}

$username = trim($input['username']);
$password = $input['password'];
$userType = $input['userType'];

// Validate user type
if (!in_array($userType, ['farmer', 'buyer'])) {
    sendResponse(['error' => 'Invalid user type'], 400);
}

try {
    // Check if is_verified column exists
    $verificationEnabled = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
        $verificationEnabled = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Column doesn't exist or error - continue without verification check
        error_log("is_verified column check: " . $e->getMessage());
    }
    
    // Find user by username and user type
    if ($verificationEnabled) {
        $stmt = $pdo->prepare("SELECT id, username, password, user_type, full_name, email, is_verified FROM users WHERE username = ? AND user_type = ?");
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, user_type, full_name, email FROM users WHERE username = ? AND user_type = ?");
    }
    $stmt->execute([$username, $userType]);
    $user = $stmt->fetch();

    if (!$user) {
        sendResponse(['error' => 'Invalid username or user type'], 401);
    }

    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        sendResponse(['error' => 'Invalid password'], 401);
    }

    // Check if email is verified (only if verification is enabled)
    if ($verificationEnabled && isset($user['is_verified']) && $user['is_verified'] != 1) {
        sendResponse([
            'error' => 'Email not verified',
            'requires_verification' => true,
            'email' => $user['email'] ?? ''
        ], 403);
    }

    // Start session
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['full_name'] = $user['full_name'];

    sendResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'userType' => $user['user_type'],
            'fullName' => $user['full_name']
        ]
    ]);
} catch (PDOException $e) {
    sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>
