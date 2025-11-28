<?php
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'userType' => $_SESSION['user_type'],
            'fullName' => $_SESSION['full_name']
        ]
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
exit;
