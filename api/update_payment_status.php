<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Check if user is logged in as buyer
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only buyers can update payment status.']);
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

    // Support both JSON and form data
    if (!$input) {
        $input = $_POST;
    }

    $order_id = intval($input['order_id'] ?? 0);
    $payment_status = trim($input['payment_status'] ?? '');

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    if (!in_array($payment_status, ['paid', 'unpaid'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment status. Must be "paid" or "unpaid"']);
        exit;
    }

    // Check if order exists and belongs to this buyer
    $stmt = $pdo->prepare("
        SELECT o.id, o.payment_method, o.payment_status
        FROM orders o
        WHERE o.id = ? AND o.buyer_id = ?
    ");
    $stmt->execute([$order_id, $buyer_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        exit;
    }

    // Check if enhanced columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
    $hasEnhancedColumns = $stmt->rowCount() > 0;

    if (!$hasEnhancedColumns) {
        echo json_encode(['success' => false, 'message' => 'Payment features not available. Please run the database migration.']);
        exit;
    }

    // Update payment status
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ? AND buyer_id = ?");
    $result = $stmt->execute([$payment_status, $order_id, $buyer_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Payment status updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
    }
} catch (PDOException $e) {
    error_log("Database error in update_payment_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in update_payment_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}


