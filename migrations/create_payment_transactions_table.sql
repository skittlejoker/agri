-- Payment Transactions Table
-- Stores payment transaction details for GCash and Bank Transfer

CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    buyer_id INT NOT NULL,
    payment_method ENUM('gcash', 'bank_transfer') NOT NULL,
    transaction_reference VARCHAR(100) UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'verified', 'failed', 'cancelled') DEFAULT 'pending',
    
    -- GCash specific fields
    gcash_qr_code TEXT NULL,
    gcash_mobile_number VARCHAR(20) NULL,
    gcash_verification_code VARCHAR(50) NULL,
    
    -- Bank Transfer specific fields
    bank_name VARCHAR(100) NULL,
    bank_account_number VARCHAR(50) NULL,
    bank_account_name VARCHAR(100) NULL,
    transfer_reference VARCHAR(100) NULL,
    transfer_date DATETIME NULL,
    
    -- Common fields
    payment_proof_url TEXT NULL,
    verified_at DATETIME NULL,
    verified_by INT NULL,
    notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_order_id (order_id),
    INDEX idx_buyer_id (buyer_id),
    INDEX idx_transaction_reference (transaction_reference),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



