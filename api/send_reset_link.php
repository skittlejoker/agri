<?php
session_start();
require_once '../config/database.php';

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

if (!isset($input['username']) || empty(trim($input['username']))) {
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
        sendResponse([
            'success' => true,
            'message' => 'If the email exists in our system, a reset link has been sent.'
        ]);
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Create password_reset_tokens table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    try {
        $pdo->exec($createTableSQL);
    } catch (PDOException $e) {
        // Table might already exist, ignore
    }

    // Delete old unused tokens for this user
    $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND (used = 1 OR expires_at < NOW())");
    $stmt->execute([$user['id']]);

    // Insert new token
    $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expiresAt]);

    // In production, send email with reset link
    // For now, we'll return the token (in production, this should be sent via email)
    $resetLink = "http://localhost/E-commerce/agriculture-marketplace/reset_password.html?token=" . $token;

    // TODO: Send email with reset link
    // mail($email, 'Password Reset Request', "Click this link to reset your password: " . $resetLink);

    sendResponse([
        'success' => true,
        'message' => 'Password reset link has been generated.',
        'reset_link' => $resetLink, // Only for development - remove in production
        'token' => $token // Only for development - remove in production
    ]);
} catch (PDOException $e) {
    sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
