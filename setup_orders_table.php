<?php

/**
 * Setup script to create orders table
 * Run this file once to create the orders/transactions table
 */

require_once 'config/database.php';

try {
    echo "Setting up orders table...\n\n";

    // Check if orders table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() > 0) {
        echo "Orders table already exists.\n";
        echo "If you want to recreate it, drop it first.\n";
        exit;
    }

    // Create orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            buyer_id INT NOT NULL,
            farmer_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_buyer (buyer_id),
            INDEX idx_farmer (farmer_id),
            INDEX idx_product (product_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "✓ Orders table created successfully!\n\n";
    echo "You can now:\n";
    echo "1. Buyers can place orders from their cart\n";
    echo "2. Farmers can view and manage orders\n";
    echo "3. Product stock automatically updates when orders are placed\n";
} catch (PDOException $e) {
    echo "✗ Error creating orders table: " . $e->getMessage() . "\n";
    exit(1);
}

