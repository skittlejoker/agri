<?php

/**
 * Database Setup Script for AgriMarket
 * Run this file to create the database and tables
 * 
 * Usage: Navigate to http://localhost/E-commerce/agriculture-marketplace/setup_database.php
 */

// Database configuration
$host = 'localhost';
$root_username = 'root';
$root_password = '';  // Default XAMPP password (empty)
$dbname = 'agrimarket';

echo "<h2>AgriMarket Database Setup</h2>";
echo "<pre>";

try {
    // Step 1: Connect to MySQL server without database
    echo "Step 1: Connecting to MySQL server...\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $root_username, $root_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected successfully\n\n";

    // Step 2: Create database if it doesn't exist
    echo "Step 2: Creating database '$dbname'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created/exists\n\n";

    // Step 3: Select the database
    echo "Step 3: Selecting database...\n";
    $pdo->exec("USE `$dbname`");
    echo "✓ Database selected\n\n";

    // Step 4: Create users table
    echo "Step 4: Creating users table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            user_type ENUM('farmer', 'buyer') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Users table created\n\n";

    // Step 5: Create products table
    echo "Step 5: Creating products table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            farmer_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image_url VARCHAR(500),
            stock INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Products table created\n\n";

    // Step 6: Create cart_items table
    echo "Step 6: Creating cart_items table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            buyer_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_buyer_product (buyer_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Cart items table created\n\n";

    // Step 7: Check for existing data
    echo "Step 7: Checking for existing data...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "Found $userCount existing users\n";

    // Step 8: Insert sample data if database is empty
    if ($userCount == 0) {
        echo "\nStep 8: Inserting sample data...\n";

        // Insert sample users
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO users (full_name, email, username, password, user_type) VALUES
            ('John Farmer', 'john@example.com', 'johnfarmer', '$hashedPassword', 'farmer'),
            ('Jane Buyer', 'jane@example.com', 'janebuyer', '$hashedPassword', 'buyer')
        ");
        echo "✓ Sample users inserted\n";

        // Insert sample products for farmer_id = 1
        $pdo->exec("
            INSERT INTO products (farmer_id, name, description, price, image_url, stock) VALUES
            (1, 'Organic Carrots', 'Fresh organic carrots from local farm', 3.50, 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=400', 50),
            (1, 'Fresh Tomatoes', 'Ripe red tomatoes, perfect for salads', 4.00, 'https://images.unsplash.com/photo-1546470427-5c1d0b0b0b0b?w=400', 30),
            (1, 'Green Lettuce', 'Crisp green lettuce leaves', 2.50, 'https://images.unsplash.com/photo-1622206151226-18ca2c9ab4a1?w=400', 25)
        ");
        echo "✓ Sample products inserted\n";
    } else {
        echo "✓ Database already contains data, skipping sample data insertion\n";
    }

    echo "\n";
    echo "===========================================\n";
    echo "✓ Database setup completed successfully!\n";
    echo "===========================================\n\n";

    echo "Database: $dbname\n";
    echo "Host: $host\n\n";

    echo "Sample Login Credentials:\n";
    echo "-------------------------\n";
    echo "Farmer:\n";
    echo "  Username: johnfarmer\n";
    echo "  Password: password\n\n";
    echo "Buyer:\n";
    echo "  Username: janebuyer\n";
    echo "  Password: password\n\n";

    echo "Next Steps:\n";
    echo "1. Make sure XAMPP MySQL is running\n";
    echo "2. Try logging in with the credentials above\n";
    echo "3. Navigate to the farmer dashboard\n";
    echo "4. Your database setup is complete!\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. XAMPP MySQL is running\n";
    echo "2. MySQL credentials are correct (root password is empty in default XAMPP)\n";
    echo "3. PHP has PDO MySQL extension enabled\n";
}

echo "</pre>";

