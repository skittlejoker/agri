<?php
// Start output buffering to prevent any accidental output
ob_start();

// Suppress error output to prevent HTML from being sent before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers first to ensure JSON response
header('Content-Type: application/json');

// Start session
session_start();

// Include database connection
require_once '../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }

    // Validate required fields
    $required_fields = ['oldPassword', 'newPassword', 'confirmPassword'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => ucfirst($field) . ' is required']);
            exit;
        }
    }

    $oldPassword = $input['oldPassword'];
    $newPassword = $input['newPassword'];
    $confirmPassword = $input['confirmPassword'];

    // Validate password match
    if ($newPassword !== $confirmPassword) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
        exit;
    }

    // Validate password length
    if (strlen($newPassword) < 6) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters long']);
        exit;
    }

    // Get user from session
    if (!isset($_SESSION['user_id'])) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Get current password from database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    // Verify old password
    if (!password_verify($oldPassword, $user['password'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        exit;
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $success = $stmt->execute([$hashedPassword, $userId]);

    if ($success) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
        exit;
    } else {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to change password']);
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error in change_password.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    exit;
} catch (Exception $e) {
    error_log("Error in change_password.php: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
    exit;
}
