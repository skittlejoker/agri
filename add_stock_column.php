<?php

/**
 * Add stock column to products table if it doesn't exist
 * Run this file once to add the stock column to your database
 */

require_once 'config/database.php';

try {
    echo "Checking for stock column in products table...\n\n";

    // Check if stock column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
    $hasStockColumn = $stmt->rowCount() > 0;

    if ($hasStockColumn) {
        echo "✓ Stock column already exists in products table.\n";
        echo "No action needed.\n";
        exit(0);
    }

    // Add stock column
    echo "Stock column not found. Adding it now...\n";

    $pdo->exec("ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 AFTER image_url");

    echo "✓ Stock column added successfully!\n\n";
    echo "All existing products now have stock = 0 by default.\n";
    echo "You can now update product stock quantities in the farmer dashboard.\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";

    // Try alternative syntax if the first one fails
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "\nNote: Stock column may already exist.\n";
        exit(0);
    }

    exit(1);
}

