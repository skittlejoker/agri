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
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only buyers can create bank transfers.']);
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
    $bank_name = trim($input['bank_name'] ?? '');
    $bank_account_number = trim($input['bank_account_number'] ?? '');
    $bank_account_name = trim($input['bank_account_name'] ?? '');
    $transfer_reference = trim($input['transfer_reference'] ?? '');
    $transfer_date = trim($input['transfer_date'] ?? '');

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }

    if (empty($bank_name) || empty($bank_account_number) || empty($bank_account_name)) {
        echo json_encode(['success' => false, 'message' => 'Bank details are required']);
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
    $transaction_ref = 'BANK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
    
    // If no transfer reference provided, use transaction reference
    if (empty($transfer_reference)) {
        $transfer_reference = $transaction_ref;
    }

    // Parse transfer date
    $transfer_date_formatted = null;
    if (!empty($transfer_date)) {
        $transfer_date_formatted = date('Y-m-d H:i:s', strtotime($transfer_date));
    } else {
        $transfer_date_formatted = date('Y-m-d H:i:s');
    }

    // Check if transaction already exists for this order
    $stmt = $pdo->prepare("SELECT id, transaction_reference, status FROM payment_transactions WHERE order_id = ? AND payment_method = 'bank_transfer' AND status = 'pending'");
    $stmt->execute([$order_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing transaction
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET bank_name = ?, bank_account_number = ?, bank_account_name = ?,
                transfer_reference = ?, transfer_date = ?, amount = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $bank_name, $bank_account_number, $bank_account_name,
            $transfer_reference, $transfer_date_formatted, $amount, $existing['id']
        ]);
        $transaction_id = $existing['id'];
        $transaction_ref = $existing['transaction_reference'];
    } else {
        // Create new transaction
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions (
                order_id, buyer_id, payment_method, transaction_reference, amount,
                bank_name, bank_account_number, bank_account_name, 
                transfer_reference, transfer_date, status
            ) VALUES (?, ?, 'bank_transfer', ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $order_id, $buyer_id, $transaction_ref, $amount,
            $bank_name, $bank_account_number, $bank_account_name,
            $transfer_reference, $transfer_date_formatted
        ]);
        $transaction_id = $pdo->lastInsertId();
    }

    // Bank account details for payment (these would be your merchant account details)
    $merchant_bank_details = [
        'bank_name' => 'BDO (Banco de Oro)',
        'account_number' => '1234-5678-9012',
        'account_name' => 'AgriMarket Inc.',
        'swift_code' => 'BNORPHMM'
    ];

    echo json_encode([
        'success' => true,
        'transaction_id' => $transaction_id,
        'transaction_reference' => $transaction_ref,
        'amount' => $amount,
        'merchant_bank_details' => $merchant_bank_details,
        'transfer_reference' => $transfer_reference,
        'message' => 'Bank transfer transaction created. Please complete the transfer and upload proof of payment.'
    ]);

} catch (PDOException $e) {
    error_log("Database error in create_bank_transfer.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in create_bank_transfer.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}



