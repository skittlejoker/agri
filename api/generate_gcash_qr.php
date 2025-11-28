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
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only buyers can generate payment QR codes.']);
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

    $order_id = intval($input['order_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);
    $mobile_number = trim($input['mobile_number'] ?? '');

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }

    // Verify order belongs to buyer
    $stmt = $pdo->prepare("SELECT id, total_price, payment_status FROM orders WHERE id = ? AND buyer_id = ?");
    $stmt->execute([$order_id, $buyer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        exit;
    }

    // Check if order is already paid
    if (isset($order['payment_status']) && $order['payment_status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Order is already paid']);
        exit;
    }

    // Check if payment_transactions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_transactions'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        // Create table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payment_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                buyer_id INT NOT NULL,
                payment_method ENUM('gcash', 'bank_transfer') NOT NULL,
                transaction_reference VARCHAR(100) UNIQUE,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'verified', 'failed', 'cancelled') DEFAULT 'pending',
                gcash_qr_code TEXT NULL,
                gcash_mobile_number VARCHAR(20) NULL,
                gcash_verification_code VARCHAR(50) NULL,
                bank_name VARCHAR(100) NULL,
                bank_account_number VARCHAR(50) NULL,
                bank_account_name VARCHAR(100) NULL,
                transfer_reference VARCHAR(100) NULL,
                transfer_date DATETIME NULL,
                payment_proof_url TEXT NULL,
                verified_at DATETIME NULL,
                verified_by INT NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_order_id (order_id),
                INDEX idx_buyer_id (buyer_id),
                INDEX idx_transaction_reference (transaction_reference),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Generate unique transaction reference
    $transaction_ref = 'GCASH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
    
    // Generate verification code
    $verification_code = strtoupper(substr(md5($order_id . $buyer_id . time()), 0, 8));

    // GCash QR Code Data (using GCash format)
    // Format: gcash://pay?amount={amount}&name={merchant}&reference={ref}
    $merchant_name = 'AgriMarket';
    $qr_data = "gcash://pay?amount={$amount}&name=" . urlencode($merchant_name) . "&reference={$transaction_ref}";
    
    // Generate QR code using a QR code library or API
    // For demo purposes, we'll use a QR code API (you can use a library like phpqrcode)
    // Using a free QR code API
    $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);

    // Check if transaction already exists for this order
    $stmt = $pdo->prepare("SELECT id, transaction_reference, status FROM payment_transactions WHERE order_id = ? AND payment_method = 'gcash' AND status = 'pending'");
    $stmt->execute([$order_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing transaction
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET gcash_qr_code = ?, gcash_mobile_number = ?, gcash_verification_code = ?, 
                amount = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$qr_code_url, $mobile_number, $verification_code, $amount, $existing['id']]);
        $transaction_id = $existing['id'];
        $transaction_ref = $existing['transaction_reference'];
    } else {
        // Create new transaction
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions (
                order_id, buyer_id, payment_method, transaction_reference, amount,
                gcash_qr_code, gcash_mobile_number, gcash_verification_code, status
            ) VALUES (?, ?, 'gcash', ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $order_id, $buyer_id, $transaction_ref, $amount,
            $qr_code_url, $mobile_number, $verification_code
        ]);
        $transaction_id = $pdo->lastInsertId();
    }

    echo json_encode([
        'success' => true,
        'transaction_id' => $transaction_id,
        'transaction_reference' => $transaction_ref,
        'verification_code' => $verification_code,
        'qr_code_url' => $qr_code_url,
        'qr_data' => $qr_data,
        'amount' => $amount,
        'mobile_number' => $mobile_number,
        'message' => 'GCash QR code generated successfully'
    ]);

} catch (PDOException $e) {
    error_log("Database error in generate_gcash_qr.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in generate_gcash_qr.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}



