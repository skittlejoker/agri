<?php
// Configure session cookie settings before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if user is logged in as farmer or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'farmer' && $_SESSION['user_type'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only farmers and admins can verify payments.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        $input = $_POST;
    }

    $transaction_id = intval($input['transaction_id'] ?? 0);
    $action = trim($input['action'] ?? ''); // 'verify' or 'reject'
    $notes = trim($input['notes'] ?? '');

    if ($transaction_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
        exit;
    }

    if (!in_array($action, ['verify', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid action. Use "verify" or "reject"']);
        exit;
    }

    // Get transaction details
    $stmt = $pdo->prepare("
        SELECT pt.*, o.farmer_id, o.payment_status AS order_payment_status
        FROM payment_transactions pt
        JOIN orders o ON o.id = pt.order_id
        WHERE pt.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Check if farmer owns this order (if user is farmer)
    if ($user_type === 'farmer' && intval($transaction['farmer_id']) !== $user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. This payment does not belong to you.']);
        exit;
    }

    if ($action === 'verify') {
        // Verify payment
        if ($transaction['status'] === 'verified') {
            echo json_encode(['success' => false, 'message' => 'Payment already verified']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET status = 'verified', verified_at = NOW(), verified_by = ?, notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$user_id, $notes, $transaction_id]);

        // Update order payment status if not already paid
        if ($transaction['order_payment_status'] !== 'paid') {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'paid', updated_at = NOW()
                WHERE id = ? AND payment_status = 'unpaid'
            ");
            $stmt->execute([$transaction['order_id']]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully',
            'transaction_id' => $transaction_id,
            'order_id' => $transaction['order_id']
        ]);

    } else if ($action === 'reject') {
        // Reject payment
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET status = 'failed', notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$notes ?: 'Payment rejected', $transaction_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Payment rejected',
            'transaction_id' => $transaction_id
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error in verify_payment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in verify_payment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

