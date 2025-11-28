<?php
// Database configuration
$host = 'localhost';
$dbname = 'agrimarket';
$username = 'root';  // Default XAMPP MySQL username
$password = '';      // Default XAMPP MySQL password (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Log error but don't die - let the API handle it
    error_log("Database connection failed: " . $e->getMessage());
    // Re-throw the exception so calling code can handle it
    throw new Exception("Database connection failed: " . $e->getMessage());
}

// Helper function to send JSON response
function sendResponse($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to hash passwords
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Helper function to verify passwords
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}
