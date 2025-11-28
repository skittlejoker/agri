<?php

/**
 * Migration script to enhance orders table with payment, shipping, and review features
 * Run this file to update your database schema
 * 
 * Usage: Navigate to http://localhost/E-commerce/agriculture-marketplace/migrations/enhance_orders_table.php
 */

require_once '../config/database.php';

echo "<h2>Enhancing Orders Table</h2>";
echo "<pre>";

try {
    // Check if orders table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() === 0) {
        echo "ERROR: Orders table does not exist. Please create it first.\n";
        exit;
    }

    echo "Step 1: Checking existing columns...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Add columns that don't exist
    $alterStatements = [];

    if (!in_array('payment_method', $columns)) {
        $alterStatements[] = "ADD COLUMN payment_method ENUM('ewallet', 'cash_on_delivery') DEFAULT 'cash_on_delivery' AFTER status";
    }

    if (!in_array('payment_status', $columns)) {
        $alterStatements[] = "ADD COLUMN payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid' AFTER payment_method";
    }

    if (!in_array('shipping_status', $columns)) {
        $alterStatements[] = "ADD COLUMN shipping_status ENUM('to_ship', 'shipped', 'delivered') DEFAULT 'to_ship' AFTER payment_status";
    }

    if (!in_array('delivery_address', $columns)) {
        $alterStatements[] = "ADD COLUMN delivery_address TEXT AFTER shipping_status";
    }

    if (!in_array('delivery_distance', $columns)) {
        $alterStatements[] = "ADD COLUMN delivery_distance DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Distance in kilometers' AFTER delivery_address";
    }

    if (!in_array('estimated_delivery_time', $columns)) {
        $alterStatements[] = "ADD COLUMN estimated_delivery_time INT DEFAULT 0 COMMENT 'Estimated delivery time in minutes' AFTER delivery_distance";
    }

    if (!in_array('shipped_at', $columns)) {
        $alterStatements[] = "ADD COLUMN shipped_at DATETIME NULL AFTER estimated_delivery_time";
    }

    if (!in_array('delivered_at', $columns)) {
        $alterStatements[] = "ADD COLUMN delivered_at DATETIME NULL AFTER shipped_at";
    }

    if (!in_array('review_rating', $columns)) {
        $alterStatements[] = "ADD COLUMN review_rating INT NULL AFTER delivered_at";
    }

    if (!in_array('review_comment', $columns)) {
        $alterStatements[] = "ADD COLUMN review_comment TEXT NULL AFTER review_rating";
    }

    if (!empty($alterStatements)) {
        echo "Step 2: Adding new columns...\n";
        $sql = "ALTER TABLE orders " . implode(", ", $alterStatements);
        $pdo->exec($sql);
        echo "✓ Columns added successfully\n\n";
    } else {
        echo "✓ All columns already exist\n\n";
    }

    // Add indexes
    echo "Step 3: Adding indexes...\n";
    $indexStatements = [];

    $stmt = $pdo->query("SHOW INDEXES FROM orders WHERE Key_name = 'idx_payment_status'");
    if ($stmt->rowCount() === 0) {
        $indexStatements[] = "ADD INDEX idx_payment_status (payment_status)";
    }

    $stmt = $pdo->query("SHOW INDEXES FROM orders WHERE Key_name = 'idx_shipping_status'");
    if ($stmt->rowCount() === 0) {
        $indexStatements[] = "ADD INDEX idx_shipping_status (shipping_status)";
    }

    $stmt = $pdo->query("SHOW INDEXES FROM orders WHERE Key_name = 'idx_shipped_at'");
    if ($stmt->rowCount() === 0) {
        $indexStatements[] = "ADD INDEX idx_shipped_at (shipped_at)";
    }

    if (!empty($indexStatements)) {
        $sql = "ALTER TABLE orders " . implode(", ", $indexStatements);
        $pdo->exec($sql);
        echo "✓ Indexes added successfully\n\n";
    } else {
        echo "✓ All indexes already exist\n\n";
    }

    // Update existing orders with default values
    echo "Step 4: Updating existing orders...\n";
    $updateSql = "UPDATE orders SET 
        payment_method = COALESCE(payment_method, 'cash_on_delivery'),
        payment_status = COALESCE(payment_status, CASE 
            WHEN status = 'completed' THEN 'paid'
            ELSE 'unpaid'
        END),
        shipping_status = COALESCE(shipping_status, CASE
            WHEN status = 'completed' THEN 'delivered'
            WHEN status = 'confirmed' THEN 'to_ship'
            ELSE 'to_ship'
        END)
    WHERE payment_method IS NULL OR payment_status IS NULL OR shipping_status IS NULL";

    $affected = $pdo->exec($updateSql);
    echo "✓ Updated {$affected} existing orders\n\n";

    echo "Migration completed successfully!\n";
    echo "</pre>";
} catch (PDOException $e) {
    echo "ERROR: Database error - " . $e->getMessage() . "\n";
    echo "</pre>";
    exit;
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "</pre>";
    exit;
}


