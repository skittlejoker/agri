<?php
// Configure session cookie settings before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if user is logged in as farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only farmers can view payments.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $farmer_id = $_SESSION['user_id'];
    
    // Check if payment_transactions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_transactions'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo json_encode(['success' => true, 'payments' => [], 'total_received' => 0, 'message' => 'No payment transactions table found']);
        exit;
    }

    // Get all payments for orders where this farmer is the seller
    // Join with orders to get farmer_id
    $stmt = $pdo->prepare("
        SELECT 
            pt.id AS transaction_id,
            pt.order_id,
            pt.payment_method,
            pt.transaction_reference,
            pt.amount,
            pt.status AS payment_status,
            pt.created_at AS payment_date,
            pt.verified_at,
            
            o.id AS order_id,
            o.buyer_id,
            o.product_id,
            o.quantity,
            o.unit_price,
            o.total_price,
            o.payment_status AS order_payment_status,
            o.shipping_status,
            o.created_at AS order_date,
            
            p.name AS product_name,
            p.image_url AS product_image,
            
            u.full_name AS buyer_name,
            u.email AS buyer_email,
            
            pt.gcash_mobile_number,
            pt.gcash_verification_code,
            pt.bank_name,
            pt.bank_account_number,
            pt.bank_account_name,
            pt.transfer_reference,
            pt.transfer_date,
            pt.notes
        FROM payment_transactions pt
        JOIN orders o ON o.id = pt.order_id
        JOIN products p ON p.id = o.product_id
        JOIN users u ON u.id = o.buyer_id
        WHERE o.farmer_id = ?
        ORDER BY pt.created_at DESC
    ");
    $stmt->execute([$farmer_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $total_received = 0;
    $pending_amount = 0;
    $verified_amount = 0;

    foreach ($transactions as $transaction) {
        if ($transaction['payment_status'] === 'verified') {
            $verified_amount += floatval($transaction['amount']);
            $total_received += floatval($transaction['amount']);
        } else if ($transaction['payment_status'] === 'pending') {
            $pending_amount += floatval($transaction['amount']);
        }
    }

    // Format transactions for response
    $formatted_transactions = array_map(function($t) {
        return [
            'transaction_id' => $t['transaction_id'],
            'order_id' => $t['order_id'],
            'product_name' => $t['product_name'],
            'product_image' => $t['product_image'],
            'buyer_name' => $t['buyer_name'],
            'buyer_email' => $t['buyer_email'],
            'quantity' => intval($t['quantity']),
            'unit_price' => floatval($t['unit_price']),
            'total_price' => floatval($t['total_price']),
            'payment_method' => $t['payment_method'],
            'payment_status' => $t['payment_status'],
            'transaction_reference' => $t['transaction_reference'],
            'amount' => floatval($t['amount']),
            'payment_date' => $t['payment_date'],
            'verified_at' => $t['verified_at'],
            'order_date' => $t['order_date'],
            'order_payment_status' => $t['order_payment_status'],
            'shipping_status' => $t['shipping_status'],
            'payment_details' => [
                'gcash_mobile_number' => $t['gcash_mobile_number'],
                'gcash_verification_code' => $t['gcash_verification_code'],
                'bank_name' => $t['bank_name'],
                'bank_account_number' => $t['bank_account_number'],
                'bank_account_name' => $t['bank_account_name'],
                'transfer_reference' => $t['transfer_reference'],
                'transfer_date' => $t['transfer_date']
            ],
            'notes' => $t['notes']
        ];
    }, $transactions);

    echo json_encode([
        'success' => true,
        'payments' => $formatted_transactions,
        'summary' => [
            'total_received' => $total_received,
            'pending_amount' => $pending_amount,
            'verified_amount' => $verified_amount,
            'total_transactions' => count($transactions)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_farmer_payments.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in get_farmer_payments.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

