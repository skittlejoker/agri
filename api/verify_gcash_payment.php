<?php
// Configure session cookie settings before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if user is logged in as buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only buyers can verify payments.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $buyer_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        $input = $_POST;
    }

    $transaction_id = intval($input['transaction_id'] ?? 0);
    $verification_code = trim($input['verification_code'] ?? '');

    if ($transaction_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
        exit;
    }

    // Get transaction details
    $stmt = $pdo->prepare("
        SELECT pt.*, o.payment_status 
        FROM payment_transactions pt
        JOIN orders o ON o.id = pt.order_id
        WHERE pt.id = ? AND pt.buyer_id = ? AND pt.payment_method = 'gcash'
    ");
    $stmt->execute([$transaction_id, $buyer_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found or access denied']);
        exit;
    }

    if ($transaction['status'] === 'verified') {
        echo json_encode(['success' => false, 'message' => 'Payment already verified']);
        exit;
    }

    // Verify the verification code
    if (!empty($verification_code) && $transaction['gcash_verification_code'] !== $verification_code) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
        exit;
    }

    // For demo: Auto-verify if verification code matches or if no code provided (manual verification)
    // In production, this would integrate with GCash API to verify actual payment
    $is_verified = false;
    
    if (!empty($verification_code) && $transaction['gcash_verification_code'] === $verification_code) {
        $is_verified = true;
    } else {
        // Manual verification - mark as pending for admin review
        $is_verified = false;
    }

    if ($is_verified) {
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET status = 'verified', verified_at = NOW(), verified_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$buyer_id, $transaction_id]);

        // Update order payment status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'paid', updated_at = NOW()
            WHERE id = ? AND payment_status = 'unpaid'
        ");
        $stmt->execute([$transaction['order_id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully',
            'order_id' => $transaction['order_id']
        ]);
    } else {
        // Mark as pending for admin review
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET status = 'pending', notes = 'Awaiting manual verification'
            WHERE id = ?
        ");
        $stmt->execute([$transaction_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Payment submitted for verification. You will be notified once verified.',
            'status' => 'pending'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in verify_gcash_payment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in verify_gcash_payment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}



